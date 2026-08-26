<?php
    require_once __DIR__ . '/../../app/base.php';

    if (!$loggedIn) {
        http_response_code(401);
        exit();
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
