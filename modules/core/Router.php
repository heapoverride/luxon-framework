<?php

class Router {
	/**
	 * @var Route[]
	 */
	private static $routes = [];

	/**
	 * @var string Regular expression to match index files
	 */
	private static $index = "/^index\.(php|html)$/i";

	/**
	 * @var string[] Disallowed extensions that should not be served by `Router::serve` method
	 */
	private static $disallowed = ['.php', '.sql'];

	/**
	 * Set by `Router::continue` method to tell router to keep trying even after a match has been found
	 */
	private static $continue = false;

	/**
	 * @var string|null Set hostname
	 */
	private static $host = null;

	/**
	 * @var string|null Set base
	 */
	private static $base = null;
	
	/**
	 * Add new route
	 * @param string $method HTTP request method (`"*"` to match any)
	 * @param string $path Regular expression to match query path
	 * @param callable $action Handler for this route
	 */
	public static function route($method, $path, $action) {
		$route = new Route($method, $path, $action);
		if (self::$host !== null) { $route->host = self::$host; }
		if (self::$base !== null) { $route->base = self::$base; }

		self::$routes[] = $route;
	}

	/**
	 * Set active path (all subsequent calls to `Router::route` will use this path)
	 * @param string|false $base
	 */
	public static function usePath($path = false) {
		if ($path === false) {
			self::$base = null;
		} else {
			self::$base = $path;
		}
	}

	/**
	 * Set active hostname (all subsequent calls to `Router::route` will use this virtual host)
	 * @param string|false $host Hostname
	 */
	public static function useHost($host = false) {
		if ($host === false) {
			self::$host = null;
		} else {
			self::$host = $host;
		}
	}

	/**
	 * Call this method from your route's callback function to tell router\
	 * to keep looking for another route
	 */
	public static function continue() {
		self::$continue = true;
	}

	private static function isAllowedExtension($filepath) {
		$filepath = strtolower($filepath);

		foreach (self::$disallowed as $ext) {
			if (str_ends_with($filepath, $ext)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Tries to serve static content from filesystem\
	 * WARNING: Please note that some web servers handle byte range requests automatically and therefore prevents this from working with audio/video files!
	 * @param null|string $filepath Path to file or directory to serve (null = look at the request URI to determine what content should be served)
	 * @param null|string $contentType MIME content type of this resource (null = automatically get the file's content type)
	 * @param bool $errorpages If set to true will serve errorpages automatically when file isn't found or there is other issue (when enabled this method will always return true)
	 * @param null|string $directory Current directory prefix (defaults to "." if null)
	 * @return bool Returns true if file was succesfully served
	 */
	public static function serve($filepath = null, $contentType = null, $errorpages = true, $directory = null) {
		if ($directory === null) $directory = ".";
		if ($filepath === null) $filepath = Utils::toLocalPath($_SERVER["REQUEST_URI"]);
		$filepath = Utils::pathCombine($directory, $filepath);

		if (is_file($filepath)) {
			if (!self::isAllowedExtension($filepath)) {
				if ($errorpages) {
					self::setStatus(404); // page or resource not found
					include_once "errorpages/404_page.php";
					return true;
				}
				return false;
			}
			
			if ($contentType === null) { header('Content-Type: '.mime_content_type($filepath)); }

			if (isset($_SERVER['HTTP_RANGE'])) {
				// try to serve partial content
				if (preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches) === 1) {
					$filesize = filesize($filepath);
					$rangeStart = intval($matches[1]);
					$rangeEnd = ($filesize - $rangeStart) - 1;
					if (isset($matches[2])) { $rangeEnd = intval($matches[2]); }
					$length = ($rangeEnd + 1) - $rangeStart;

					if ($length < 0 || $length > $filesize || $rangeStart < 0 || $rangeEnd > $filesize) {
						self::setStatus(416); // range not satisfiable
						return;
					}

					if (($handle = fopen($filepath, 'rb')) !== false) {
						self::setStatus(206); // partial content
						header("Content-Length: $length");
						header("Content-Range: bytes $rangeStart-$rangeEnd/$filesize");

						fseek($handle, $rangeStart, SEEK_SET);
						print(fread($handle, $length));

						return true;
					} else {
						self::setStatus(500); // internal server error
						if ($errorpages) return true;
					}
				}
				return false;
			}

			header('Accept-Ranges: bytes');

			if (!readfile($filepath)) {
				if ($errorpages) {
					self::setStatus(500); // internal server error
					include_once "errorpages/500_internal.php";
					return true;
				}
				return false;
			}
		} else if (is_dir($filepath)) {
			$s = (str_ends_with($filepath, DIRECTORY_SEPARATOR) ? '' : DIRECTORY_SEPARATOR);

			foreach (scandir($filepath) as $entry) {
				$fullpath = $filepath.$s.$entry;
				if (in_array($entry, array(".", "..")) || is_link($fullpath)) continue;

				if (preg_match(self::$index, $entry) === 1) {
					include_once $fullpath;
					return true;
				}
			}
		}

		if ($errorpages) {
			self::setStatus(404); // page or resource not found
			include_once "errorpages/404_page.php";
			return true;
		}

		return false;
	}

	/**
	 * Set HTTP response code
	 * @param integer $code Response status code
	 */
	public static function setStatus($code) {
		http_response_code($code);
	}

	/**
	 * Try to route this request to appropriate handler
	 */
	public static function accept() {
		$path = strtok($_SERVER["REQUEST_URI"], '?');

		for ($i = count(self::$routes) - 1; $i > -1; $i--) {
			$route = &self::$routes[$i];
			self::$continue = false;
			
			if (($route->method === '*' || $_SERVER['REQUEST_METHOD'] === $route->method) && ($route->host === null || $_SERVER['HTTP_HOST'] === $route->host)) {
				$_path = $path;
				
				if ($route->base !== null) {
					if (str_starts_with($path, $route->base)) {
						$_path = substr($_path, strlen($route->base));
					} else { 
						continue; 
					}
				}
				
				$matches = null;
				if (preg_match($route->path, $_path, $matches) === 1) {
					array_shift($matches);
					for ($j = 0; $j < count($matches); $j++) {
						$matches[$j] = urldecode($matches[$j]);
					}
					
					if (is_callable($route->action)) {
						($route->action)(...$matches);
					} else if (is_array($route->action)) {
						call_user_func([$route->action[0], $route->action[1]], ...$matches);
					}

					if (!self::$continue) return;
				}
			}
		}

		throw new NoRouteException('Route not found!');
	}
}

class Route {
	/**
	 * @var string HTTP request method
	 */
	public $method = null;

	/**
	 * @var string Regular expression to match query path
	 */
	public $path = null;

	/**
	 * @var callable Handler for this route
	 */
	public $action = null;

	/**
	 * @var string|null Route is valid for specific hostname only
	 */
	public $host = null;

	/**
	 * @var string|null Base directory for this route
	 */
	public $base = null;

	/**
	 * @param string $method HTTP request method
	 * @param string $path Regular expression to match query path
	 * @param string $action Handler for this route
	 */
	function __construct($method, $path, $action) {
		if ($method === null) $method = '*';
		if ($path === null) $path = '/^\/$/';

		$this->method = $method;
		$this->path = $path;
		$this->action = $action;
	}
}

class NoRouteException extends Exception {}