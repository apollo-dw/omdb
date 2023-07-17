<?php
    include '../base.php';

    $tags = $_POST['tags'];
    $beatmapID = $_POST['beatmapID'];

    $stmt = $conn->prepare("SELECT COUNT(*) FROM `beatmaps` WHERE `BeatmapID`= ?;");
    $stmt->bind_param("i", $beatmapID);
    $stmt->execute();

    if($stmt->get_result()->fetch_row()[0] == 0){
        die ("NO - Cant Find Map In DB");
    }

    $stmt->close();

    if ($loggedIn == false) {
        die ("NO - Not Logged In");
    }

    $tagList = explode(',', $tags);

    $deleteStmt = $conn->prepare("DELETE FROM rating_tags WHERE BeatmapID = ? AND UserID = ?");
    $deleteStmt->bind_param("ii", $beatmapID, $userId);
    $deleteStmt->execute();
    $deleteStmt->close();

    $insertStmt = $conn->prepare("INSERT INTO rating_tags (UserID, BeatmapID, Tag) VALUES (?, ?, ?)");
    $insertStmt->bind_param("iis", $userId, $beatmapID, $tag);

    foreach ($tagList as $tag) {
        $tag = trim($tag);

        if (empty($tag))
            continue;

        $insertStmt->execute();
    }

    $insertStmt->close();