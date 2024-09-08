<?php
	require '../base.php';
	
	if ($loggedIn && $user["DoTrueRandom"] == 0){
	$isSupporter = GetOwnUserData($token)["is_supporter"];
	
		if ($isSupporter == 1){
			$object = GetRandomPlayedBeatmap($token);
		
			if (sizeof($object["beatmapsets"]) == 0){
                $stmt = $conn->prepare("SELECT `SetID` FROM `beatmaps` WHERE `Mode` = ? ORDER BY RAND() LIMIT 1;");
				$stmt->bind_param("i", $mode);
                $stmt->execute();
                $stmt->bind_result($result);
                $stmt->fetch();
                $stmt->close();
		
				siteRedirect("/mapset/" . strval($result));
			}
			
			// lol
			$setID = $object["beatmapsets"][rand(0,49)]["beatmaps"][0]["beatmapset_id"];
			
			if ($setID == NULL){
                $stmt = $conn->prepare("SELECT `SetID` FROM `beatmaps` WHERE `Mode` = ? ORDER BY RAND() LIMIT 1;");
                $stmt->bind_param("i", $mode);
				$stmt->execute();
                $stmt->bind_result($result);
                $stmt->fetch();
                $stmt->close();
		
				siteRedirect("/mapset/" . strval($result));
			}
			
			siteRedirect("/mapset/" . $setID);
		}
	
	}

    $stmt = $conn->prepare("SELECT `SetID` FROM `beatmaps` WHERE `Mode` = ? ORDER BY RAND() LIMIT 1;");
    $stmt->bind_param("i", $mode);
	$stmt->execute();
    $stmt->bind_result($result);
    $stmt->fetch();
    $stmt->close();

	if (!$result || !count($result)) {
		http_response_code(404);
		echo "No beatmaps";
	} else {
		siteRedirect("/mapset/" . strval($result));
	}
