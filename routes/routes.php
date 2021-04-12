<?php

    /* Guestbook */
    Router::route("GET", "/^\/$/", function() { view('index'); });
    Router::route("POST", "/^\/$/", ['Guestbook', 'addSignature']);

?>