<?php
    include '../base.php';

    $beatmapId = $_POST['beatmapId'] ?? -1;
    $listId = $_POST['listId'] ?? -1;
    $description = trim($_POST['description'] ?? "");
    if ($beatmapId == -1 || $listId == -1) {
        http_response_code(400);
        exit();
    }

    $stmt = $conn->prepare("SELECT ListID FROM `lists` WHERE `ListID`= ? AND `UserID` = ?;");
    $stmt->bind_param("ii", $listId, $userId);
    $stmt->execute();

    if ($stmt->get_result()->fetch_row()[0] == 0) {
        http_response_code(404);
        exit();
    }

    $stmt->close();

    if ($loggedIn == false) {
        http_response_code(401);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO `comments` (UserID, SetID, Comment) VALUES (?, ?, ?);");
    $stmt->bind_param("sss", $userId, $setId, $comment);

    $stmt->execute();
    $stmt->close();
