<?php
    require_once __DIR__ . '/../../../app/base.php';

    $proposalID = $_POST['pID'] ?? -1;
    $commentId = $_POST['cID'] ?? -1;
    if ($proposalID == -1) {
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

    $stmt = $conn->prepare("SELECT * FROM `descriptor_proposal_comments` WHERE `CommentID` = ? and `ProposalID` = ?;");
    $stmt->bind_param("ii", $commentId, $proposalID);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result["UserID"] != $userId && $userName != "moonpoint") {
        http_response_code(403);
        exit();
    }

    $array = array(
        "type" => "comment_deletion",
        "data" => array(
            "CommentID" => $result["CommentID"],
            "UserID" => $result["UserID"],
            "ProposalID" => $result["ProposalID"],
            "Comment" => $result["Comment"],
            "Date" => $result["Timestamp"],
        ));

    $json = json_encode($array);

    $stmt = $conn->prepare("INSERT INTO logs (UserID, LogData) VALUES (?, ?);");
    $stmt->bind_param("is", $userId, $json);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM `descriptor_proposal_comments` WHERE `CommentID` = ? AND `ProposalID` = ?");
    $stmt->bind_param("ii", $commentId, $proposalID);
    $stmt->execute();
    $stmt->close();
