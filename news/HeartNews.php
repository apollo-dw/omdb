<?php
    include '../base.php';
    header('Content-Type: application/json');
    $newsId = $_POST['bID'] ?? -1;

    if ($newsId == -1) {
        die("BREAKING NEWS: UR TROLLING");
    }
    if (!$loggedIn) {
        die("BREAKING NEWS: UR TROLLING");
    }

    $stmt = $conn->prepare("SELECT NewsID FROM `news_posts` WHERE `NewsID` = ?;");
    $stmt->bind_param("i", $newsId);
    $stmt->execute();

    if (is_null($stmt->get_result()->fetch_assoc())) {
        die("BREAKING NEWS: UR TROLLING");
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
?>