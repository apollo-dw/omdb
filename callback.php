<?php
    require 'connection.php';
    require 'functions.php';

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	$code = $_GET["code"];
	$fields = json_encode(array(
		"client_id" => $clientID,
		"client_secret" => $clientSecret,
		"code" => $code,
		"grant_type" => "authorization_code",
		"redirect_uri" => relUrl("/callback.php"),
	));

	$curl = curl_init();
	
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://osu.ppy.sh/oauth/token',
	  CURLOPT_CUSTOMREQUEST => 'POST',
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_POST => true,
	  CURLOPT_POSTFIELDS => $fields,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	));

	$response = curl_exec($curl);
	curl_close($curl);

	$json = json_decode($response, true);
	$accessToken = $json["access_token"];
	$refreshToken = $json["refresh_token"];
	$expiresIn = (int) $json["expires_in"];
	
	$curl = curl_init();
	
	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://osu.ppy.sh/api/v2/me/osu',
	  CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'Authorization: Bearer ' . $accessToken],
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'GET',
	));

	$response = curl_exec($curl);
	curl_close($curl);
	
	$json = json_decode($response, true);
	$userId = $json["id"];
	$username = $json["username"];

	if($conn->query("SELECT * FROM `users` WHERE `UserID`='${userId}'")->num_rows == 0){
		$conn->query("INSERT INTO `users` (UserID, Username, AccessToken, RefreshToken) VALUES ('${userId}', '${username}', '${accessToken}', '${refreshToken}')");
	}else{
		$conn->query("UPDATE `users` SET `AccessToken`='${accessToken}', `RefreshToken`='${refreshToken}' WHERE `UserID`=${userId}");
	}
	
	setcookie("AccessToken", $accessToken, time() + $expiresIn);
	siteRedirect();
?>
