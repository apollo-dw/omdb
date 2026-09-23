<?php

$envFilePath = dirname(__DIR__, 2) . '/.env';

if (!file_exists($envFilePath)) {
    error_log('Missing .env file at project root');
    http_response_code(500);
    exit();
}

$envFromFile = parse_ini_file($envFilePath);
if (!is_array($envFromFile)) {
    error_log('Failed to parse .env');
    http_response_code(500);
    exit();
}

$env = array_merge($envFromFile, $_ENV);
$env['DATABASE_HOST'] ??= 'localhost';
$env['PUBLIC_URL'] ??= 'https://omdb.nyahh.net';
