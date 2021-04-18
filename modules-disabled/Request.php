<?php

    /**
     * A library for making HTTP requests from PHP with cURL\
     * written by <github.com/UnrealSecurity>
     */
    class Request {
        private static function send($method, $url, $headers = null, $body = null, $json = false) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10000);
            if ($json) curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

            if ($headers !== null) {
                $_headers = [];
                foreach ($headers as $name => $value) {
                    $matches = null;
                    $_headers[] = "$name: $value";
                }

                curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
            }
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_URL, $url);

            if ($method === 'HEAD') {
                curl_setopt($ch, CURLOPT_NOBODY, 1);
            } else if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            return new Response($ch);
        }

        /**
         * The HTTP GET method requests a representation of the specified resource. Requests using GET should only be used to request data (they shouldn't include data).
         * @param string $url Request URI
         * @param string[] $headers Request headers in an associative array
         * @return Response
         */
        public static function get($url, $headers = null) {
            return self::send('GET', $url, $headers);
        }

        /**
         * The HTTP POST method sends data to the server. The type of the body of the request is indicated by the Content-Type header.
         * @param string $url Request URI
         * @param string|mixed $body Request body
         * @param string[] $headers Request headers in an associative array
         * @param boolean $json If set to true will encode request body to JSON and set the appropriate content type header (application/json)
         * @return Response
         */
        public static function post($url, $body, $headers = null, $json = false) {
            if ($json) $body = json_encode($body);
            if (!is_string($body)) $body = strval($body);
            return self::send('POST', $url, $headers, $body, $json);
        }

        /**
         * The HTTP HEAD method requests the headers that would be returned if the HEAD request's URL was instead requested with the HTTP GET method.
         * @param string $url Request URI
         * @param string[] $headers Request headers in an associative array
         * @return Response
         */
        public static function head($url, $headers = null) {
            return self::send('HEAD', $url, $headers);
        }

        /**
         * The HTTP PUT request method creates a new resource or replaces a representation of the target resource with the request payload.
         * @param string $url Request URI
         * @param string|mixed $body Request body
         * @param string[] $headers Request headers in an associative array
         * @param boolean $json If set to true will encode request body to JSON and set the appropriate content type header (application/json)
         * @return Response
         */
        public static function put($url, $body, $headers = null, $json = false) {
            if ($json) $body = json_encode($body);
            if (!is_string($body)) $body = strval($body);
            return self::send('PUT', $url, $headers, $body, $json);
        }

        /**
         * The HTTP DELETE request method deletes the specified resource.
         * @param string $url Request URI
         * @param string[] $headers Request headers in an associative array
         * @return Response
         */
        public static function delete($url, $headers = null) {
            return self::send('DELETE', $url, $headers);
        }

        /**
         * The HTTP OPTIONS method requests permitted communication options for a given URL or server. A client can specify a URL with this method, or an asterisk (*) to refer to the entire server.
         * @param string $url Request URI
         * @param string[] $headers Request headers in an associative array
         * @return Response
         */
        public static function options($url, $headers = null) {
            return self::send('OPTIONS', $url, $headers);
        }

        /**
         * The HTTP CONNECT method starts two-way communications with the requested resource. It can be used to open a tunnel.
         * @param string $url Request URI
         * @param string[] $headers Request headers in an associative array
         * @return Response
         */
        public static function connect($uri, $headers = null) {
            return self::send('CONNECT', $uri, $headers);
        }

        /**
         * The HTTP TRACE method performs a message loop-back test along the path to the target resource, providing a useful debugging mechanism.
         * @param string $url Request URI
         * @param string[] $headers Request headers in an associative array
         * @return Response
         */
        public static function trace($uri, $headers = null) {
            return self::send('TRACE', $uri, $headers);
        }

        /**
         * The HTTP PATCH request method applies partial modifications to a resource.
         * @param string $url Request URI
         * @param string|mixed $body Request body
         * @param string[] $headers Request headers in an associative array
         * @param boolean $json If set to true will encode request body to JSON and set the appropriate content type header (application/json)
         * @return Response
         */
        public static function patch($uri, $url, $body, $headers = null, $json = false) {
            if ($json) $body = json_encode($body);
            if (!is_string($body)) $body = strval($body);
            return self::send('PATCH', $url, $headers, $body, $json);
        }
    }

    /**
     * HTTP response object
     */
    class Response {
        /**
         * Response URL
         * @return string
         */
        public $url = null;
        /**
         * Response HTTP status code
         * @return integer
         */
        public $code = 0;
        /**
         * Response content type
         * @return string
         */
        public $type = null;
        /**
         * Response content length
         * @return integer
         */
        public $length = 0;
        /**
         * Response headers in an associative array
         * @return array[]
         */
        public $headers = [];
        /**
         * Response body
         * @return string
         */
        public $body = null;
        
        function __construct($ch) {
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(&$this, 'header_function'));
            $this->body = curl_exec($ch);
            $this->url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $this->code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $this->type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $this->length = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            curl_close($ch);
        }

        private function header_function($ch, $header) {
            $matches = null;
            if (preg_match("/([a-z0-9\-]*): (.*)/i", $header, $matches) === 1) {
                $name = $matches[1];
                $value = $matches[2];
                $this->headers[$name] = $value;
            }

            return strlen($header);
        }
    }

?>