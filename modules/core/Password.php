<?php

    class Password {
        private static $salt_length = 16;

        private static function _hash($password, $salt) {
            if (strlen(APP_SECRET) === 0) throw new Exception("APP_SECRET must not be empty");
            return bin2hex($salt) . hash_hmac("sha256", implode("\0", [APP_SECRET, $password]), $salt);
        }

        /**
         * Hash a plaintext password
         * @param string $password Plaintext password
         */
        public static function hash($password) {
            return self::_hash($password, random_bytes(self::$salt_length));
        }

        /**
         * Verify password hash
         * @param string $password Plaintext password user is authenticating with
         * @param string $hash Password hash that was originally created for this user
         */
        public static function verify($password, $hash) {
            $salt = substr($hash, 0, self::$salt_length * 2);
            return self::_hash($password, hex2bin($salt)) === $hash;
        }
    }

?>