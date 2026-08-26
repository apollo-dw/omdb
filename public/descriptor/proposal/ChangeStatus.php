<?php
    require_once __DIR__ . '/../../../app/base.php';

    if (!$loggedIn) {
        http_response_code(401);
        exit();
    }

    if ($userName != "moonpoint") {
        http_response_code(403);
        exit();
    }

    header('Content-Type: application/json');

    $proposalID = $_POST["proposalID"];
    $newStatus = $_POST["newStatus"];

    $updateStmt = $conn->prepare("UPDATE descriptor_proposals SET `Status` = ?, `EditorID` = ? WHERE `ProposalID` = ?");
    $updateStmt->bind_param("sii", $newStatus, $userId, $proposalID);
    $updateStmt->execute();

    $response = array('status' => 'success', 'message' => 'Status submitted successfully');
    header('Content-Type: application/json');
    echo json_encode($response);
