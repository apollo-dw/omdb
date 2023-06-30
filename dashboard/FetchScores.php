<?php
	require "../base.php";
	header('Content-Type: application/json; charset=utf-8');

	$curl = curl_init();

    $modeString = "osu";
    switch($mode){
        case 0:
            $modeString = "osu";
            break;
        case 1:
            $modeString = "taiko";
            break;
        case 2:
            $modeString = "fruits";
            break;
        case 3:
            $modeString = "mania";
            break;
    }

	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://osu.ppy.sh/api/v2/users/' . $userId . '/scores/recent?mode=' . $modeString . '&limit=10',
	  CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'Authorization: Bearer ' . $user['AccessToken']],
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

	$scores = json_decode($response);

	$stmt = $conn->prepare("SELECT `Score` FROM `ratings` WHERE `BeatmapID`=? AND `UserID`=?;");
	$stmt->bind_param("si", $bID, $userId);

	foreach ($scores as &$score) {
		$bID = $score->beatmap->id;
		$stmt->execute();
		$result = $stmt->get_result()->fetch_row()[0] ?? "-1";
		$score->rating = $result;
	}

	$response = json_encode(array_reverse($scores));

	echo $response;
