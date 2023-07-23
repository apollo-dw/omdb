<?php
    include_once 'sensitiveStrings.php';

	/**
	 * Sends a redirect header pointed to the given relative location (using the
	 * environment variable PUBLIC_URL to determine the host), and exits.
	 */
	function siteRedirect(string $path = "/") {
		header("Location: " . relUrl($path));
		exit();
	}

	/**
	 * Returns the requested relative location (using the environment variable
	 * PUBLIC_URL to determine the host) as a string.
	 */
	function relUrl(string $path = "/") {
		return getenv("PUBLIC_URL") . $path;
	}

	function GetBeatmapDataOsuApi(string $token, int $id){
		$curl = curl_init();
	
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://osu.ppy.sh/api/v2/beatmaps/' . strval($id),
		  CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'Authorization: Bearer ' . $token],
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

		return json_decode($response, true);
	}
	
	function GetOwnUserData(string $token){
		$curl = curl_init();
	
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://osu.ppy.sh/api/v2/me/',
		  CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'Authorization: Bearer ' . $token],
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

		return json_decode($response, true);
	}
	
	function GetRandomPlayedBeatmap(string $token){
		$curl = curl_init();
		
		$sortOrder = array("_asc", "_desc");
		$sortFields = array("artist", "creator", "ranked", "title", "difficulty");
		$sortString = $sortFields[array_rand($sortFields)] . $sortOrder[array_rand($sortOrder)];
		
		$randLetter = substr(md5(microtime()),rand(0,26),1);
		
		$first_date = "2007-08-14 10:21:02";
		$second_date = date('Y-m-d');
		$first_time = strtotime($first_date);
		$second_time = strtotime($second_date);
		$rand_time = rand($first_time, $second_time);
		$randDate = date('Y-m-d', $rand_time);
		
		$queryUrl = "https://osu.ppy.sh/api/v2/beatmapsets/search?played=played&status=ranked&sort={$sortString}&q={$randLetter}%20ranked>{$randDate}&m=0";
		
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $queryUrl,
		  CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json', 'Authorization: Bearer ' . $token],
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

		return json_decode($response, true);
	}
	
	function GetUserDataOsuApi(int $id){
        global $apiV1Key;
		$curl = curl_init();
	
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://osu.ppy.sh/api/get_user?k={$apiV1Key}&u=" . strval($id),
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

		return json_decode($response, true)[0];
	}
	
	function GetHumanTime($datetime, $full = false) {
		$now = new DateTime;
		$ago = new DateTime($datetime);
		$diff = $now->diff($ago);

		$diff->w = floor($diff->d / 7);
		$diff->d -= $diff->w * 7;

		$string = array(
			'y' => 'yr',
			'm' => 'mo',
			'w' => 'w',
			'd' => 'd',
			'h' => 'h',
			'i' => 'm',
			's' => 's',
		);
		foreach ($string as $k => &$v) {
			if ($diff->$k) {
				$v = $diff->$k . $v;
			} else {
				unset($string[$k]);
			}
		}

		if (!$full) $string = array_slice($string, 0, 1);
		return $string ? implode(', ', $string) . ' ago' : 'now';
	}

	function GetUserNameFromId($id, $conn){
		static $cache = array();
		if (array_key_exists($id, $cache))
			return $cache[$id];

		$stmt = $conn->prepare("SELECT `Username` FROM `users` WHERE `UserID` = ? UNION SELECT `Username` FROM `mappernames` WHERE `UserID` = ?");
		$stmt->bind_param("ii", $id, $id);
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result->num_rows >= 1) {
			$row = $result->fetch_row();
			$stmt->close();
			if (!is_null($row[0])) {
				$cache[$id] = $row[0];
				return $row[0];
			}
		}

		$username = GetUserDataOsuApi($id)["username"];

		$stmt = $conn->prepare("REPLACE INTO `mappernames` VALUES (?, ?)");
		$stmt->bind_param("is", $id, $username);
		$stmt->execute();
		$stmt->close();

		$cache[$id] = $username;
		return $username;
	}

	function ParseCommentLinks($conn, $string) {
		$pattern = '/(\d+):(\d{2}):(\d{3})\s*(\(((\d,?)+)\))?/';
		$replacement = '<a class="osuTimestamp" href="osu://edit/$0">$0</a>';
		$string = preg_replace($pattern, $replacement, $string);

		$pattern = '/https:\/\/osu\.ppy\.sh\/(?P<endpoint>beatmapsets|beatmaps|b|s)\/(?P<id1>\d+)(?:\S+)?(?:#(osu|taiko|fruits|mania)\/(?P<id2>\d+))?/';

		$string = preg_replace_callback($pattern, function ($matches) {
			$setID = $matches['id1'];
			$mapID = $matches['id2'] ?? '';

			if ($mapID != '')
				return '<a href="' . $matches[0] . '">/b/' . $mapID . '</a>';
			else
				return '<a href="' . $matches[0] . '">/s/' . $setID . '</a>';

		}, $string);

		$pattern = '/https:\/\/omdb\.nyahh\.net\/mapset\/(\d+)/';
		$string = preg_replace_callback($pattern, function ($matches) use ($conn) {
			$setID = $matches[1];

			$stmt = $conn->prepare("SELECT Artist, Title, SetCreatorID FROM beatmaps WHERE SetID = ? LIMIT 1;");
			$stmt->bind_param("i", $setID);
			$stmt->execute();
			$beatmap = $stmt->get_result()->fetch_assoc();

			if (isset($beatmap)){
				$mapper = GetUserNameFromId($beatmap["SetCreatorID"], $conn);
				return "<a href='{$matches[0]}'> {$beatmap["Artist"]} - {$beatmap["Title"]} ({$mapper})</a>";
			}

			return $matches[0];
		}, $string);

		return $string;
	}

	function RenderRating($rating){
		$starString = "";
		for ($i = 0; $i < 5; $i++) {
			if ($i < $rating) {
				if ($rating - 0.5 == $i) {
					$starString .= "<i class='star icon-star-half'></i>";
				} else {
					$starString .= "<i class='star icon-star'></i>";
				}
			}
		}
		$backgroundStars = "<div class='starBackground'><i class='icon-star'></i><i class='icon-star'></i><i class='icon-star'></i><i class='icon-star'></i><i class='icon-star'></i></div>";
		return "<div class='starRatingDisplay'>" . $backgroundStars . "<div class='starForeground'>" . $starString . "</div></div>";
	}

    function RenderUserRating($conn, $ratingRow) {
        $score = $ratingRow["Score"];

		$stmt = $conn->prepare("SELECT * FROM `users` WHERE `UserID` = ?");
		$stmt->bind_param("i", $ratingRow["UserID"]);
		$stmt->execute();
		$result = $stmt->get_result();
		$user = $result->fetch_assoc();
		$stmt->close();

        switch($score){
            case 0:
                $hint = $user["Custom00Rating"];
                break;
            case 0.5:
                $hint = $user["Custom05Rating"];
                break;
            case 1:
                $hint = $user["Custom10Rating"];
                break;
            case 1.5:
                $hint = $user["Custom15Rating"];
                break;
            case 2.0:
                $hint = $user["Custom20Rating"];
                break;
            case 2.5:
                $hint = $user["Custom25Rating"];
                break;
            case 3.0:
                $hint = $user["Custom30Rating"];
                break;
            case 3.5:
                $hint = $user["Custom35Rating"];
                break;
            case 4.0:
                $hint = $user["Custom40Rating"];
                break;
            case 4.5:
                $hint = $user["Custom45Rating"];
                break;
            case 5.0:
                $hint = $user["Custom50Rating"];
                break;
        }

		$starString = RenderRating($score);
		if ($hint == "" || !isset($hint))
			return $starString;

        $hint = htmlspecialchars($hint, ENT_QUOTES);
        echo "<span title='{$hint}' style='border-bottom:1px dotted white;'>{$starString}</span>";
    }

	function CalculatePearsonCorrelation($x, $y) {
		$n = count($x);
		$sum_x = array_sum($x);
		$sum_y = array_sum($y);
		$sum_x_sq = array_sum(array_map(function($x) { return pow($x, 2); }, $x));
		$sum_y_sq = array_sum(array_map(function($y) { return pow($y, 2); }, $y));
		$sum_xy = 0;
		for ($i = 0; $i < $n; $i++) {
			$sum_xy += $x[$i] * $y[$i];
		}
		$numerator = $n * $sum_xy - $sum_x * $sum_y;
		$denominator = sqrt(($n * $sum_x_sq - pow($sum_x, 2)) * ($n * $sum_y_sq - pow($sum_y, 2)));
		if ($denominator == 0) {
			return -1;
		}
		return $numerator / $denominator;
	}

    function SubmitRating($conn, $beatmapID, $userID, $score): bool
    {
        $validRatings = array(-2, 0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5);
        if (!in_array($score, $validRatings))
            return false;

        $stmt = $conn->prepare("SELECT * FROM `beatmaps` WHERE `beatmapID` = ? AND `Blacklisted`='0';");
        $stmt->bind_param("i", $beatmapID);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows != 1) return false;

        $stmt = $conn->prepare("SELECT * FROM `users` WHERE `UserID` = ?;");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows != 1) return false;

        if($score == -2){
            $stmt = $conn->prepare("DELETE FROM `ratings` WHERE `beatmapID` = ? AND `UserID` = ?;");
            $stmt->bind_param("ii", $beatmapID, $userID);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("SELECT * FROM `ratings` WHERE `beatmapID` = ? AND `UserID` = ?;");
            $stmt->bind_param("ii", $beatmapID, $userID);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows == 1){
                $stmt = $conn->prepare("UPDATE `ratings` SET `Score` = ? WHERE `beatmapID` = ? AND `UserID` = ?;");
                $stmt->bind_param("dii", $score, $beatmapID, $userID);
                $stmt->execute();
            }else{
                $stmt = $conn->prepare("INSERT INTO `ratings` (beatmapID, UserID, Score, date) VALUES (?, ?, ?, CURRENT_TIMESTAMP);");
                $stmt->bind_param("iid", $beatmapID, $userID, $score);
                $stmt->execute();
            }
        }

        return true;
    }

	function getGenre($number) {
		switch ($number) {
			case 2:
				return "Video Game";
			case 3:
				return "Anime";
			case 4:
				return "Rock";
			case 5:
				return "Pop";
			case 6:
				return "Other Genre";
			case 7:
				return "Novelty";
			case 9:
				return "Hip Hop";
			case 10:
				return "Electronic";
			case 11:
				return "Metal";
			case 12:
				return "Classical";
			case 13:
				return "Folk";
			case 14:
				return "Jazz";
			default:
				return null;
		}
	}

	function getLanguage($number) {
		switch ($number) {
			case 2:
				return "English";
			case 3:
				return "Japanese";
			case 4:
				return "Chinese";
			case 5:
				return "Instrumental";
			case 6:
				return "Korean";
			case 7:
				return "French";
			case 8:
				return "German";
			case 9:
				return "Swedish";
			case 10:
				return "Spanish";
			case 11:
				return "Italian";
			case 12:
				return "Russian";
			case 13:
				return "Polish";
			case 14:
				return "Other Language";
		}
	}

	function RenderBeatmapCreators($beatmapID, $conn) {
		$stmt = $conn->prepare("SELECT `CreatorID` FROM `beatmap_creators` WHERE BeatmapID = ?");
		$stmt->bind_param('i', $beatmapID);
		$stmt->execute();
		$creators = $stmt->get_result();

		$creatorCount = $creators->num_rows;
		$index = 0;

		while ($creator = $creators->fetch_assoc()){
			$creatorName = GetUserNameFromId($creator['CreatorID'], $conn);
			echo "<a href='/profile/{$creator['CreatorID']}'>{$creatorName}</a><a href='https://osu.ppy.sh/u/{$creator['CreatorID']}' target='_blank' rel='noopener noreferrer'><i class='icon-external-link' style='font-size:10px;'></i></a>";

			$index++;
			if ($index < $creatorCount - 1)
				echo ", ";
			elseif ($index == $creatorCount - 1)
				echo " and ";
		}
	}
?>
