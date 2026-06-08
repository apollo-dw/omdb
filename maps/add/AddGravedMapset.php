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

    $beatmap_stmt = $conn->prepare("INSERT INTO `beatmaps` (BeatmapID, SetID, SR, DifficultyName, Mode, Status, Blacklisted, BlacklistReason, Timestamp)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);");
    $beatmap_stmt->bind_param("iidsiiiss", $beatmapID, $setID, $SR, $difficultyName, $mode, $status, $blacklisted, $blacklist_reason, $dateRanked);

    $beatmapset_stmt = $conn->prepare("INSERT INTO beatmapsets (DateRanked, Artist, SetID, CreatorID, Genre, Lang, Title, Status, HasStoryboard, HasVideo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");
    $beatmapset_stmt->bind_param("ssiiiisiii", $dateRanked, $artist, $setID, $creatorID, $genre, $lang, $title, $status, $hasStoryboard, $hasVideo);

    $creators_stmt = $conn->prepare("INSERT INTO beatmap_creators (BeatmapID, CreatorID) VALUES (?, ?)");
    $creators_stmt->bind_param("ii", $beatmapID, $diffCreatorID);

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

    // Diff-based params
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

        $beatmap_stmt->execute();

        $owners = !empty($diff["owners"]) ? $diff["owners"] : [["id" => $diff["user_id"]]];
        foreach ($owners as $owner) {
            $diffCreatorID = $owner["id"];
            $creators_stmt->execute();
        }
    }

    header("Location: ../../mapset/" . $setID);
    die();
?>