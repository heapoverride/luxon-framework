<?php

    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

    if (!isset($_SESSION['LUXON_MESSAGES'])) {
        $_SESSION['LUXON_MESSAGES'] = [];
    }

    class Messages {
        /**
         * Return number of messages in queue
         * @return integer
         */
        public static function count() {
            return count($_SESSION['LUXON_MESSAGES']);
        }

        /**
         * Push message to queue
         * @param string $str Message to push
         */
        public static function push($str) {
            $_SESSION['LUXON_MESSAGES'][] = $str;
        }

        /**
         * Pop one message from top of the queue
         * @return string
         */
        public static function pop() {
            return array_shift($_SESSION['LUXON_MESSAGES']);
        }

        /**
         * Return array of all queued messages and clear queue
         * @return array
         */
        public static function flush() {
            $all = $_SESSION['LUXON_MESSAGES'];
            $_SESSION['LUXON_MESSAGES'] = [];
            return $all;
        }

        /**
         * Format message (HTML safe)
         * @param string $template Message template
         * @param array $array Associative array (key-value pairs)
         * @return string
         */
        public static function format($template, $array) {
            $arr = str_split($template);
            $tmp = [];
            $res = [];
            $sta = 0;

            for ($i=0; $i<count($arr); $i++) {
                if ($sta == 0) {
                    if ($arr[$i] == '\\' && $i < count($arr)-1) {
                        switch ($arr[$i+1]) {
                            case '{':
                                $res[] = '{'; $i++;
                                break;
                            case '}':
                                $res[] = '}'; $i++;
                                break;
                        }
                    } else if ($arr[$i] == '{') {
                        $sta = 1;
                    } else {
                        $res[] = $arr[$i];
                    }
                } else if ($sta == 1) {
                    if ($arr[$i] == '}') {
                        $key = implode('', $tmp);
                        if (array_key_exists($key, $array)) {
                            $res[] = htmlspecialchars(strval($array[$key]));
                        } else {
                            $res[] = 'undefined';
                        }
                        $tmp = []; $sta = 0;
                    } else {
                        $tmp[] = $arr[$i];
                    }
                }
            }

            return implode('', $res);
        }
    }

?>