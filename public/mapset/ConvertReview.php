<?php
    require_once __DIR__ . '/../../app/base.php';

    $setId = $_POST['sID'] ?? -1;
    $reviewId = $_POST['rID'] ?? -1;
    if ($setId == -1) {
        http_response_code(400);
        exit();
    }

    if ($reviewId == -1) {
        http_response_code(400);
        exit();
    }

    if (!$loggedIn) {
        http_response_code(401);
        exit();
    }

    if (!$isModerator) {
        header('HTTP/1.0 403 Forbidden');
        http_response_code(403);
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM `reviews` WHERE `ReviewID` = ? and `SetID` = ?;");
    $stmt->bind_param("ii", $reviewId, $setId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        http_response_code(404);
        exit();
    }

    $array = array(
        "type" => "review_to_comment",
        "data" => array(
            "ReviewID" => $result["ReviewID"],
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

    $stmt = $conn->prepare("INSERT INTO `comments` (UserID, SetID, Comment, date) VALUES (?, ?, ?, ?);");
    $stmt->bind_param("iiss", $result["UserID"], $setId, $result["Comment"], $result["date"]);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM `reviews` WHERE `ReviewID` = ? AND `SetID` = ?");
    $stmt->bind_param("ii", $reviewId, $setId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM `review_hearts` WHERE `ReviewID` = ?;");
    $stmt->bind_param("i", $reviewId);
    $stmt->execute();
    $stmt->close();
