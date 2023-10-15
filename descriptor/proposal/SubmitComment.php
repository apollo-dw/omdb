<?php
    include '../../base.php';

    $proposalID = $_POST['pID'] ?? -1;
	$comment = trim($_POST['comment']) ?? "";
	if ($proposalID == -1) {
		die("NO");
	}
	
	if( strlen($comment) < 3){
		die("SHORT");
	}
	
	if( strlen($comment) > 8000){
		die("LONG");
	}

    $stmt = $conn->prepare("SELECT COUNT(*) FROM `descriptor_proposals` WHERE `ProposalID`= ? AND `Status` = 'pending';");
    $stmt->bind_param("i", $proposalID);
    $stmt->execute();
	
	if($stmt->get_result()->fetch_row()[0] == 0){
		die ("NO - Cant Find Proposal In DB");
	}

    $stmt->close();
	
	if ($loggedIn == false) {
		die ("NO - Not Logged In");
	}
	
	$stmt = $conn->prepare("INSERT INTO `descriptor_proposal_comments` (UserID, ProposalID, Comment) VALUES (?, ?, ?);");
	$stmt->bind_param("sss", $userId, $proposalID, $comment);
	
	$stmt->execute();
	$stmt->close();
?>