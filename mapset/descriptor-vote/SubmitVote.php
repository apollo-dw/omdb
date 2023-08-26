<?php
    require '../../base.php';

    if (!$loggedIn)
        die("NO");

    header('Content-Type: application/json');

    $beatmapID = $_POST["beatmapID"];
    $descriptorID = $_POST["descriptorID"];
    $vote = $_POST["vote"];

    $stmt = $conn->prepare("SELECT BeatmapID FROM beatmaps WHERE BeatmapID = ?;");
    $stmt->bind_param('i', $beatmapID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0)
        die(array("error" => "NO BEATMAP FOUND"));

    $stmt = $conn->prepare("SELECT * FROM descriptors WHERE DescriptorID = ?;");
    $stmt->bind_param('i', $descriptorID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0)
        die(array("error" => "NO DESCRIPTOR FOUND"));

    $checkVoteStmt = $conn->prepare("SELECT VoteID, Vote FROM descriptor_votes WHERE BeatmapID = ? AND UserID = ? AND DescriptorID = ?");
    $checkVoteStmt->bind_param("iii", $beatmapID, $userId, $descriptorID);
    $checkVoteStmt->execute();
    $checkVoteResult = $checkVoteStmt->get_result();

    if ($checkVoteResult->num_rows === 0) {
        $insertStmt = $conn->prepare("INSERT INTO descriptor_votes (BeatmapID, UserID, Vote, DescriptorID) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param("iiii", $beatmapID, $userId, $vote, $descriptorID);
        $insertStmt->execute();
    } else {
        $voteIDRow = $checkVoteResult->fetch_assoc();
        $voteID = $voteIDRow["VoteID"];

        if ($voteIDRow["Vote"] != $vote) {
            $updateStmt = $conn->prepare("UPDATE descriptor_votes SET Vote = ? WHERE VoteID = ?");
            $updateStmt->bind_param("ii", $vote, $voteID);
            $updateStmt->execute();
        } else {
            $removeStmt = $conn->prepare("DELETE FROM descriptor_votes WHERE VoteID = ?");
            $removeStmt->bind_param("i", $voteID);
            $removeStmt->execute();
        }
    }

    $stmt = $conn->prepare("SELECT SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) AS upvotes, SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END) AS downvotes FROM descriptor_votes WHERE BeatmapID = ? AND DescriptorID = ?");
    $stmt->bind_param('ii', $beatmapID, $descriptorID);
    $stmt->execute();
    $result = $stmt->get_result();
    $voteData = $result->fetch_assoc();

    $stmt = $conn->prepare( "SELECT users.Username FROM descriptor_votes INNER JOIN users ON descriptor_votes.UserID = users.UserID WHERE descriptor_votes.BeatmapID = ? AND descriptor_votes.DescriptorID = ? AND descriptor_votes.Vote = 1;");
    $stmt->bind_param('ii', $beatmapID, $descriptorID);
    $stmt->execute();
    $result = $stmt->get_result();

    $upvoteUsernames = array();
    while ($row = $result->fetch_assoc())
        $upvoteUsernames[] = $row['Username'];

    $stmt = $conn->prepare("SELECT users.Username FROM descriptor_votes INNER JOIN users ON descriptor_votes.UserID = users.UserID WHERE descriptor_votes.BeatmapID = ? AND descriptor_votes.DescriptorID = ? AND descriptor_votes.Vote = 0;");
    $stmt->bind_param('ii', $beatmapID, $descriptorID);
    $stmt->execute();
    $result = $stmt->get_result();

    $downvoteUsernames = array();
    while ($row = $result->fetch_assoc())
        $downvoteUsernames[] = $row['Username'];

    $response = array(
        'upvotes' => $voteData['upvotes'],
        'downvotes' => $voteData['downvotes'],
        'upvoteUsernames' => $upvoteUsernames,
        'downvoteUsernames' => $downvoteUsernames
    );

    echo json_encode($response);