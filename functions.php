<?php
    include_once 'sensitiveStrings.php';
	include_once 'functions/bbcode.php';
	include_once 'functions/access.php';

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
		return "https://omdb.nyahh.net" . $path;
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
		
		if ($response === "[]")
			return;

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

		$stmt = $conn->prepare("SELECT `Username` FROM `mappernames` WHERE `UserID` = ?");
		$stmt->bind_param("i", $id);
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
		
		try {
        $userData = GetUserDataOsuApi($id);
		if (!$userData)
			return "ID => " . strval($id);
		$username = $userData["username"];
        $country = $userData["country"];

		$stmt = $conn->prepare("REPLACE INTO `mappernames` VALUES (?, ?, ?)");
		$stmt->bind_param("iss", $id, $username, $country);
		$stmt->execute();
		$stmt->close();
		} catch (Exception $e) {
			unset($e);
		}
		
		$cache[$id] = $username;
		return $username;
	}
	
function getFullCountryName($code) {
        $countries = array
        (
            'AF' => 'Afghanistan',
            'AX' => 'Aland Islands',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AS' => 'American Samoa',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AQ' => 'Antarctica',
            'AG' => 'Antigua And Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AW' => 'Aruba',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia And Herzegovina',
            'BW' => 'Botswana',
            'BV' => 'Bouvet Island',
            'BR' => 'Brazil',
            'IO' => 'British Indian Ocean Territory',
            'BN' => 'Brunei Darussalam',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'KY' => 'Cayman Islands',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CX' => 'Christmas Island',
            'CC' => 'Cocos (Keeling) Islands',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CG' => 'Congo',
            'CD' => 'Congo, Democratic Republic',
            'CK' => 'Cook Islands',
            'CR' => 'Costa Rica',
            'CI' => 'Cote D\'Ivoire',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'ET' => 'Ethiopia',
            'FK' => 'Falkland Islands (Malvinas)',
            'FO' => 'Faroe Islands',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'GF' => 'French Guiana',
            'PF' => 'French Polynesia',
            'TF' => 'French Southern Territories',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GG' => 'Guernsey',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HM' => 'Heard Island & Mcdonald Islands',
            'VA' => 'Holy See (Vatican City State)',
            'HN' => 'Honduras',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran, Islamic Republic Of',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IM' => 'Isle Of Man',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JE' => 'Jersey',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KR' => 'Korea',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Lao People\'s Democratic Republic',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libyan Arab Jamahiriya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MO' => 'Macao',
            'MK' => 'Macedonia',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MQ' => 'Martinique',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'YT' => 'Mayotte',
            'MX' => 'Mexico',
            'FM' => 'Micronesia, Federated States Of',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MS' => 'Montserrat',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'AN' => 'Netherlands Antilles',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'NU' => 'Niue',
            'NF' => 'Norfolk Island',
            'MP' => 'Northern Mariana Islands',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestinian Territory, Occupied',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PN' => 'Pitcairn',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'PR' => 'Puerto Rico',
            'QA' => 'Qatar',
            'RE' => 'Reunion',
            'RO' => 'Romania',
            'RU' => 'Russian Federation',
            'RW' => 'Rwanda',
            'BL' => 'Saint Barthelemy',
            'SH' => 'Saint Helena',
            'KN' => 'Saint Kitts And Nevis',
            'LC' => 'Saint Lucia',
            'MF' => 'Saint Martin',
            'PM' => 'Saint Pierre And Miquelon',
            'VC' => 'Saint Vincent And Grenadines',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'Sao Tome And Principe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'GS' => 'South Georgia And Sandwich Isl.',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SJ' => 'Svalbard And Jan Mayen',
            'SZ' => 'Swaziland',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SY' => 'Syrian Arab Republic',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TT' => 'Trinidad And Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TC' => 'Turks And Caicos Islands',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'UM' => 'United States Outlying Islands',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VE' => 'Venezuela',
            'VN' => 'Viet Nam',
            'VG' => 'Virgin Islands, British',
            'VI' => 'Virgin Islands, U.S.',
            'WF' => 'Wallis And Futuna',
            'EH' => 'Western Sahara',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
        );

        return $countries[$code];
    }

	function ParseCommentLinks($conn, $string) {
		$string = bbcode_to_html($string);

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

			$stmt = $conn->prepare("SELECT Artist, Title, CreatorID FROM beatmaps b JOIN beatmapsets s ON b.SetID = s.SetID WHERE b.SetID = ? LIMIT 1;");
			$stmt->bind_param("i", $setID);
			$stmt->execute();
			$beatmap = $stmt->get_result()->fetch_assoc();

			if (isset($beatmap)){
				$mapper = GetUserNameFromId($beatmap["CreatorID"], $conn);
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

        $stmt = $conn->prepare("SELECT * FROM `beatmaps` WHERE `beatmapID` = ?;");
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

	function getModeIcon($mode) {
		switch ($mode) {
			case 0:
				return "<div class='ruleset-icon osu'></div>";
			case 1:
				return "<div class='ruleset-icon taiko'></div>";
			case 2:
				return "<div class='ruleset-icon catch'></div>";
			case 3:
				return "<div class='ruleset-icon mania'></div>";
			default:
				return "";
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

	function getListItemDisplayInformation($listItem, $conn)
	{
		$imageUrl = "";
		$title = "";
		$linkUrl = "";

		switch ($listItem["Type"]) {
			case "person":
				$username = GetUserNameFromId($listItem["SubjectID"], $conn);
				if ($username == "")
					die(json_encode(array("error" => "user not found")));

				$imageUrl = "https://s.ppy.sh/a/" . $listItem["SubjectID"];
				$title = $username;
				$linkUrl = "/profile/?id=" . $listItem["SubjectID"];

				break;
			case "beatmap":
				$stmt = $conn->prepare("SELECT s.SetID, s.Artist, s.Title, b.DifficultyName
                        FROM `beatmapsets` s
                        INNER JOIN `beatmaps` b ON s.SetID = b.SetID
                        WHERE b.BeatmapID = ?;");
				$stmt->bind_param("i", $listItem["SubjectID"]);
				$stmt->execute();
				$result = $stmt->get_result();
				if ($result->num_rows != 1)
					die(json_encode(array("error" => "beatmap not found")));
				$map = $result->fetch_assoc();

				$imageUrl = "https://b.ppy.sh/thumb/" . $map["SetID"] . "l.jpg";
				$title = "{$map["Artist"]} - {$map["Title"]} [{$map["DifficultyName"]}]";
				$linkUrl = "/mapset/" . $map["SetID"];

				break;
			case "beatmapset":
				$stmt = $conn->prepare("SELECT Artist, Title FROM `beatmapsets` WHERE `SetID` = ? LIMIT 1;");
				$stmt->bind_param("i", $listItem["SubjectID"]);
				$stmt->execute();
				$result = $stmt->get_result();
				if ($result->num_rows != 1)
					die(json_encode(array("error" => "set not found")));

				$set = $result->fetch_assoc();
				$imageUrl = "https://b.ppy.sh/thumb/" . $listItem["SubjectID"] . "l.jpg";
				$title = "{$set["Artist"]} - {$set["Title"]}";
				$linkUrl = "/mapset/" . $listItem["SubjectID"];

				break;
		}

		return [$imageUrl, $title, $linkUrl];
	}
	
	function RenderLocalTime($time) { ?>
		<script type="text/javascript">
			var myDate = new Date('<?php echo $time; ?>')
			document.write(myDate.toLocaleString())
		</script>
	<?php }
?>
