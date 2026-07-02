<?php

include '../base.php';

$set_id = $_POST['sID'] ?? -1;
$comment = trim($_POST['comment'] ?? "");

if (strlen($comment ?? "") < 3) {
	die("SHORT");
}

if (strlen($comment ?? "") > 40000) {
	die("LONG");
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM `beatmaps` WHERE `SetID`= ?;");
$stmt->bind_param("i", $set_id);
$stmt->execute();

if ($stmt->get_result()->fetch_row()[0] == 0) {
	die("NO - Cant Find Map In DB");
}

$stmt->close();

if ($loggedIn == false) {
	die("not logged in");
}

$stmt = $conn->prepare("
		INSERT INTO `reviews` (UserID, SetID, Comment)
		VALUES (?, ?, ?)
		ON DUPLICATE KEY UPDATE Comment = VALUES(Comment);
	");
$stmt->bind_param("sss", $userId, $set_id, $comment);
$stmt->execute();
$stmt->close();
?>