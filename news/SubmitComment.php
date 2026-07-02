<?php
    include '../base.php';

    $newsId = $_POST['nID'] ?? -1;
    $comment = trim($_POST['comment'] ?? "");
    if ($newsId == -1) {
        die("NO");
    }

    if (strlen($comment) < 3) {
        die("SHORT");
    }

    if (strlen($comment) > 8000) {
        die("LONG");
    }

    if (!$loggedIn) {
        die("NO - Not Logged In");
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM `news_posts` WHERE `NewsID` = ?;");
    $stmt->bind_param("i", $newsId);
    $stmt->execute();

    if ($stmt->get_result()->fetch_row()[0] == 0) {
        die("NO - Cant Find Post In DB");
    }

    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO `news_comments` (UserID, NewsID, Comment) VALUES (?, ?, ?);");
    $stmt->bind_param("iis", $userId, $newsId, $comment);
    $stmt->execute();
    $stmt->close();
?>