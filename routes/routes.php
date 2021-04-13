<?php

    /* Luxon's default page */
    Router::route("GET", "/^\/$/", function() {
        include_once "views/standalone/luxon.php";
    });

?>