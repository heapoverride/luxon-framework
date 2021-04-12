<?php

    class Password {
        /**
         * Gets random base64 encoded salt bytes
         */
        public static function salt() {
            return base64_encode(random_bytes(16));
        }

        /**
         * Gets strong password hash for storing login passwords
         * @param string $password Plaintext password
         * @param string $salt Base64 encoded salt for this user
         */
        public static function hash($password, $salt) {
            if (strlen(APP_SECRET) === 0) throw new Exception('APP_SECRET must not be empty');
            return hash_hmac('sha256', implode("\0", [APP_SECRET, $password]), base64_decode($salt));
        }

        /**
         * Test if given plaintext $password\
         * hashed with APP_SECRET and user's unique $salt stored in database\
         * results in expected $hash value
         * @param string $password Plaintext password user is authenticating with
         * @param string $salt Base64 encoded salt for this user
         * @param string $hash Password hash that was originally created for this user
         */
        public static function test($password, $salt, $hash) {
            return self::hash($password, $salt) === $hash;
        }
    }

?>