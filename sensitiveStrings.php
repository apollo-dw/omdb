<?php
	$env = parse_ini_file(__DIR__ . '/.env');
	
    $servername = "localhost";
    $username = $env["DATABASE_USER"];
    $password = $env["DATABASE_PASSWORD"];
    $dbname = "omdb";
    $apiV1Key = $env["OSU_API_V1_KEY"];
    $clientID = $env["OSU_CLIENT_ID"];
    $clientSecret = $env["OSU_CLIENT_SECRET"];
?>