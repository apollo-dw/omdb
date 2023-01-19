<?php
    include '../base.php';

    $apiId = $_GET["id"];
    $stmt = $conn->prepare("SELECT * FROM `apikeys` WHERE ApiID = ?;");
    $stmt->bind_param("i", $apiId);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result == null)
        die("This API doesn't exist.");

    $row = $result->fetch_assoc();
    $apiOwner = $row["UserID"];

    if($apiOwner != $userId)
        die("You do not own this API!");

    $stmt = $conn->prepare("DELETE FROM `apikeys` WHERE ApiID = ? AND UserID = ?;");
    $stmt->bind_param("ii", $apiId, $userId);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php?success");