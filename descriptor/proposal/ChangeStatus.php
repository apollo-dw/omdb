<?php
    require '../../base.php';

    if (!$loggedIn)
        die("NO");

    if ($userName != "apollodw") {
        header('HTTP/1.0 403 Forbidden');
        http_response_code(403);
        die("Forbidden");
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