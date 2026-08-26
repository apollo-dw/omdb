<?php
    require_once __DIR__ . '/../../app/base.php';

    $newsID = $_POST['nID'] ?? -1;
    $commentId = $_POST['cID'] ?? -1;
    if ($newsID == -1) {
        http_response_code(400);
        exit();
    }

    if ($commentId == -1) {
        http_response_code(400);
        exit();
    }

    if (!$loggedIn) {
        http_response_code(401);
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM `news_comments` WHERE `CommentID` = ? and `NewsID` = ?;");
    $stmt->bind_param("ii", $commentId, $newsID);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result["UserID"] != $userId && $userName != "moonpoint") {
        header('HTTP/1.0 403 Forbidden');
        http_response_code(403);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM `news_comments` WHERE `CommentID` = ? AND `NewsID` = ?");
    $stmt->bind_param("ii", $commentId, $newsID);
    $stmt->execute();
    $stmt->close();

    $array = array(
        "type" => "comment_deletion",
        "data" => array(
            "CommentID" => $result["CommentID"],
            "UserID" => $result["UserID"],
            "NewsID" => $result["NewsID"],
            "Comment" => $result["Comment"],
            "Date" => $result["Timestamp"],
        ));

    $json = json_encode($array);

    $stmt = $conn->prepare("INSERT INTO logs (UserID, LogData) VALUES (?, ?);");
    $stmt->bind_param("is", $userId, $json);
    $stmt->execute();
    $stmt->close();
