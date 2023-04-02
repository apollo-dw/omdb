<?php
    include '../base.php';
	
	$setId = $_POST['sID'] ?? -1;
	$comment = trim($_POST['comment']) ?? "";
	if ($setId == -1) {
		die("NO");
	}
	
	if( strlen($comment) < 3){
		die("SHORT");
	}
	
	if( strlen($comment) > 8000){
		die("LONG");
	}
	
	if($conn->query("SELECT * FROM `beatmaps` WHERE `SetID`='${setId}';")->num_rows == 0){
		die ("NO - Cant Find Map In DB");
	}
	
	if ($loggedIn == false) {
		die ("NO - Not Logged In");
	}
	
	$statement = $conn->prepare("INSERT INTO `comments` (UserID, SetID, Comment) VALUES (?, ?, ?);");
	$statement->bind_param("sss", $uID, $sID, $cmnt);
	$uID = $userId;
	$sID = $setId;
	$cmnt = $comment;
	
	$statement->execute();
	$statement->close();
?>