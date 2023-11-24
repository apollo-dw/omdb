<?php
    include '../../base.php';

    if (!$loggedIn) {
        die('Goodbye');
    }

    $requestedSetId = $_GET["id"] ?? "";

    if ($requestedSetId === "" || !is_numeric($requestedSetId)) {
        die("No");
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://osu.ppy.sh/api/get_beatmaps?k=${apiV1Key}&s=${requestedSetId}",
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $array = json_decode($response, true);

    $beatmap_stmt = $conn->prepare("INSERT INTO `beatmaps` (DateRanked, Artist, BeatmapID, SetID, SR, Genre, Lang, Title, DifficultyName, Mode, Status, Blacklisted, BlacklistReason, SetCreatorID, Timestamp)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");
    $beatmap_stmt->bind_param("ssiidiissiiisis", $dateRanked, $artist, $beatmapID, $setID, $SR, $genre, $lang, $title, $difficultyName, $mode, $status, $blacklisted, $blacklist_reason, $creatorID, $dateRanked);

    $creators_stmt = $conn->prepare("INSERT INTO beatmap_creators (BeatmapID, CreatorID) VALUES (?, ?)");
    $creators_stmt->bind_param("ii", $beatmapID, $creatorID);

    foreach($array as $diff){
        if($diff["approved"] != -2){
            continue;
        }

        $query1 = $conn->prepare("SELECT * FROM `beatmaps` WHERE `BeatmapID` = ?");
        $query1->bind_param("i", $diff["beatmap_id"]);
        $query1->execute();
        $query1->store_result();
        if ($query1->num_rows > 0) {
            $query1->close();
            continue;
        }
        $query1->close();

        $currentTimestamp = time();
        $sixMonthsAgo = strtotime("-6 months", $currentTimestamp);
        if (strtotime($diff["last_update"]) > $sixMonthsAgo) {
            die("No - not old enough");
        }

        $dateRanked = date("Y-m-d", strtotime($diff["last_update"]));
        $artist = $diff["artist"];
        $beatmapID = $diff["beatmap_id"];
        $creatorID = $diff["creator_id"];
        $setID = $diff["beatmapset_id"];
        $SR = $diff["difficultyrating"];
        $genre = $diff["genre_id"];
        $lang = $diff["language_id"];
        $title = $diff["title"];
        $difficultyName = $diff["version"];
        $mode = $diff["mode"];
        $status = $diff["approved"];
        $blacklisted = 0;

        $query2 = $conn->prepare("SELECT * FROM blacklist WHERE UserID = ?");
        $query2->bind_param("i", $creatorID);
        $query2->execute();
        $query2->store_result();
        if ($query2->num_rows > 0) {
            $query2->close();
            die("No");
        }
        $query2->close();

        $beatmap_stmt->execute();
        $creators_stmt->execute();
    }

    header("Location: ../../mapset/" . $requestedSetId);
    die();
?>