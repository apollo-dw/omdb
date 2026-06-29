<?php
    ob_start();
    include '../../../base.php';

    if (!$loggedIn) {
        die("Not logged in");
    }

    $descriptorName = $_POST["DescriptorName"];
    $shortDescription = $_POST["ShortDescription"];
    $parentID = $_POST["ParentDescriptorID"];
    $usable = $_POST["Usable"];
    $entryComment = trim($_POST["EntryComment"] ?? "");
    
    $descriptorIdTarget = isset($_POST["DescriptorID"]) && $_POST["DescriptorID"] !== "" ? intval($_POST["DescriptorID"]) : null;
    $type = !is_null($descriptorIdTarget) ? "modify" : "new";

    if (strlen($entryComment ?? "") < 3){
        die("comment too short");
    }

    if ($parentID === ""){
        $parentID = null;
    }

    $stmt = $conn->prepare("INSERT INTO `descriptor_proposals` (DescriptorID, Name, ShortDescription, ParentID, Usable, Type, ProposerID) VALUES (?, ?, ?, ?, ?, ?, ?);");
    $stmt->bind_param("isssisi", $descriptorIdTarget, $descriptorName, $shortDescription, $parentID, $usable, $type, $userId);
    $stmt->execute();
    $proposalId = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO `descriptor_proposal_comments` (UserID, ProposalID, Comment) VALUES (?, ?, ?);");
    $stmt->bind_param("iis", $userId, $proposalId, $entryComment);
    $stmt->execute();
    $stmt->close();

    header('Location: ../?id=' . $proposalId);