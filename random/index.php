<?php
	require '../connection.php';
	require '../userConnect.php';
	require '../functions.php';
	
	if ($loggedIn && $user["DoTrueRandom"] == 0){
	$isSupporter = GetOwnUserData($token)["is_supporter"];
	
		if ($isSupporter == 1){
			$object = GetRandomPlayedBeatmap($token);
		
			if (sizeof($object["beatmapsets"]) == 0){
                $stmt = $conn->prepare("SELECT `SetID` FROM `beatmaps` WHERE `Mode` = '0' ORDER BY RAND() LIMIT 1");
                $stmt->execute();
                $stmt->bind_result($result);
                $stmt->fetch();
                $stmt->close();
		
				siteRedirect("/mapset/" . $result);
			}
			
			// lol
			$setID = $object["beatmapsets"][rand(0,49)]["beatmaps"][0]["beatmapset_id"];
			
			if ($setID == NULL){
                $stmt = $conn->prepare("SELECT `SetID` FROM `beatmaps` WHERE `Mode` = '0' ORDER BY RAND() LIMIT 1");
                $stmt->execute();
                $stmt->bind_result($result);
                $stmt->fetch();
                $stmt->close();
		
				siteRedirect("/mapset/" . $result);
			}
			
			siteRedirect("/mapset/" . $setID);
		}
	
	}

    $stmt = $conn->prepare("SELECT `SetID` FROM `beatmaps` WHERE `Mode` = '0' ORDER BY RAND() LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($result);
    $stmt->fetch();
    $stmt->close();

	if (!$result || !count($result)) {
		http_response_code(404);
		echo "No beatmaps";
	} else {
		siteRedirect("/mapset/" . $result[0]);
	}
