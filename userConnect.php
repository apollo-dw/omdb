<?php
	$loggedIn = false;
	$isModerator = false;
	$userId = -1;
	$userName = "";

	if (isset($_COOKIE["SessionToken"])) {
		$sessionToken = $_COOKIE["SessionToken"];

		$stmt = $conn->prepare("SELECT u.*, s.SessionToken, s.ExpiresAt AS SessionExpiresAt, s.LastAccessedAt
			FROM `sessions` s
			JOIN `users` u ON s.UserID = u.UserID
			WHERE s.SessionToken = ? AND s.ExpiresAt > NOW()
			LIMIT 1
		");
		$stmt->bind_param("s", $sessionToken);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows === 1) {
			$row = $result->fetch_assoc();
			$loggedIn = true;
			$userId = $row['UserID'];
			$userName = $row['Username'];
			$isModerator = $row['moderator'] === 1;
			$user = $row;

			if (!empty($row['TokenExpiresAt']) && strtotime($row['TokenExpiresAt']) < time() + 300) {
				$newTokens = refreshOsuToken($conn, $userId, $row['RefreshToken']);
				if ($newTokens) {
					$row['AccessToken'] = $newTokens['access_token'];
					$row['RefreshToken'] = $newTokens['refresh_token'];
					$user = $row;
				} else { // Force logout cuz refresh failed
					$stmt = $conn->prepare("DELETE FROM `sessions` WHERE `SessionToken` = ?");
					$stmt->bind_param("s", $sessionToken);
					$stmt->execute();

					setcookie("SessionToken", "", [
						'expires'  => time() - 3600,
						'path'     => '/',
						'secure'   => true,
						'httponly' => true,
					]);

					$loggedIn = false;
					$userId = -1;
					$userName = "";
					$isModerator = false;
					$user = null;

					return;
				}
			}

			if ((time() - strtotime($row['LastAccessedAt'])) > 60) {
				$ip = $_SERVER['HTTP_CLIENT_IP']
					?? $_SERVER['HTTP_X_FORWARDED_FOR']
					?? $_SERVER['REMOTE_ADDR'];

				$newExpiry     = time() + 30 * 24 * 3600;
				$newExpiryDate = date('Y-m-d H:i:s', $newExpiry);

				$stmt = $conn->prepare("UPDATE `sessions`
					SET `LastAccessedAt` = CURRENT_TIMESTAMP, `ExpiresAt` = ?, `IpAddress` = ?
					WHERE `SessionToken` = ?
				");
				$stmt->bind_param("sss", $newExpiryDate, $ip, $sessionToken);
				$stmt->execute();

				$stmt = $conn->prepare("UPDATE `users`
					SET `LastAccessedSite` = CURRENT_TIMESTAMP, `IpAddress` = ?
					WHERE `UserID` = ?
				");
				$stmt->bind_param("si", $ip, $userId);
				$stmt->execute();

				setcookie("SessionToken", $sessionToken, [
					'expires'  => $newExpiry,
					'path'     => '/',
					'secure'   => true,
					'httponly' => true,
				]);
			}
		}
	}

	function refreshOsuToken($conn, int $userId, string $refreshToken): ?array {
		global $env;

		$fields = json_encode([
			"client_id"     => $env['OSU_CLIENT_ID'],
			"client_secret" => $env['OSU_CLIENT_SECRET'],
			"grant_type"    => "refresh_token",
			"refresh_token" => $refreshToken,
		]);

		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL            => 'https://osu.ppy.sh/oauth/token',
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_POSTFIELDS     => $fields,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Content-Type: application/json'],
		]);
		$response = curl_exec($curl);
		curl_close($curl);

		$json = json_decode($response, true);
		if (empty($json['access_token'])) return null; // refresh failed user will just re-auth

		$newAccessToken  = $json['access_token'];
		$newRefreshToken = $json['refresh_token'];
		$expiresIn       = (int) $json['expires_in'];
		$tokenExpiresAt  = date('Y-m-d H:i:s', time() + $expiresIn);

		$stmt = $conn->prepare("
			UPDATE `users`
			SET `AccessToken` = ?, `RefreshToken` = ?, `TokenExpiresAt` = ?
			WHERE `UserID` = ?
		");
		$stmt->bind_param("sssi", $newAccessToken, $newRefreshToken, $tokenExpiresAt, $userId);
		$stmt->execute();

		return [
			'access_token' => $newAccessToken,
			'refresh_token' => $newRefreshToken
		];
	}
?>