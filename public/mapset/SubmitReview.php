<?php
    require_once __DIR__ . '/../../app/base.php';

    $set_id = $_POST['sID'] ?? -1;
    $comment = trim($_POST['comment'] ?? "");
    $beatmap_id = filter_var($_POST['bID'] ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

    if ($loggedIn == false) {
        http_response_code(401);
        exit();
    }

    if (strlen($comment ?? "") < 3) {
        http_response_code(400);
        exit();
    }

    if (strlen($comment ?? "") > 40000) {
        http_response_code(400);
        exit();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) FROM `beatmaps` WHERE `SetID`= ?;");
    $stmt->bind_param("i", $set_id);
    $stmt->execute();

    if ($stmt->get_result()->fetch_row()[0] == 0) {
        http_response_code(404);
        exit();
    }

    $stmt->close();

    if ($beatmap_id !== null) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `beatmaps` WHERE `SetID` = ? AND `BeatmapID` = ?;");
        $stmt->bind_param("ii", $set_id, $beatmap_id);
        $stmt->execute();

        if ($stmt->get_result()->fetch_row()[0] == 0) {
            http_response_code(404);
            exit();
        }

        $stmt->close();
    }

    $stmt = $conn->prepare("
		INSERT INTO `reviews` (UserID, SetID, Comment, BeatmapID)
		VALUES (?, ?, ?, ?)
		ON DUPLICATE KEY UPDATE 
                    Comment = VALUES(Comment),
                    BeatmapID = VALUES(BeatmapID);
	");
    $stmt->bind_param("iisi", $userId, $set_id, $comment, $beatmap_id);
    $stmt->execute();
    $stmt->close();
