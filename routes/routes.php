<?php

    Router::route("GET", "/^\/$/", function() {
        view('index');
    });

?>