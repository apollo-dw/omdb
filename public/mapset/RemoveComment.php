<?php
    require_once __DIR__ . '/../../app/base.php';

    $setId = $_POST['sID'] ?? -1;
    $commentId = $_POST['cID'] ?? -1;
    if ($setId == -1) {
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

    $stmt = $conn->prepare("SELECT * FROM `comments` WHERE `CommentID` = ? and `SetID` = ?;");
    $stmt->bind_param("ii", $commentId, $setId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!($isModerator || $result["UserID"] === $userId)) {
        header('HTTP/1.0 403 Forbidden');
        http_response_code(403);
        exit();
    }

    $array = array(
        "type" => "comment_deletion",
        "data" => array(
            "CommentID" => $result["CommentID"],
            "UserID" => $result["UserID"],
            "SetID" => $result["SetID"],
            "Comment" => $result["Comment"],
            "Date" => $result["date"],
        ));

    $json = json_encode($array);

    $stmt = $conn->prepare("INSERT INTO logs (UserID, LogData) VALUES (?, ?);");
    $stmt->bind_param("is", $userId, $json);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM `comments` WHERE `CommentID` = ? AND `SetID` = ?");
    $stmt->bind_param("ii", $commentId, $setId);
    $stmt->execute();
    $stmt->close();
