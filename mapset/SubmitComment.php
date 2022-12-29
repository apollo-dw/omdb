<?php
	include '../connection.php';
	
	$setId = $_POST['sID'] ?? -1;
	$comment = $_POST['comment'] ?? "";
	if ($beatmapId == -1) {
		die("NO");
	}
	
	if( strlen($comment) < 5){
		die("SHORT");
	}
	
	if( strlen($comment) > 10000){
		die("LONG");
	}
	
	if($conn->query("SELECT * FROM `beatmaps` WHERE `SetID`='${setId}';")->num_rows == 0){
		die ("NO");
	}

	$loggedIn = false;
	$userId = -1;
	$userName = "";

	if (isset($_COOKIE["AccessToken"])) {
		$token = $_COOKIE["AccessToken"];
		
		$stmt = $conn->prepare("SELECT UserID, Username FROM `users` WHERE `AccessToken` = ?");
		$stmt->bind_param("s", $token);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows == 1) {
			$row = $result->fetch_assoc();
			$loggedIn = true;
			$userId = $row['UserID'];
			$userName = $row['Username'];
		}
	}

	
	if ($loggedIn == false) {
		die ("NO");
	}
	

	
	$statement = $conn->prepare("INSERT INTO `comments` (UserID, SetID, Comment) VALUES (?, ?, ?);");
	$statement->bind_param("sss", $uID, $sID, $cmnt);
	$uID = $userId;
	$sID = $setId;
	$cmnt = $comment;
	
	$statement->execute();
	$statement->close();
?>