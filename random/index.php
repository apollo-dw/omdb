<?php
	require '../connection.php';
	require '../userConnect.php';
	require '../functions.php';
	
	if ($loggedIn && $user["DoTrueRandom"] == 0){
	$isSupporter = GetOwnUserData($token)["is_supporter"];
	
		if ($isSupporter == 1){
			$object = GetRandomPlayedBeatmap($token);
		
			if (sizeof($object["beatmapsets"]) == 0){
				$result = $conn->query("SELECT `SetID` FROM `beatmaps` WHERE `Mode`='0' ORDER BY RAND() LIMIT 1;")->fetch_row()[0];
		
				siteRedirect("/mapset/" . $result);
			}
			
			// lol
			$setID = $object["beatmapsets"][rand(0,49)]["beatmaps"][0]["beatmapset_id"];
			
			if ($setID == NULL){
				$result = $conn->query("SELECT `SetID` FROM `beatmaps` WHERE `Mode`='0' ORDER BY RAND() LIMIT 1;")->fetch_row()[0];
		
				siteRedirect("/mapset/" . $result);
			}
			
			siteRedirect("/mapset/" . $setID);
		}
	
	}
	
	$result = $conn->query("SELECT `SetID` FROM `beatmaps` WHERE `Mode`='0' ORDER BY RAND() LIMIT 1;")->fetch_row();

	if (!$result || !count($result)) {
		http_response_code(404);
		echo "No beatmaps";
	} else {
		siteRedirect("/mapset/" . $result[0]);
	}
