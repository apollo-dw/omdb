<?php
    require "../../base.php";
    if (!$loggedIn) {
        die("Sorry you need to log in");
    }

    $title = $_POST["PostSubject"];
    $body = $_POST["PostBody"];
    $topicId = $_POST["PostTopic"];

    $stmt = $conn->prepare("INSERT INTO forum_threads (Title, TopicID, UserID) VALUES (?, ?, ?);");
    $stmt->bind_param("sii", $title, $topicId, $userId);
    $stmt->execute();
    $threadId = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO forum_posts (ThreadID, UserID, Content) VALUES (?, ?, ?);");
    $stmt->bind_param("iis", $threadId, $userId, $body);
    $stmt->execute();
    $stmt->close();

    header("Location: ../post/?id=" . $threadId);
    die();