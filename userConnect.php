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
		}
	}
?>