<?php
    include '../../base.php';

    if (!$loggedIn) {
        die('Goodbye');
    }

    $requestedRaw = trim($_GET["id"] ?? "");

    if ($requestedRaw === "") {
        die("No");
    }

    if (ctype_digit($requestedRaw)) {
        $requestedSetId = (int)$requestedRaw;
    } elseif (preg_match('~^https?://osu\.ppy\.sh/(?:s|beatmapsets)/(?P<setid>\d+)~', $requestedRaw, $matches)) {
        $requestedSetId = (int)$matches['setid'];
    } else {
        die("No");
    }

    $set = GetBeatmapsetDataOsuApi($token, $requestedSetId);

    $beatmap_stmt = $conn->prepare("INSERT INTO `beatmaps` (BeatmapID, SetID, SR, DifficultyName, Mode, Status, Blacklisted, BlacklistReason, Timestamp, ApproachRate, CircleSize, Drain, OverallDifficulty, CircleCount, SpinnerCount, SliderCount, PlayTime, Bpm)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");
    $beatmap_stmt->bind_param("iidsiiissddddiiiid", $beatmapID, $setID, $SR, $difficultyName, $mode, $status, $blacklisted, $blacklist_reason, $dateRanked, $approachRate, $circleSize, $drainHp, $overallDifficulty, $circleCount, $spinnerCount, $sliderCount, $playTime, $bpm);

    $beatmapset_stmt = $conn->prepare("INSERT INTO beatmapsets (DateRanked, Artist, SetID, CreatorID, Genre, Lang, Title, Status, HasStoryboard, HasVideo, CreatorName, IsNSFW) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");
    $beatmapset_stmt->bind_param("ssiiiisiiisi", $dateRanked, $artist, $setID, $creatorID, $genre, $lang, $title, $status, $hasStoryboard, $hasVideo, $creatorName, $isNsfw);

    $creators_stmt = $conn->prepare("INSERT INTO beatmap_creators (BeatmapID, CreatorID) VALUES (?, ?)");
    $creators_stmt->bind_param("ii", $beatmapID, $diffCreatorID);

    $descriptor_stmt = $conn->prepare("INSERT INTO descriptor_votes (BeatmapID, UserID, Vote, DescriptorID) VALUES (?, 0, 1, ?) ON DUPLICATE KEY UPDATE Vote = 1;");
    $descriptor_stmt->bind_param("ii", $beatmapID, $descriptorID);

    if (!$set || sizeof($set["beatmaps"] ?? []) == 0) {
        die("there are no maps found from this id (did u paste in beatmap id)");
    }

    // Set-based params
    $artist = $set["artist"];
    $creatorID = $set["user_id"];
    $setID = $set["id"];
    $genre = $set["genre"]["id"];
    $lang = $set["language"]["id"];
    $title = $set["title"];
    $status = $set["ranked"];
    $blacklisted = 0;
    $hasStoryboard = (int)$set["storyboard"];
    $hasVideo = (int)$set["video"];
    $creatorName = $set["creator"];
    $isNsfw = $set["nsfw"];
    $dateRanked = date("Y-m-d", strtotime($set["last_updated"]));

    // Blacklist + last update checks
    $query2 = $conn->prepare("SELECT * FROM blacklist WHERE UserID = ?");
    $query2->bind_param("i", $creatorID);
    $query2->execute();
    $query2->store_result();
    if ($query2->num_rows > 0) {
        $query2->close();
        die("No");
    }
    $query2->close();

    if (strtotime($set["last_updated"]) > strtotime("-6 months")) {
        die("No - not old enough");
    }

    $query3 = $conn->prepare("SELECT * FROM beatmapsets WHERE SetID = ?");
    $query3->bind_param("i", $setID);
    $query3->execute();
    $query3->store_result();
    if ($query3->num_rows == 0) {
        $beatmapset_stmt->execute();
    }
    $query3->close();

    $isFeaturedArtist = isset($set["track_id"]) && !is_null($set["track_id"]);
    
    $allSetCreators = [];
    foreach ($set["beatmaps"] as $diff) {
        $owners = !empty($diff["owners"]) ? $diff["owners"] : [["id" => $diff["user_id"]]];
        foreach ($owners as $owner) {
            $allSetCreators[] = $owner["id"];
        }
    }
    $uniqueCreatorsCount = count(array_unique($allSetCreators));

    $isMegacollab = ($uniqueCreatorsCount >= 8);
    $isCollab = (!$isMegacollab && $uniqueCreatorsCount >= 2);

    foreach($set["beatmaps"] as $diff){
        if($diff["ranked"] != -2){
            continue;
        }

        $query1 = $conn->prepare("SELECT * FROM `beatmaps` WHERE `BeatmapID` = ?");
        $query1->bind_param("i", $diff["id"]);
        $query1->execute();
        $query1->store_result();
        if ($query1->num_rows > 0) {
            $query1->close();
            continue;
        }
        $query1->close();

        $dateRanked = date("Y-m-d", strtotime($diff["last_updated"]));
        $beatmapID = $diff["id"];
        $SR = $diff["difficulty_rating"];
        $difficultyName = $diff["version"];
        $mode = $diff["mode_int"];
        $approachRate = $diff["ar"];
        $circleSize = $diff["cs"];
        $drainHp = $diff["drain"];
        $overallDifficulty = $diff["accuracy"];
        $circleCount = $diff["count_circles"];
        $spinnerCount = $diff["count_spinners"];
        $sliderCount = $diff["count_sliders"];
        $playTime = $diff["total_length"];
        $bpm = $diff["bpm"];

        $beatmap_stmt->execute();

        $owners = !empty($diff["owners"]) ? $diff["owners"] : [["id" => $diff["user_id"]]];
        foreach ($owners as $owner) {
            $diffCreatorID = $owner["id"];
            $creators_stmt->execute();
        }

        $votesToInsert = [];

        // precision: CS >= 6
        if ($circleSize >= 6) {
            $votesToInsert[] = 35;
        }

        // large circles: CS <= 3 and SR >= 4.0
        if ($circleSize <= 3 && $SR >= 4.0) {
            $votesToInsert[] = 82;
        }

        // slider only: circle count == 0
        if ($circleCount == 0) {
            $votesToInsert[] = 69;
        }

        // circle only: slider count == 0
        if ($sliderCount == 0) {
            $votesToInsert[] = 70;
        }

        if ($playTime > 600) {
            $votesToInsert[] = 40; // gungathon
        } elseif ($playTime > 300) {
            $votesToInsert[] = 39; // marathon
        }

        if ($isFeaturedArtist) {
            $votesToInsert[] = 78;
        }
        if ($isMegacollab) {
            $votesToInsert[] = 68;
        } elseif ($isCollab) {
            $votesToInsert[] = 38;
        }

        foreach ($votesToInsert as $descriptorID) {
            $descriptor_stmt->execute();
        }
    }

    $delete_desc_stmt = $conn->prepare("DELETE FROM beatmap_descriptors WHERE BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID = ?);");
    $delete_desc_stmt->bind_param("i", $setID);
    $delete_desc_stmt->execute();
    $delete_desc_stmt->close();

    $rebuild_desc_stmt = $conn->prepare("
        INSERT INTO beatmap_descriptors (BeatmapID, DescriptorID, Weight)
        SELECT 
            Descriptor_votes.BeatmapID, 
            Descriptor_votes.DescriptorID, 
            SUM(CASE WHEN Vote = 1 THEN 1 ELSE -1 END) AS net
        FROM descriptor_votes
        INNER JOIN beatmaps b ON descriptor_votes.BeatmapID = b.BeatmapID
        WHERE b.SetID = ?
        GROUP BY descriptor_votes.BeatmapID, descriptor_votes.DescriptorID
        HAVING net > 0;
    ");
    $rebuild_desc_stmt->bind_param("i", $setID);
    $rebuild_desc_stmt->execute();
    $rebuild_desc_stmt->close();

    header("Location: ../../mapset/" . $setID);
    die();
?>