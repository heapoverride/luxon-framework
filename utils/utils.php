<?php

class Utils {
    public static function removeDotDots($filepath) {
        while (true) {
            $lengthBefore = strlen($filepath);
            $filepath = str_replace('..', '.', $filepath);
            if ($lengthBefore === strlen($filepath)) break;
        }
        
        return $filepath;
    }
    
    public static function toLocalPath($uri) {
        $uri = self::removeDotDots(strtok($uri, '?'));
        if (strlen($uri) > 0 && $uri[0] === "/") {
            $uri = substr($uri, 1);
        }
        return $uri;
    }

    public static function pathCombine(...$paths) {
        for ($i=0; $i<count($paths); $i++) {
            if ($i !== 0) { $paths[$i] = self::removeDotDots($paths[$i]); }
            
            if ($paths[$i] === "/") {
                $paths[$i] = "";
            } else {
                if (str_starts_with($paths[$i], DIRECTORY_SEPARATOR) && $i !== 0) {
                    $paths[$i] = substr($paths[$i], 1);
                }
                if (str_ends_with($paths[$i], DIRECTORY_SEPARATOR)) {
                    $paths[$i] = substr($paths[$i], 0, strlen($paths[$i])-1);
                }
            }
        }

        return implode(DIRECTORY_SEPARATOR, $paths);
    }
}

/**
 * Load view by name (partials header and footer will be loaded as well)
 * @param string $viewName Name of the view to be loaded from "./views/"
 */
function view($viewName) {
    if (preg_match("/^[A-z0-9\/\-\._]*$/", $viewName) !== 1) {
        throw new Exception('Disallowed characters in view name');
    }
    
    include_once "views/partials/header.php";
    include_once "views/$viewName.php";
    include_once "views/partials/footer.php";
}