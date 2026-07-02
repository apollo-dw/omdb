<?php
    include '../base.php';

    $newsID = $_POST['nID'] ?? -1;
    $commentId = $_POST['cID'] ?? -1;
    if ($newsID == -1) {
        die("NO - INVALID SET");
    }

    if ($commentId == -1) {
        die("NO - INVALID COMMENT");
    }

    if (!$loggedIn) {
        die("NO");
    }

    if ($userName != "moonpoint") {
        header('HTTP/1.0 403 Forbidden');
        http_response_code(403);
        die("Forbidden");
    }

    $stmt = $conn->prepare("SELECT * FROM `news_comments` WHERE `CommentID` = ? and `NewsID` = ?;");
    $stmt->bind_param("ii", $commentId, $newsID);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $array = array(
        "type" => "comment_scrubbed",
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

    $stmt = $conn->prepare("UPDATE `news_comments` SET `Comment`='[comment removed]' WHERE `CommentID` = ? AND `NewsID` = ?;");
    $stmt->bind_param("ii", $commentId, $newsID);
    $stmt->execute();
    $stmt->close();

?>