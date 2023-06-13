<?php
	include '../base.php';
	
	$setId = $_POST['sID'] ?? -1;
	$commentId = $_POST['cID'] ?? -1;
	if ($setId == -1) {
		die("NO - INVALID SET");
	}
	
	if ($commentId == -1) {
		die("NO - INVALID COMMENT");
	}

    if (!$loggedIn) {
        die("NO");
    }

    $stmt = $conn->prepare("DELETE FROM `comments` WHERE `CommentID` = ? AND `UserID` = ? AND `SetID` = ?");
    $stmt->bind_param("iii", $commentId, $userId, $setId);
    $stmt->execute();
    $stmt->close();
?>