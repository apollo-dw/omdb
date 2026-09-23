<?php

require_once 'env.php';

$conn = new mysqli(
    $env['DATABASE_HOST'],
    $env['DATABASE_USER'],
    $env['DATABASE_PASSWORD'],
    'omdb',
);
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    http_response_code(500);
    exit();
}

$conn->query("SET time_zone = '+00:00'");
