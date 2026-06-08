<?php

require_once 'env.php';

$conn = new mysqli(
	$env['DATABASE_HOST'],
	$env['DATABASE_USER'],
	$env['DATABASE_PASSWORD'],
	'omdb',
);
if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}
