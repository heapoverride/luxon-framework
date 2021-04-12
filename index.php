<?php

    error_reporting(0);

    try {
        function __load($path) {
            if (is_dir($path)) {
                foreach (scandir($path) as $entry) {
                    $fullpath = $path.DIRECTORY_SEPARATOR.$entry;
                    if (in_array($entry, array(".", "..")) || is_link($fullpath)) continue;
                    __load($fullpath);
                }
            } else if (is_file($path)) {
                require_once $path;
            }
        }
    
        __load("config");
        __load("utils");
        __load("database");
        __load("models");
        __load("modules");
        __load("controllers");
        __load("routes");

        if (DB_CONNECT) { Database::connect(); }
        Router::accept();
    } catch (NoRouteException $_EXCEPTION) {
        global $_EXCEPTION;
        Router::setStatus(404); // not found
        include_once "error/route/index.php";
    } catch (Exception $_EXCEPTION) {
        global $_EXCEPTION;
        Router::setStatus(503); // internal server error
        include_once "error/other/index.php";
    }

?>