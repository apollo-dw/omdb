<?php
    include '../../base.php';

    if (!$loggedIn) {
        echo 'Goodbye';
    }

    $requestedSetId = $_GET["id"] ?? "";

    if ($requestedSetId === "" || !is_numeric($requestedSetId)) {
        die("No");
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

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

    $beatmap_stmt = $conn->prepare("INSERT INTO `beatmaps` (DateRanked, Artist, BeatmapID, SetID, SR, Genre, Lang, Title, DifficultyName, Mode, Status, Blacklisted, BlacklistReason, SetCreatorID)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);");
    $beatmap_stmt->bind_param("ssiidiissiiisi", $dateRanked, $artist, $beatmapID, $setID, $SR, $genre, $lang, $title, $difficultyName, $mode, $status, $blacklisted, $blacklist_reason, $creatorID);

    $creators_stmt = $conn->prepare("INSERT INTO beatmap_creators (BeatmapID, CreatorID) VALUES (?, ?)");
    $creators_stmt->bind_param("ii", $beatmapID, $creatorID);

    if (count($array) == 0) {
        die("");
    }

    foreach($array as $diff){
        if($diff["approved"] != -2){
            continue;
        }

        if($conn->query("SELECT * FROM `beatmaps` WHERE `BeatmapID`='${diff["beatmap_id"]}';")->num_rows > 0){
            echo "skipping";
            continue;
        }

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
        $blacklist_reason = null;
        if($conn->query("SELECT * FROM blacklist WHERE UserID = ${creatorID}")->num_rows > 0){
            die("No - Dude is Blacklisted");
        }

        $beatmap_stmt->execute();
        $creators_stmt->execute();
    }

    header("Location: ../../mapset/" . $requestedSetId);
    die();
?>