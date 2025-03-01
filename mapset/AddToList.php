<?php
    include '../base.php';
	
	$beatmapId = $_POST['beatmapId'] ?? -1;
	$listId = $_POST['listId'] ?? -1;
	$description = trim($_POST['description']) ?? "";
	if ($beatmapId == -1 || $listId == -1) {
		die("NO");
	}

    $stmt = $conn->prepare("SELECT ListID FROM `lists` WHERE `ListID`= ? AND `UserID` = ?;");
    $stmt->bind_param("ii", $listId, $userId);
    $stmt->execute();
	
	if($stmt->get_result()->fetch_row()[0] == 0){
		die ("no - list doesnt exist");
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