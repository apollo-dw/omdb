<?php
	require '../connection.php';
	require '../userConnect.php';
	require '../functions.php';
	
	if ($loggedIn){
	$isSupporter = GetOwnUserData($token)["is_supporter"];
	
		if ($isSupporter == 1){
			$object = GetRandomPlayedBeatmap($token);
		
			if (sizeof($object["beatmapsets"]) == 0){
				$result = $conn->query("SELECT `SetID` FROM `beatmaps` WHERE `Mode`='0' ORDER BY RAND() LIMIT 1;")->fetch_row()[0];
		
				header("Location: https://omdb.nyahh.net/mapset/" . $result);
				exit();
			}
			
			// lol
			$setID = $object["beatmapsets"][rand(0,49)]["beatmaps"][0]["beatmapset_id"];
			
			if ($setID == NULL){
				$result = $conn->query("SELECT `SetID` FROM `beatmaps` WHERE `Mode`='0' ORDER BY RAND() LIMIT 1;")->fetch_row()[0];
		
				header("Location: https://omdb.nyahh.net/mapset/" . $result);
				exit();
			}
			
			header("Location: https://omdb.nyahh.net/mapset/" . $setID);
			exit();
		}
	
	}
	
	$result = $conn->query("SELECT `SetID` FROM `beatmaps` WHERE `Mode`='0' ORDER BY RAND() LIMIT 1;")->fetch_row()[0];
	
	header("Location: https://omdb.nyahh.net/mapset/" . $result);
	exit();