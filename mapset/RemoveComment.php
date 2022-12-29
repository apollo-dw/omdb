<?php
	include '../connection.php';
	
	$setId = $_POST['sID'] ?? -1;
	$commentId = $_POST['cID'] ?? -1;
	if ($setId == -1) {
		die("NO - INVALID SET");
	}
	
	if ($commentId == -1) {
		die("NO - INVALID COMMENT");
	}

	$loggedIn = false;
	$userId = -1;
	$userName = "";
	if(isset($_COOKIE["AccessToken"])){
		$token = $_COOKIE["AccessToken"];
		if($conn->query("SELECT * FROM `users` WHERE `AccessToken`='${token}'")->num_rows == 1){
			$loggedIn = true;
			$userId = $conn->query("SELECT UserID FROM `users` WHERE `AccessToken`='${token}'")->fetch_row()[0];
			$userName = $conn->query("SELECT Username FROM `users` WHERE `AccessToken`='${token}'")->fetch_row()[0];
		}
	}

	
	if ($loggedIn == false) {
		die ("NO");
	}
	
	$conn->query("DELETE FROM `comments` WHERE `CommentID`='${commentId}' AND `UserID`='${userId}' AND `SetID`='${setId}';");
?>