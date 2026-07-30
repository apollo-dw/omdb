<?php
    include '../base.php';

    $set_id = $_POST['sID'] ?? -1;
    $comment = trim($_POST['comment'] ?? "");

    if (strlen($comment ?? "") < 3) {
        http_response_code(400);
        exit();
    }

    if (strlen($comment ?? "") > 40000) {
        http_response_code(400);
        exit();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM `beatmaps` WHERE `SetID`= ?;");
    $stmt->bind_param("i", $set_id);
    $stmt->execute();

    if ($stmt->get_result()->fetch_row()[0] == 0) {
        http_response_code(404);
        exit();
    }

    $stmt->close();

    if ($loggedIn == false) {
        http_response_code(401);
        exit();
    }

    $stmt = $conn->prepare("
		INSERT INTO `reviews` (UserID, SetID, Comment)
		VALUES (?, ?, ?)
		ON DUPLICATE KEY UPDATE Comment = VALUES(Comment);
	");
    $stmt->bind_param("iis", $userId, $set_id, $comment);
    $stmt->execute();
    $stmt->close();
