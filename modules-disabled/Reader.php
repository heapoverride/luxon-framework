<?php

    /**
     * A small string reader utility (I hope this doesn't exist in PHP by default...)\
     * written by <github.com/UnrealSecurity>
     */
    class Reader {
        private $pos = 0;
        private $str = null;
        
        function __construct(&$str) {
            if ($str === null || !is_string($str)) throw new Exception('$str must be of type string');
            $this->str = $str;
        }
        
        /**
         * Try to read $length characters and advance position by the amount of characters read
         * @param integer $length Number of characters to read
         * @return string|null Returns max $length characters or null if can't read any more characters
         */
        function read($length = 1) {
            $slen = strlen($this->str);
            $avail = $slen - $this->pos;
            
            if ($length < 1 || $avail < 1) return null;
            if ($avail < $length) $length = $avail;
            
            $data = substr($this->str, $this->pos, $length);
            $this->pos += $length;

            return $data;
        }

        /**
         * Seek to absolute or relative position within the "stream"
         * @param integer $position Position to seek to (can be negative when $offset is set to true)
         * @param boolean $offset When this option is set to true the new position is added to the current position
         * @return boolean Returns true on success and false if the seek is impossible
         */
        public function seek($position, $offset = false) {
            $pos = $this->pos;

            if ($offset) {
                $pos += $position;
            } else {
                $pos = $position;
            }

            if ($pos >= 0 && $pos < strlen($this->str)) {
                $this->pos = $pos;
                return true;
            }

            return false;
        }
    }

?>