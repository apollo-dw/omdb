<?php

$envFromFile = parse_ini_file(__DIR__ . '/.env');
if (!is_array($envFromFile)) {
	die('Failed to parse .env');
}

$env = array_merge($envFromFile, $_ENV);
$env['DATABASE_HOST'] ??= 'localhost';
$env['PUBLIC_URL'] ??= 'https://omdb.nyahh.net';
