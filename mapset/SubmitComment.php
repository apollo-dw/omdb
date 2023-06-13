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

    $stmt = $conn->prepare("SELECT COUNT(*) FROM `beatmaps` WHERE `SetID`= ?;");
    $stmt->bind_param("i", $setId);
    $stmt->execute();
	
	if($stmt->get_result()->fetch_row()[0] == 0){
		die ("NO - Cant Find Map In DB");
	}

    $stmt->close();
	
	if ($loggedIn == false) {
		die ("NO - Not Logged In");
	}
	
	$stmt = $conn->prepare("INSERT INTO `comments` (UserID, SetID, Comment) VALUES (?, ?, ?);");
	$stmt->bind_param("sss", $userId, $setId, $comment);
	
	$stmt->execute();
	$stmt->close();
?>