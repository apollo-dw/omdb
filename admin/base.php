<?php
    require_once ('../base.php');

    if ($userName != "apollodw" || !$loggedIn) {
        header('HTTP/1.0 403 Forbidden');
        http_response_code(403);
        die("Forbidden");
    }