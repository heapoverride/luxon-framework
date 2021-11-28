<?php

class JSONAPI {
    /**
     * @var object|null Request payload
     */
    public $request = null;

    /**
     * @var object|null Response payload
     */
    public $response = null;

    /**
     * Send response and stop script execution
     * @param int $status_code Response status code (see: https://restfulapi.net/http-status-codes/)
     * @param bool $is_error Is error response
     * @param mixed $error_desc Error description
     * @param bool $is_empty Is empty response
     */
    private function _send($status_code = 200, $is_error = false, $error_desc = null, $is_empty = false) {
        http_response_code($status_code);
        if ($is_empty) exit;

        $res = [ "success" => true ];

        if ($this->response !== null) { $res["response"] = $this->response; }

        if ($is_error) {
            $res["success"] = false;

            if ($error_desc !== null) {
                $res["error"] = $error_desc;
            }
        }

        echo json_encode($res);
        exit;
    }

    /**
     * Send succesful response and stop script execution
     * - 200 - OK
     * @param int $status_code Optional status code
     */
    public function sendResponse($status_code = 200) {
        $this->_send($status_code);
    }

    /**
     * Send error response and stop script execution
     * - 500 - INTERNAL SERVER ERROR
     * @param mixed|null $error_desc Error description
     * @param int $status_code Optional status code
     */
    public function sendError($error_desc = null, $status_code = 500) {
        $this->_send($status_code, true, $error_desc);
    }

    /**
     * Send empty response and stop script execution
     * - 204 - NO CONTENT
     */
    public function sendEmpty() {
        $this->_send(204, false, null, true);
    }

    /**
     * Send unauthorized response and stop script execution
     * - 401 - UNAUTHORIZED
     * @param mixed|null $error_desc Error description
     */
    public function sendUnauthorized($error_desc = null) {
        $this->_send(401, true, $error_desc);
    }

    /**
     * Get default options
     * @param array [$options]
     * @return array
     */
    private static function getOptions($options = null) {
        $defaults = [
            "type" => "object", // object, array:[type], string, number, bool
            "length" => null,   // [min, max]
            "range" => null,    // [min, max]
            "pattern" => null,  // regex pattern
            "filter" => null    // FILTER_VALIDATE_EMAIL, ...
        ];

        /**
         * Override default options
         */
        if ($options !== null) {
            foreach ($options as $key => $value) {
                if (str_starts_with($key, ":")) { 
                    $defaults[substr($key, 1)] = $value; 
                }
            }
        }

        return $defaults;
    }

