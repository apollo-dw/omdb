<?php
	require_once 'base.php';
    require_once 'connection.php';
    require_once 'functions.php';

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	
	$state = json_decode(urldecode($_GET["state"]), true);
	$csrf_token = $state["csrf_token"];
	$redirect_url = $state["redirect_url"];

	if (!isset($_SESSION["LOGIN_CSRF_TOKEN"]) || !hash_equals($csrf_token, $_SESSION["LOGIN_CSRF_TOKEN"])) {
		die("Forged request.");
	}
	unset($_SESSION["LOGIN_CSRF_TOKEN"]);

	$code = $_GET["code"];
	$fields = json_encode(array(
		"client_id" => $env['OSU_CLIENT_ID'],
		"client_secret" => $env['OSU_CLIENT_SECRET'],
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
	$tokenExpiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

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
	$country = $json["country"];

	$stmt = $conn->prepare("SELECT * FROM `users` WHERE `UserID` = ?");
	$stmt->bind_param("s", $userId);
	$stmt->execute();
	$result = $stmt->get_result();


	if ($result && $result->num_rows == 0) {
		$stmt = $conn->prepare("INSERT INTO `users` (UserID, Username, AccessToken, RefreshToken, TokenExpiresAt) VALUES (?, ?, ?, ?, ?);");
		$stmt->bind_param("sssss", $userId, $username, $accessToken, $refreshToken, $tokenExpiresAt);
		$stmt->execute();
		$stmt->close();
	} else {
		$stmt = $conn->prepare("UPDATE `users` SET `AccessToken` = ?, `RefreshToken` = ?, `TokenExpiresAt` = ?, `Username` = ? WHERE `UserID` = ?");
		$stmt->bind_param("ssssi", $accessToken, $refreshToken, $tokenExpiresAt, $username, $userId);
		$stmt->execute();
		$stmt->close();
	}
	
	$stmt = $conn->prepare("SELECT * FROM `mappernames` WHERE `UserID` = ?");
	$stmt->bind_param("s", $userId);
	$stmt->execute();
	$result = $stmt->get_result();

	if ($result && $result->num_rows == 0) {
		$stmt = $conn->prepare("INSERT INTO `mappernames` (UserID, Username, Country) VALUES (?, ?, ?);");
		$stmt->bind_param("iss", $userId, $username, $country["code"]);
		$stmt->execute();
		$stmt->close();
	} else {
		$stmt = $conn->prepare("UPDATE `mappernames` SET `Username` = ?, `Country` = ? WHERE `UserID` = ?");
		$stmt->bind_param("ssi", $username, $country["code"], $userId);
		$stmt->execute();
		$stmt->close();
	}

	$sessionToken = bin2hex(random_bytes(32));
	$sessionExpiry = date('Y-m-d H:i:s', time() + 30 * 24 * 3600);

	$ua = $_SERVER["HTTP_USER_AGENT"] ?? "";
	$deviceInfo = parseUserAgent($ua);

	$stmt = $conn->prepare("INSERT INTO `sessions` (SessionToken, UserID, ExpiresAt, DeviceInfo) VALUES (?, ?, ?, ?)");
	$stmt->bind_param("siss", $sessionToken, $userId, $sessionExpiry, $deviceInfo);
	$stmt->execute();
	$stmt->close();
	
	setcookie("SessionToken", $sessionToken, [
		'expires'  => time() + 30 * 24 * 3600,
		'path'     => '/',
		'secure'   => true,
		'httponly' => true,
	]);
	siteRedirect($redirect_url);
?>
