<?php
    require '../../base.php';

    if (!$loggedIn) {
        die("NO");
    }

    header('Content-Type: application/json');
    $descriptorID = $_GET["descriptorID"];

    $stmt = $conn->prepare("SELECT * FROM descriptors WHERE DescriptorID = ?;");
    $stmt->bind_param('i', $descriptorID);
    $stmt->execute();
    $result = $stmt->get_result();
    $descriptorData = $result->fetch_assoc();

    echo json_encode($descriptorData);