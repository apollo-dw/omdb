<?php
	include '../base.php';
	
	$beatmapId = $_POST['bID'] ?? -1;
	$rating = $_POST['rating'] ?? -1;
	if ($beatmapId == -1) {
		die ("NO");
	}
	
	$validRatings = array(-2, 0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5);
	if (!in_array($rating, $validRatings)){
		die ("NO");
	}
	
	if($conn->query("SELECT * FROM `beatmaps` WHERE `BeatmapID`='{$beatmapId}';")->num_rows != 1){
		die ("NO");
	}
	
	if ($loggedIn == false) {
		die ("NO");
	}
	
	SubmitRating($conn, $beatmapId, $userId, $rating);