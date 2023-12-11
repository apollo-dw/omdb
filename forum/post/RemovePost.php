<?php
    include '../../base.php';

    $threadId = $_POST['tID'] ?? -1;
    $postId = $_POST['pID'] ?? -1;
    if ($threadId == -1) {
        die("NO - INVALID THREAD");
    }

    if ($postId == -1) {
        die("NO - INVALID POST");
    }

    if (!$loggedIn) {
        die("NO - LOG IN!");
    }

    $stmt = $conn->prepare("SELECT * FROM `forum_posts` WHERE `PostID` = ? and `ThreadID` = ?;");
    $stmt->bind_param("ii", $postId, $threadId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!($result["UserID"] === $userId || $userId === 9558549)) {
        die("NO");
    }

    $array = array(
        "type" => "post_deletion",
        "data" => array(
            "CommentID" => $result["CommentID"],
            "UserID" => $result["UserID"],
            "ThreadID" => $threadId,
            "Content" => $result["Content"],
            "Date" => $result["date"],
        ));

    $json = json_encode($array);

    $stmt = $conn->prepare("INSERT INTO logs (UserID, LogData) VALUES (?, ?);");
    $stmt->bind_param("is", $userId, $json);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM `forum_posts` WHERE `PostID` = ? AND `ThreadID` = ?;");
    $stmt->bind_param("ii", $postId, $threadId);
    $stmt->execute();
    $stmt->close();
?>