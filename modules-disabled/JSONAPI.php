<?php

class JSONAPI {
    /**
     * @var object|null Request payload
     */
    public $request = null;

    /**
     * @var object Response payload
     */
    public $response = [];

    /**
     * Send successful response and stop script execution
     */
    public function send() {
        echo json_encode([
            "success" => true,
            "response" => $this->response
        ]);
        exit;
    }

    /**
     * Send error response and stop script execution
     * @param mixed|null $error Error description
     */
    public function error($error = null) {
        $res = ["success" => false];
        if ($error !== null) { 
            $res["error"] = $error; 
        }
        echo json_encode($res);
        exit;
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
            "pattern" => null   // regex pattern
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
     * Determine if all values in input array have same value
     * @param array $array
     * @param string $type 
     */
    private static function arrayType(&$array, $type) {
        if ($type === "object") {
            foreach ($array as &$a) { if (!self::isAssoc($a)) return false; }
        }
        else if ($type === "array") {
            foreach ($array as &$a) { if (!is_array($a)) return false; }
        }
        else if ($type === "string") {
            foreach ($array as &$a) { if (!is_string($a)) return false; }
        }
        else if ($type === "number") {
            foreach ($array as &$a) { if (!self::isNumber($a)) return false; }
        }
        else if ($type === "bool") {
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

        if (self::isAssoc($prop)) {
            /**
             * Associative array with keys
             */
            if ($options["type"] !== "object") { $error = true; return; }

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
            $type = explode(":", $options["type"], 2);
            if ($type[0] !== "array") { $error = true; return; }

            /**
             * Check array element types?
             */
            if (count($type) === 2 && !self::arrayType($prop, $type[1])) {
                $error = true; return;
            }

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

        }
        else if (is_string($prop)) {
            /**
             * Property value is a string
             */
            if ($options["type"] !== "string") { $error = true; return; }

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
            if ($options["type"] !== "number") { $error = true; return; }

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
            if ($options["type"] !== "bool") { $error = true; return; }
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
     * @return JSONAPI
     */
    static function accept($template = null) {
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

            $error = null;
            if (!self::validate($api->request, $template)) {
                $api->error($error);
            }
        }

        return $api;
    }
}