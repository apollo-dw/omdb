<?php
    include '../base.php';

    $name = $_GET["apiname"];

    if(!$loggedIn){
        header("Location: index.php?success");
    }

    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < 32; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    $apiKey = hash('sha256', $userId . $randomString . "omdb!");

    $stmt = $conn->prepare("SELECT * FROM `apikeys` WHERE `UserID` = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows < 5) {
        $stmt = $conn->prepare("INSERT INTO `apikeys` (`Name`, `ApiKey`, `UserID`) VALUES (?, ?, ?);");
        $stmt->bind_param("ssi", $name, $apiKey, $userId);
        $stmt->execute();
    }

    $stmt->close();

    header("Location: index.php");