    /**
     * Determine if input array is associative array
     * @param array $array
     * @return bool
     */
    private static function isAssoc(&$array) {
        if (!is_array($array) || count($array) === 0) return false;
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Determine if input is a number
     * @param mixed $input
     * @return bool
     */
    private static function isNumber($input) {
        return !is_string($input) && is_numeric($input);
    }

    /**
     * @param object $object
     */
    private static function _recursive_get_object_vars(&$object, &$array) {
        if (is_object($object)) {
            foreach (get_object_vars($object) as $key => $value) {
                self::_recursive_get_object_vars($value, $array[$key]);
            }
        } else {
            $array = $object;
        }
    }

    /**
     * @param object $object
     * @return array
     */
    private static function recursive_get_object_vars($object) {
        $array = [];
        self::_recursive_get_object_vars($object, $array);

        return $array;
    }

    /**
     * Determine if all values in input array have the same value
     * @param array $array
     * @param string $_type 
     */
    private static function arrayType(&$array, $_type) {
        $type = explode(":", $_type, 2);

        if ($type[0] === "object") {
            foreach ($array as &$a) { if (!self::isAssoc($a)) return false; }
        }
        else if ($type[0] === "array") {
            foreach ($array as &$a) { if (!is_array($a)) return false; }
        }
        else if ($type[0] === "string") {
            if (count($type) === 2) {
                if ($type[1] === "email") { 
                    foreach ($array as &$a) { if (!filter_var($a, FILTER_VALIDATE_EMAIL)) return false; } 
                }
                else if ($type[1] === "domain") {
                    foreach ($array as &$a) { if (!filter_var($a, FILTER_VALIDATE_DOMAIN)) return false; } 
                }
                else if ($type[1] === "ip") {
                    foreach ($array as &$a) { if (!filter_var($a, FILTER_VALIDATE_IP)) return false; } 
                }
                else if ($type[1] === "mac") {
                    foreach ($array as &$a) { if (!filter_var($a, FILTER_VALIDATE_MAC)) return false; } 
                }
                else if ($type[1] === "url") {
                    foreach ($array as &$a) { if (!filter_var($a, FILTER_VALIDATE_URL)) return false; } 
                }
            }
            else {
                foreach ($array as &$a) { if (!is_string($a)) return false; }
            }
        }
        else if ($type[0] === "number") {
            foreach ($array as &$a) { if (!self::isNumber($a)) return false; }
        }
        else if ($type[0] === "bool") {
            foreach ($array as &$a) { if (!is_bool($a)) return false; }
        }

        return true;
    }

    /**
     * @param mixed $prop
     * @param array $template
     * @param bool $error
     */
    private static function _validate(&$prop, &$template, &$error) {
        $options = self::getOptions($template);
        $type = explode(":", $options["type"], 2);

        if (self::isAssoc($prop)) {
            /**
             * Associative array with keys
             */
            if ($type[0] !== "object") { $error = true; return; }

            foreach ($template as $key => $value) {
                if (str_starts_with($key, ":")) continue;
                
                /**
                 * Recursively validate properties
                 */
                if (!isset($prop[$key]) || !isset($template[$key])) { $error = true; return; }
                self::_validate($prop[$key], $value, $error);
            }

            return;
        }

        if (is_array($prop)) {
            /**
             * Regular array
             */
            if ($type[0] !== "array") { $error = true; return; }

            /**
             * Check array length?
             */
            if ($options["length"] !== null) {
                $count = count($prop);

                if (self::isNumber($options["length"])) {
                    if ($count !== $options["length"]) { $error = true; return; }
                }
                else if (count($options["length"]) === 2 && ($count < $options["length"][0] || $count > $options["length"][1])) {
                    $error = true; return;
                };
            }

            /**
             * Check array element types?
             */
            if (count($type) === 2 && !self::arrayType($prop, $type[1])) {
                $error = true; return;
            }
        }
        else if (is_string($prop)) {
            /**
             * Property value is a string
             */
            if ($type[0] !== "string") { $error = true; return; }

            /**
             * Check string length?
             */
            if ($options["length"] !== null) {
                $strlen = strlen($prop);

                if (self::isNumber($options["length"]) && $strlen !== $options["length"]) {
                    $error = true; return;
                }
                else if (count($options["length"]) === 2 && ($strlen < $options["length"][0] || $strlen > $options["length"][1])) {
                    $error = true; return;
                }
            }

            /**
             * Check special string types?
             */
            if (count($type) === 2) {
                if ($type[1] === "email" && !filter_var($prop, FILTER_VALIDATE_EMAIL)) {
                    $error = true; return;
                }
                else if ($type[1] === "domain" && !filter_var($prop, FILTER_VALIDATE_DOMAIN)) {
                    $error = true; return;
                }
                else if ($type[1] === "ip" && !filter_var($prop, FILTER_VALIDATE_IP)) {
                    $error = true; return;
                }
                else if ($type[1] === "mac" && !filter_var($prop, FILTER_VALIDATE_MAC)) {
                    $error = true; return;
                }
                else if ($type[1] === "url" && !filter_var($prop, FILTER_VALIDATE_URL)) {
                    $error = true; return;
                }
            }

            /**
             * Check if string matches a given pattern?
             */
            if ($options["pattern"] !== null && preg_match($options["pattern"], $prop) !== 1) {
                $error = true; return;
            }
        }
        else if (self::isNumber($prop)) {
            /**
             * Property value is a number
             */
            if ($type[0] !== "number") { $error = true; return; }

            /**
             * Check number range?
             */
            if ($options["range"] !== null) {
                if (count($options["range"]) === 2 && ($prop < $options["range"][0] || $prop > $options["range"][1])) {
                    $error = true; return;
                }
            }
        }
        else if (is_bool($prop)) {
            /**
             * Property value is a boolean
             */
            if ($type[0] !== "bool") { $error = true; return; }
        }
    }

    /**
     * @param object $request
     * @param array $template
     */
    private static function validate(&$request, &$template) {
        if ($template === null) return true;

        $error = false;
        $data = self::recursive_get_object_vars($request);
        self::_validate($data, $template, $error);

        return !$error;
    }

    /**
     * Accept and validate JSON API request
     * @param array|null $template JSON payload template or null if no request payload is expected
     * @param callable|null $authenticate Authentication callback that should return `true` if request is authenticated
     * @return JSONAPI
     */
    static function accept($template = null, $authenticate = null) {
        /**
         * Set response content type
         */
        header("Content-Type: application/json");

        /**
         * Try to get request content type
         */
        $type = $_SERVER["HTTP_CONTENT_TYPE"] ?? $_SERVER["CONTENT_TYPE"];

        /**
         * Try to get request body (JSON object)
         */
        $data = null;
        if ($type !== null && $type == "application/json") { 
            $data = json_decode(file_get_contents("php://input")); 
        }

        $api = new JSONAPI();
        if ($data !== null) {
            $api->request = $data;

            if (!self::validate($api->request, $template)) {
                $api->_send(400, true, "Bad Request");
            }

            if ($authenticate !== null) {
                $authenticated = call_user_func_array($authenticate, [ $api ]);
                if (!$authenticated) { $api->sendUnauthorized("Unauthorized"); }
            }
        }

        return $api;
    }
}