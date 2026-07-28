<?php
    require "../../base.php";
    if (!$loggedIn) {
        http_response_code(401);
        exit();
    }

    $body = $_POST["PostReply"];
    $threadId = $_POST["PostThread"];

    $stmt = $conn->prepare("INSERT INTO forum_posts (ThreadID, UserID, Content) VALUES (?, ?, ?);");
    $stmt->bind_param("iis", $threadId, $userId, $body);
    $stmt->execute();
    $postId = $stmt->insert_id;
    $stmt->close();

    header("Location: ../post/?id=" . $threadId . "#post-" . $postId);
    die();
