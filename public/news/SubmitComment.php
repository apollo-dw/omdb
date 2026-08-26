<?php
    require_once __DIR__ . '/../../app/base.php';

    $newsId = $_POST['nID'] ?? -1;
    $comment = trim($_POST['comment'] ?? "");
    if ($newsId == -1) {
        http_response_code(400);
        exit();
    }

    if (strlen($comment) < 3) {
        http_response_code(400);
        exit();
    }

    if (strlen($comment) > 8000) {
        http_response_code(400);
        exit();
    }

    if (!$loggedIn) {
        http_response_code(401);
        exit();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM `news_posts` WHERE `NewsID` = ?;");
    $stmt->bind_param("i", $newsId);
    $stmt->execute();

    if ($stmt->get_result()->fetch_row()[0] == 0) {
        http_response_code(404);
        exit();
    }

    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO `news_comments` (UserID, NewsID, Comment) VALUES (?, ?, ?);");
    $stmt->bind_param("iis", $userId, $newsId, $comment);
    $stmt->execute();
    $stmt->close();
