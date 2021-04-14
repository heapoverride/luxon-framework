<?php

    class Router {
        private static $routes = [];
        private static $index = "/^index\.(php|html)$/i";
        private static $disallowed = ['.php', '.sql'];
        
        /**
         * Add new route
         * @param string $method HTTP method or * to match any
         * @param string $path Regular expression to match path (captured groups are passed to handler)
         * @param function $action Handler that handles this request
         */
        public static function route($method, $path, $action) {
            self::$routes[] = new Route($method, $path, $action);
        }

        /**
         * Remove dot-dots (..) from filepath
         * @param string $filepath Path to file or directory to be sanitized
         */
        private static function removeDotDots($filepath) {
            while (true) {
                $lengthBefore = strlen($filepath);
                $filepath = str_replace('..', '.', $filepath);
                if ($lengthBefore === strlen($filepath)) break;
            }
            
            return $filepath;
        }

        private static function starts($haystack, $needle) {
            $length = strlen($needle);
            if (!$length) return true;
            return substr($haystack, 0, $length) === $needle;
        }

        private static function ends($haystack, $needle) {
            $length = strlen($needle);
            if (!$length) return true;
            return substr($haystack, -$length) === $needle;
        }

        private static function isAllowedExtension($filepath) {
            $filepath = strtolower($filepath);

            foreach (self::$disallowed as $ext) {
                if (self::ends($filepath, $ext)) {
                    return false;
                }
            }

            return true;
        }

        private static function toLocalPath($uri) {
            $uri = self::removeDotDots(strtok($uri, '?'));
            if (strlen($uri) > 0 && $uri[0] === "/") {
                $uri = substr($uri, 1);
            }
            return $uri;
        }

        private static function pathCombine(...$paths) {
            for ($i=0; $i<count($paths); $i++) {
                if ($paths[$i] === "/") {
                    $paths[$i] = "";
                } else {
                    if (self::starts($paths[$i], DIRECTORY_SEPARATOR) && $i !== 0) {
                        $paths[$i] = substr($paths[$i], 1);
                    }
                    if (self::ends($paths[$i], DIRECTORY_SEPARATOR)) {
                        $paths[$i] = substr($paths[$i], 0, strlen($paths[$i])-1);
                    }
                }
            }

            return implode(DIRECTORY_SEPARATOR, $paths);
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
            if ($filepath === null) $filepath = self::toLocalPath($_SERVER["REQUEST_URI"]);
            $filepath = self::pathCombine($directory, $filepath);

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
                $s = (self::ends($filepath, DIRECTORY_SEPARATOR) ? '' : DIRECTORY_SEPARATOR);

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
            $routes = self::$routes;
            array_splice(self::$routes, 0);

            for ($i = count($routes)-1; $i > -1; $i--) {
                $route = $routes[$i];
                
                if ($route->method === '*' || $_SERVER['REQUEST_METHOD'] === $route->method) {
                    $matches = null;
                    if (preg_match($route->path, $path, $matches) === 1) {
                        array_shift($matches);
                        for ($j=0; $j<count($matches); $i++) {
                            $matches[$i] = urldecode($matches[$i]);
                        }
                        
                        if (is_callable($route->action)) {
                            ($route->action)(...$matches);
                        } else if (is_array($route->action)) {
                            call_user_func([$route->action[0], $route->action[1]], ...$matches);
                        }

                        return;
                    }
                }
            }

            throw new NoRouteException('Route not found!');
        }
    }

    class Route {
        public $method = null;
        public $path = null;
        public $action = null;

        function __construct($method, $path, $action) {
            if (!is_string($method)) $method = '*';
            if (!is_string($path)) $path = '/^\/$/';

            $this->method = $method;
            $this->path = $path;
            $this->action = $action;
        }
    }

    class NoRouteException extends Exception {}

?>