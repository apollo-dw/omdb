<?php

$envFromFile = parse_ini_file(__DIR__ . '/.env');
if (!is_array($envFromFile)) {
    error_log('Failed to parse .env');
    http_response_code(500);
    exit();
}

$env = array_merge($envFromFile, $_ENV);
$env['DATABASE_HOST'] ??= 'localhost';
$env['PUBLIC_URL'] ??= 'https://omdb.nyahh.net';
