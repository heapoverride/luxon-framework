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

        private static function ends_with($haystack, $needle) {
            $length = strlen($needle);
            if (!$length) return true;
            return substr($haystack, -$length) === $needle;
        }

        private static function isAllowedExtension($filepath) {
            $filepath = strtolower($filepath);

            foreach (self::$disallowed as $ext) {
                if (self::ends_with($filepath, $ext)) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Tries to serve static content from filesystem\
         * WARNING: Please note that some webservers handle byte range requests automatically and therefore prevents this from working with audio/video files!
         * @param null|string $filepath Path to file or directory to serve (null = look at request URI to determine content to be served)
         * @param null|string $contentType MIME content type of this resource
         * @return bool Returns true if file was succesfully served
         */
        public static function serve($filepath = null, $contentType = null) {
            if ($filepath === null) return self::serve(self::removeDotDots('.'.$_SERVER['REQUEST_URI']));

            if (is_file($filepath)) {
                if (!self::isAllowedExtension($filepath)) return false;
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

                        if (($handle = fopen($filepath, 'r')) !== false) {
                            self::setStatus(206); // partial content
                            header("Content-Length: $length");
                            header("Content-Range: bytes $rangeStart-$rangeEnd/$filesize");

                            fseek($handle, $rangeStart, SEEK_SET);
                            print(fread($handle, $length));

                            return true;
                        } else {
                            self::setStatus(500);
                        }
                    }
                    return false;
                }

                header('Accept-Ranges: bytes');
                readfile($filepath);
            } else if (is_dir($filepath)) {
                foreach (scandir($filepath) as $entry) {
                    $fullpath = $filepath.DIRECTORY_SEPARATOR.$entry;
                    if (in_array($entry, array(".", "..")) || is_link($fullpath)) continue;

                    if (preg_match(self::$index, $entry) === 1) {
                        include_once $fullpath;
                        return true;
                    }
                }
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

            for ($i = count(self::$routes)-1; $i > -1; $i--) {
                $route = self::$routes[$i];
                
                if ($route->method === '*' || $_SERVER['REQUEST_METHOD'] === $route->method) {
                    $matches = null;
                    if (preg_match($route->path, $path, $matches) === 1) {
                        array_shift($matches);
                        
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