<?php
    include '../../../base.php';

    if (!$loggedIn) {
        die("Not logged in");
    }

    $descriptorName = $_POST["DescriptorName"];
    $shortDescription = $_POST["ShortDescription"];
    $parentID = $_POST["ParentDescriptorID"];
    $usable = $_POST["Usable"];
    $entryComment = trim($_POST["EntryComment"]) ?? "";
    $type = "new";

    if (strlen($entryComment) < 3){
        die("comment too short");
    }

    if ($parentID === ""){
        $parentID = null;
    }

    $stmt = $conn->prepare("INSERT INTO `descriptor_proposals` (Name, ShortDescription, ParentID, Usable, Type, ProposerID) VALUES (?, ?, ?, ?, ?, ?);");
    $stmt->bind_param("sssssi", $descriptorName, $shortDescription, $parentID, $usable, $type, $userId);
    $stmt->execute();
    $descriptorId = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO `descriptor_proposal_comments` (UserID, ProposalID, Comment) VALUES (?, ?, ?);");
    $stmt->bind_param("iis", $userId, $descriptorId, $entryComment);
    $stmt->execute();
    $stmt->close();

    header('Location: ../?id=' . $descriptorId);