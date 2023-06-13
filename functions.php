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
			'y' => 'year',
			'm' => 'month',
			'w' => 'week',
			'd' => 'day',
			'h' => 'hour',
			'i' => 'minute',
			's' => 'second',
		);
		foreach ($string as $k => &$v) {
			if ($diff->$k) {
				$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
			} else {
				unset($string[$k]);
			}
		}

		if (!$full) $string = array_slice($string, 0, 1);
		return $string ? implode(', ', $string) . ' ago' : 'just now';
	}

	function GetUserNameFromId($id, $conn){
		$stmt = $conn->prepare("SELECT `Username` FROM `users` WHERE `UserID` = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows >= 1){
			$row = $result->fetch_row();
			$stmt->close();
			return $row[0];
		}

		$stmt = $conn->prepare("SELECT `Username` FROM `mappernames` WHERE `UserID` = ?");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$result = $stmt->get_result();
		if($result->num_rows >= 1){
			$row = $result->fetch_row();
			$stmt->close();
			return $row[0];
		}

		$username = GetUserDataOsuApi($id)["username"];

		$stmt = $conn->prepare("INSERT INTO `mappernames` VALUES (?, ?)");
		$stmt->bind_param("is", $id, $username);
		$stmt->execute();
		$stmt->close();

		return $username;
	}


function ParseOsuLinks($string) {
	  $pattern = '/(\d+):(\d{2}):(\d{3})\s*(\(((\d,?)+)\))?/';
	  $replacement = '<a class="osuTimestamp" href="osu://edit/$0">$0</a>';
	  return preg_replace($pattern, $replacement, $string);
	}

    function RenderRating($conn, $ratingRow) {
        $score = $ratingRow["Score"];
        $starString = "";

        for ($i = 0; $i < 5; $i++) {
            if ($i < $score) {
                if ($score - 0.5 == $i) {
                    $starString .= "<i class='star icon-star-half'></i>";
                } else {
                    $starString .= "<i class='star icon-star'></i>";
                }
            }
        }

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

        $backgroundStars = "<div class='starBackground'><i class='icon-star'></i><i class='icon-star'></i><i class='icon-star'></i><i class='icon-star'></i><i class='icon-star'></i></div>";

        $starString = "<div class='starRatingDisplay'>" . $backgroundStars . "<div class='starForeground'>" . $starString . "</div></div>";

        if ($hint == "" || !isset($hint))
            return $starString;

        $hint = htmlspecialchars($hint, ENT_COMPAT);

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
?>
