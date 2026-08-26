<?php
    require_once __DIR__ . '/../../../app/base.php';

    $proposalID = $_POST['pID'] ?? -1;
    $comment = trim($_POST['comment'] ?? "");
    if ($proposalID == -1) {
        http_response_code(400);
        exit();
    }

    if (strlen($comment) < 3) {
        http_response_code(400);
        exit();
    }

    if (strlen($comment) > 8000) {
        http_response_code(400);
        exit();
    }

    if (!$loggedIn) {
        http_response_code(401);
        exit();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM `descriptor_proposals` WHERE `ProposalID`= ? AND `Status` = 'pending';");
    $stmt->bind_param("i", $proposalID);
    $stmt->execute();

    if ($stmt->get_result()->fetch_row()[0] == 0) {
        http_response_code(404);
        exit();
    }

    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO `descriptor_proposal_comments` (UserID, ProposalID, Comment) VALUES (?, ?, ?);");
    $stmt->bind_param("iis", $userId, $proposalID, $comment);
    $stmt->execute();
    $stmt->close();
