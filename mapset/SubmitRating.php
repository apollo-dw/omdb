<?php
	include '../connection.php';
	
	$beatmapId = $_POST['bID'] ?? -1;
	$rating = $_POST['rating'] ?? -1;
	if ($beatmapId == -1) {
		die ("NO");
	}
	
	$validRatings = array(-2, 0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5);
	if (!in_array($rating, $validRatings)){
		die ("NO");
	}
	
	if($conn->query("SELECT * FROM `beatmaps` WHERE `BeatmapID`='${beatmapId}';")->num_rows != 1){
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
	
	if($rating == -2){
		$conn->query("DELETE FROM `ratings` WHERE `BeatmapID`='${beatmapId}' AND `UserID`='${userId}';");
	} else {
		if($conn->query("SELECT * FROM `ratings` WHERE `BeatmapID`='${beatmapId}' AND `UserID`='${userId}';")->num_rows == 1){
			echo "YES - 2";
			$conn->query("UPDATE `ratings` SET `Score`='${rating}' WHERE `BeatmapID`='${beatmapId}' AND `UserID`='${userId}';");
		}else{
			echo $rating;
			$conn->query("INSERT INTO `ratings` (BeatmapID, UserID, Score, date) VALUES ('${beatmapId}', '${userId}', '${rating}', CURRENT_TIMESTAMP);");
		}
	}