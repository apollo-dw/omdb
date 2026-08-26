<?php
    require_once __DIR__ . '/../../app/base.php';

    $tags = $_POST['tags'];
    $beatmapID = $_POST['beatmapID'];

    $stmt = $conn->prepare("SELECT COUNT(*) FROM `beatmaps` WHERE `BeatmapID`= ?;");
    $stmt->bind_param("i", $beatmapID);
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

    $tagList = explode(',', $tags);

    $deleteStmt = $conn->prepare("DELETE FROM rating_tags WHERE BeatmapID = ? AND UserID = ?");
    $deleteStmt->bind_param("ii", $beatmapID, $userId);
    $deleteStmt->execute();
    $deleteStmt->close();

    $insertStmt = $conn->prepare("INSERT INTO rating_tags (UserID, BeatmapID, Tag) VALUES (?, ?, ?)");
    $insertStmt->bind_param("iis", $userId, $beatmapID, $tag);

    foreach ($tagList as $tag) {
        $tag = trim($tag ?? "");

        if (empty($tag)) {
            continue;
        }

        $insertStmt->execute();
    }

    $insertStmt->close();
