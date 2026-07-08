<?php
    ob_start();
    include '../../../base.php';

    if (!$loggedIn) {
        die("Not logged in");
    }

    $proposalID = $_POST['ProposalID'];
    $descriptorName = $_POST["DescriptorName"];
    $shortDescription = $_POST["ShortDescription"];
    $longDescription = $_POST["LongDescription"];
    $parentID = $_POST["ParentDescriptorID"];
    $usable = $_POST["Usable"];

    $stmt = $conn->prepare("SELECT * FROM `descriptor_proposals` WHERE ProposalID = ?;");
    $stmt->bind_param("i", $proposalID);
    $stmt->execute();
    $proposal = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($parentID === ""){
        $parentID = null;
    }

    $stmt = $conn->prepare("UPDATE `descriptor_proposals` SET Name = ?, ShortDescription = ?, LongDescription = ?, ParentID = ?, Usable = ? WHERE ProposerID = ? AND ProposalID = ?;");
    $stmt->bind_param("ssssiii", $descriptorName, $shortDescription, $longDescription, $parentID, $usable, $userId, $proposalID);
    $stmt->execute();
    $stmt->close();

    header('Location: ../?id=' . $proposalID);