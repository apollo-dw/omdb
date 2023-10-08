<?php
    require '../../base.php';

    if (!$loggedIn)
        die("NO");

    header('Content-Type: application/json');

    $proposalID = $_POST["proposalID"];
    $vote = $_POST["vote"];

    $stmt = $conn->prepare("SELECT * FROM descriptor_proposals WHERE ProposalID = ?;");
    $stmt->bind_param('i', $proposalID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0)
        die(array("error" => "NO PROPOSAL FOUND"));

    $checkVoteStmt = $conn->prepare("SELECT VoteID, Vote FROM descriptor_proposal_votes WHERE ProposalID = ? AND UserID = ?");
    $checkVoteStmt->bind_param("ii", $proposalID, $userId);
    $checkVoteStmt->execute();
    $checkVoteResult = $checkVoteStmt->get_result();

    if ($checkVoteResult->num_rows === 0) {
        $insertStmt = $conn->prepare("INSERT INTO descriptor_proposal_votes (UserID, Vote, ProposalID) VALUES (?, ?, ?)");
        $insertStmt->bind_param("isi", $userId, $vote, $proposalID);
        $insertStmt->execute();
    } else {
        $voteIDRow = $checkVoteResult->fetch_assoc();
        $voteID = $voteIDRow["VoteID"];

        if ($voteIDRow["Vote"] != $vote && $vote != "unvoted") {
            $updateStmt = $conn->prepare("UPDATE descriptor_proposal_votes SET Vote = ? WHERE VoteID = ?");
            $updateStmt->bind_param("si", $vote, $voteID);
            $updateStmt->execute();
        } else {
            $removeStmt = $conn->prepare("DELETE FROM descriptor_proposal_votes WHERE VoteID = ?");
            $removeStmt->bind_param("i", $voteID);
            $removeStmt->execute();
        }
    }

    $response = array('status' => 'success', 'message' => 'Vote submitted successfully');
    header('Content-Type: application/json');
    echo json_encode($response);