<?php
    require_once __DIR__ . '/../../app/base.php';

    if (!$isModerator || !$loggedIn) {
        header('HTTP/1.0 403 Forbidden');
        http_response_code(403);
        exit();
    }
