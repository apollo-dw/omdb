<?php
    include '../../base.php';

    $proposalID = $_POST['pID'] ?? -1;
	$commentId = $_POST['cID'] ?? -1;
	if ($proposalID == -1) {
		die("NO - INVALID SET");
	}
	
	if ($commentId == -1) {
		die("NO - INVALID COMMENT");
	}

    if (!$loggedIn) {
        die("NO");
    }

    $stmt = $conn->prepare("SELECT * FROM `descriptor_proposal_comments` WHERE `CommentID` = ? and `ProposalID` = ?;");
    $stmt->bind_param("ii", $commentId, $proposalID);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $array = array(
        "type" => "comment_deletion",
        "data" => array(
            "CommentID" => $result["CommentID"],
            "UserID" => $result["UserID"],
            "ProposalID" => $result["ProposalID"],
            "Comment" => $result["Comment"],
            "Date" => $result["date"],
        ));

    $json = json_encode($array);

    $stmt = $conn->prepare("INSERT INTO logs (UserID, LogData) VALUES (?, ?);");
    $stmt->bind_param("is", $userId, $json);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM `descriptor_proposal_comments` WHERE `CommentID` = ? AND `UserID` = ? AND `ProposalID` = ?");
    $stmt->bind_param("iii", $commentId, $userId, $proposalID);
    $stmt->execute();
    $stmt->close();
?>