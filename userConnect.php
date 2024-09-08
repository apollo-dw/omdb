<?php
	$loggedIn = false;
	$userId = -1;
	$userName = "";

	if (isset($_COOKIE["AccessToken"])) {
		$token = $_COOKIE["AccessToken"];
		
		$stmt = $conn->prepare("SELECT * FROM `users` WHERE `AccessToken` = ?");
		$stmt->bind_param("s", $token);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows == 1) {
			$row = $result->fetch_assoc();
			$loggedIn = true;
			$userId = $row['UserID'];
			$userName = $row['Username'];
			$user = $row;
			
			// Update the last accessed site column in one-minute limits.
			if((time() - strtotime($row['LastAccessedSite'])) > 60){
				$ip = $_SERVER['HTTP_CLIENT_IP'] 
					   ? $_SERVER['HTTP_CLIENT_IP'] 
					   : ($_SERVER['HTTP_X_FORWARDED_FOR'] 
							? $_SERVER['HTTP_X_FORWARDED_FOR'] 
							: $_SERVER['REMOTE_ADDR']);
		
				$stmt = $conn->prepare("UPDATE `users` SET `LastAccessedSite` = CURRENT_TIMESTAMP, `IpAddress` = ? WHERE `AccessToken` = ?");
				$stmt->bind_param("ss", $ip, $token);
				$stmt->execute();
			}
		}
	}
?>