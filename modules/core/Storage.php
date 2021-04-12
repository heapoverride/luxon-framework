<?php

    class Storage {
        private static $storage = array();

        public static function &get($name) {
            return self::$storage[$name];
        }

        public static function set($name, $value) {
            self::$storage[$name] = $value;
        }

        public static function unset($name) {
            unset(self::$storage[$name]);
        }

        public static function has($name) {
            return array_key_exists($name, self::$storage);
        }
    }

?>