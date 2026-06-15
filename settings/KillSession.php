<?php
    require '../base.php';
    
    if (!$loggedIn) {
        die("You need to be logged in to do this.");
    }

    if (isset($_GET['token'])) {
        $tokenToKill = $_GET['token'];

        $stmt = $conn->prepare("DELETE FROM `sessions` WHERE `SessionToken` = ? AND `UserID` = ?");
        $stmt->bind_param("si", $tokenToKill, $userId);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: index.php");
    exit;
?>