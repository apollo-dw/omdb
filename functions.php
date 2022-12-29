<?php
    include_once 'sensitiveStrings.php';
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
		$result = $conn->query("SELECT `Username` FROM `users` WHERE `UserID`='{$id}';");
		if($result->num_rows >= 1){
			return $result->fetch_row()[0];
		}
		
		$result = $conn->query("SELECT `Username` FROM `mappernames` WHERE `UserID`='{$id}';");
		if($result->num_rows >= 1){
			return $result->fetch_row()[0];
		}
		
		$username = GetUserDataOsuApi($id)["username"];
		$conn->query("INSERT INTO `mappernames` VALUES ('{$id}', '{$username}');");
		
		return $username;
	}
	
	function ParseOsuLinks($string) {
	  $pattern = '/(\d+):(\d{2}):(\d{3})\s*(\(((\d,?)+)\))?/';
	  $replacement = '<a class="osuTimestamp" href="osu://edit/$0">$0</a>';
	  return preg_replace($pattern, $replacement, $string);
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
?>