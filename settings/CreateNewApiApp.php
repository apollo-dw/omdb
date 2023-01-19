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

    $conn->query("INSERT INTO `apikeys` (`Name`, `ApiKey`, `UserID`) VALUES ('{$name}', '{$apiKey}', '{$userId}');");

    header("Location: index.php?success");