<?php

class FileUpload {
    public $name;
    public $length;
    public $path;
    public $mimetype;
    public $extension;
    public $error;
    protected $temp;

    /**
     * Move file and return `true` on success and `false` on error
     * @return bool
     */
    function move($destination) {
        if (str_ends_with($destination, "/")) { $destination = Utils::pathCombine($destination, $this->name); }

        if ($this->temp !== null) {
            if (move_uploaded_file($this->temp, $destination)) {
                $this->path = $destination;
                return true;
            }
        } else {
            if (rename($this->path, $destination)) {
                $this->path = $destination;
                return true;
            }
        }

        return false;
    }

    /**
     * Get default options
     * @param array [$options]
     * @return array
     */
    private static function getOptions($options = null) {
        $defaults = [
            "minfiles" => 1,
            "maxfiles" => 1,
            "minlength" => 0,
            "maxlength" => 2000000,
            "mimetypes" => [],
            "extensions" => []
        ];

        /**
         * Override default options
         */
        if ($options !== null) {
            foreach ($options as $key => $value) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    /**
     * Get extension from filename
     * @param string $name
     * @return string
     */
    private static function getExtension($name) {
        $i = strripos($name, ".");
        if ($i !== false) return substr($name, $i);
        return "";
    }

    /**
     * Accept uploaded files and return `true` on success and `false` on error
     * @param FileUpload[] $files Array to hold accepted files
     * @param array [$options] Options
     * @return bool
     */
    static function accept(&$files, $options = null) {
        $options = self::getOptions($options);
        $tempFiles = [];

        foreach ($_FILES as $key => $file) {
            $name = $file["name"];
            $type = $file["type"];
            $temp = $file["tmp_name"];
            $error = $file["error"];
            $length = $file["size"];

            if (is_array($name)) {
                for ($i = 0; $i < count($name); $i++) {
                    $file = new FileUpload();
                    $file->name = $name[$i];
                    $file->length = $length[$i];
                    $file->mimetype = $type[$i];
                    $file->extension = self::getExtension($file->name);
                    $file->temp = $temp[$i];
                    $file->error = $error[$i];

                    $tempFiles[] = $file;
                }
            } else {
                $file = new FileUpload();
                $file->name = $name;
                $file->length = $length;
                $file->mimetype = $type;
                $file->extension = self::getExtension($file->name);
                $file->temp = $temp;
                $file->error = $error;

                $tempFiles[] = $file;
            }
        }

        $fileCount = count($tempFiles);

        /**
         * Make sure that accepted amount of files were uploaded
         */
        if ($fileCount === 0 || $fileCount < $options["minfiles"] || $fileCount > $options["maxfiles"]) {
            return false;
        }

        foreach ($tempFiles as $file) {
            /**
             * Make sure that file's mime type is one of the accepted mime types
             */
            if (count($options["mimetypes"]) > 0 && !in_array($file->mimetype, $options["mimetypes"], true)) {
                return false;
            }

            /**
             * Make sure that file's extension is one of the accepted extensions
             */
            if (count($options["extensions"]) > 0 && !in_array($file->extension, $options["extensions"], true)) {
                return false;
            }

            /**
             * Make sure that file's size is within accepted range
             */
            if ($file->length < $options["minlength"] || $file->length > $options["maxlength"]) { 
                return false;
            }

            /**
             * Make sure that the file name doesn't contain any illegal characters
             */
            if (preg_match("/[\<\>\:\"\/\\\|\?\*]/", $file->name) !== 0) {
                return false;
            }
        }

        array_push($files, ...$tempFiles);
        return true;
    }
}