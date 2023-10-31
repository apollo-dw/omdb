<?php
    include '../../../base.php';

    if (!$loggedIn) {
        die("Not logged in");
    }

    $proposalID = $_POST['ProposalID'];
    $descriptorName = strtolower($_POST["DescriptorName"]);
    $shortDescription = $_POST["ShortDescription"];
    $parentID = $_POST["ParentDescriptorID"];
    $usable = $_POST["Usable"];

    $stmt = $conn->prepare("SELECT * FROM `descriptor_proposals` WHERE ProposalID = ?;");
    $stmt->bind_param("i", $proposalID);
    $stmt->execute();
    $stmt->execute();
    $proposal = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($parentID === ""){
        $parentID = null;
    }

    $stmt = $conn->prepare("UPDATE `descriptor_proposals` SET Name = ?, ShortDescription = ?, ParentID = ?, Usable = ? WHERE ProposerID = ? AND ProposalID = ?;");
    $stmt->bind_param("ssssii", $descriptorName, $shortDescription, $parentID, $usable, $userId, $proposalID);
    $stmt->execute();
    $stmt->close();

    header('Location: ../?id=' . $proposalID);