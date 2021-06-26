<?php

class Logger {
    private $fp;
    private $format = "Y-m-d H:i:s";

    function __construct($path)
    {
        $this->fp = fopen($path, "a");
        if (!$this->fp) throw new Exception("Failed to open file");
    }

    function write($text) {
        fwrite($this->fp, date($this->format)."  ".$text."\n");
    }
}