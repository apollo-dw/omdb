<?php
    require_once __DIR__ . '/../../app/base.php';
    header('Content-Type: application/json');
    $newsId = $_POST['bID'] ?? -1;

    if ($newsId == -1) {
        http_response_code(400);
        exit();
    }
    if (!$loggedIn) {
        http_response_code(401);
        exit();
    }

    $stmt = $conn->prepare("SELECT NewsID FROM `news_posts` WHERE `NewsID` = ?;");
    $stmt->bind_param("i", $newsId);
    $stmt->execute();

    if (is_null($stmt->get_result()->fetch_assoc())) {
        http_response_code(404);
        exit();
    }

    $stmt->close();

    $stmtCheckHeart = $conn->prepare("SELECT UserID FROM `news_hearts` WHERE `NewsID` = ? AND `UserID` = ?");
    $stmtCheckHeart->bind_param("ii", $newsId, $userId);
    $stmtCheckHeart->execute();
    $existingHeart = $stmtCheckHeart->get_result()->fetch_assoc();

    if ($existingHeart) {
        $stmtRemoveHeart = $conn->prepare("DELETE FROM `news_hearts` WHERE `NewsID` = ? AND `UserID` = ?;");
        $stmtRemoveHeart->bind_param("ii", $newsId, $userId);
        $stmtRemoveHeart->execute();
        $stmtRemoveHeart->close();

        echo json_encode(array("state" => 0));
    } else {
        $stmtAddHeart = $conn->prepare("INSERT INTO `news_hearts` (`NewsID`, `UserID`) VALUES (?, ?)");
        $stmtAddHeart->bind_param("ii", $newsId, $userId);
        $stmtAddHeart->execute();
        $stmtAddHeart->close();

        echo json_encode(array("state" => 1));
    }

    $stmtCheckHeart->close();
