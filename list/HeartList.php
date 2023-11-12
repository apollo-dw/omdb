<?php
    include '../base.php';
    header('Content-Type: application/json');
    $listId = $_POST['bID'] ?? -1;

    if ($listId == -1) {
        die ("NO");
    }
    if (!$loggedIn) {
        die ("NO");
    }

    $stmt = $conn->prepare("SELECT ListID FROM `lists` WHERE `ListID`= ?;");
    $stmt->bind_param("i", $listId);
    $stmt->execute();

    if(is_null($stmt->get_result()->fetch_assoc())){
        die ("NO");
    }

    $stmt->close();

    $stmtCheckHeart = $conn->prepare("SELECT UserID FROM `list_hearts` WHERE `ListID` = ? AND `UserID` = ?");
    $stmtCheckHeart->bind_param("ii", $listId, $userId);
    $stmtCheckHeart->execute();

    $existingHeart = $stmtCheckHeart->get_result()->fetch_assoc();

    if ($existingHeart) {
        $stmtRemoveHeart = $conn->prepare("DELETE FROM `list_hearts` WHERE `ListID` = ? AND `UserID` = ?;");
        $stmtRemoveHeart->bind_param("ii", $listId, $userId);
        $stmtRemoveHeart->execute();

        $stmtRemoveHeart->close();

        echo json_encode(array("state" => 0));
    } else {
        $stmtAddHeart = $conn->prepare("INSERT INTO `list_hearts` (`ListID`, `UserID`) VALUES (?, ?)");
        $stmtAddHeart->bind_param("ii", $listId, $userId);
        $stmtAddHeart->execute();

        $stmtAddHeart->close();

        echo json_encode(array("state" => 1));
    }

    $stmtCheckHeart->close();

