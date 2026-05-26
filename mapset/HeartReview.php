<?php
    include '../base.php';
    header('Content-Type: application/json');
    $reviewId = $_POST['rID'] ?? -1;

    if ($reviewId == -1) {
        die ("NO");
    }
    if (!$loggedIn) {
        die ("NO");
    }

    $stmt = $conn->prepare("SELECT ReviewID FROM `reviews` WHERE `ReviewID`= ?;");
    $stmt->bind_param("i", $reviewId);
    $stmt->execute();

    if(is_null($stmt->get_result()->fetch_assoc())){
        die ("NO");
    }

    $stmt->close();

    $stmtCheckHeart = $conn->prepare("SELECT UserID FROM `review_hearts` WHERE `ReviewID` = ? AND `UserID` = ?");
    $stmtCheckHeart->bind_param("ii", $reviewId, $userId);
    $stmtCheckHeart->execute();

    $existingHeart = $stmtCheckHeart->get_result()->fetch_assoc();

    if ($existingHeart) {
        $stmtRemoveHeart = $conn->prepare("DELETE FROM `review_hearts` WHERE `ReviewID` = ? AND `UserID` = ?;");
        $stmtRemoveHeart->bind_param("ii", $reviewId, $userId);
        $stmtRemoveHeart->execute();

        $stmtRemoveHeart->close();

        echo json_encode(array("state" => 0));
    } else {
        $stmtAddHeart = $conn->prepare("INSERT INTO `review_hearts` (`ReviewID`, `UserID`) VALUES (?, ?)");
        $stmtAddHeart->bind_param("ii", $reviewId, $userId);
        $stmtAddHeart->execute();

        $stmtAddHeart->close();

        echo json_encode(array("state" => 1));
    }

    $stmtCheckHeart->close();

