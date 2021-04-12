<?php

    class Utils {
        /**
         * For PHP versions older than 8.0 - Check whether string starts with substring
         */
        function str_starts_with($haystack, $needle) {
            $length = strlen($needle);
            if (!$length) return true;
            return substr($haystack, 0, $length) === $needle;
        }

        /**
         * For PHP versions older than 8.0 - Check whether string ends with substring
         */
        function str_ends_with($haystack, $needle) {
            $length = strlen($needle);
            if (!$length) return true;
            return substr($haystack, -$length) === $needle;
        }
    }

    /**
     * Load view by name (partials header and footer will be loaded as well)
     * @param string $viewName Name of the view to be loaded from "./views/"
     */
    function view($viewName) {
        include_once "views/partials/header.php";
        include_once "views/$viewName.php";
        include_once "views/partials/footer.php";
    }

?>