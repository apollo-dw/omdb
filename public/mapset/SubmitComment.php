<?php
    include '../base.php';

    $setId = $_POST['sID'] ?? -1;
    $comment = trim($_POST['comment'] ?? "");
    if ($setId == -1) {
        http_response_code(400);
        exit();
    }

    if (strlen($comment ?? "") < 3) {
        http_response_code(400);
        exit();
    }

    if (strlen($comment ?? "") > 8000) {
        http_response_code(400);
        exit();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM `beatmaps` WHERE `SetID`= ?;");
    $stmt->bind_param("i", $setId);
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

    $stmt = $conn->prepare("INSERT INTO `comments` (UserID, SetID, Comment) VALUES (?, ?, ?);");
    $stmt->bind_param("sss", $userId, $setId, $comment);

    $stmt->execute();
    $stmt->close();
