<?php
    require '../base.php';
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");
    header('Content-Type: application/json; charset=utf-8');


    $response = array();
    $args = array_keys($_GET);
    $apiKey = $_GET["?key"] ?? "-1";

    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = explode( '/', $uri );

    if (sizeof($args) == 0)
        die(json_encode(array("error" => "Invalid request")));
    if ($apiKey == -1)
        die(json_encode(array("error" => "Invalid request - missing api key")));

    $stmt = $conn->prepare("SELECT * FROM `apikeys` WHERE ApiKey = ?;");
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 0)
        die(json_encode(array("error" => "Invalid api key")));

    $row = $result->fetch_assoc();
    $userID = $row["UserID"];

    if ($uri[2] == "set") {
        $setID = $uri[3];
        $stmt = $conn->prepare("SELECT * FROM beatmaps WHERE SetID = ? ORDER BY SR DESC;");
        $stmt->bind_param("i", $setID);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
			$beatmapID = $row["BeatmapID"];
			
            $stmt = $conn->prepare("SELECT Score FROM ratings WHERE BeatmapID = ? AND UserID = ?");
            $stmt->bind_param("ii", $beatmapID, $userID);
            $stmt->execute();
            $ratingResult = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $rating = null;
            if ($ratingResult != null)
                $rating = $ratingResult["Score"];
			
			$stmt = $conn->prepare("SELECT Score, COUNT(*) as Count FROM ratings WHERE BeatmapID = ? GROUP BY Score ORDER BY Score");
			$stmt->bind_param("i", $beatmapID);
			$stmt->execute();
			$ratingsResult = $stmt->get_result();
			$stmt->close();

			$ratingsCounts = array();
			while ($ratingRow = $ratingsResult->fetch_assoc()) {
				$ratingsCounts[$ratingRow["Score"]] = $ratingRow["Count"];
			}

            $response[] = array(
                "BeatmapID" => $row["BeatmapID"],
                "Artist" => $row["Artist"],
                "Title" => $row["Title"],
                "Difficulty" => $row["DifficultyName"],
                "ChartRank" => $row["ChartRank"],
                "ChartYearRank" => $row["ChartYearRank"],
                "RatingCount" => $row["RatingCount"],
                "WeightedAvg" => $row["WeightedAvg"],
				"OwnRating" => $rating,
				"Ratings" => $ratingsCounts,
            );
        }

        if (sizeof($response) == 0)
            $response = array("error" => "Mapset not found");
    } elseif ($uri[2] == "beatmap") {
        $beatmapID = $uri[3];
        $stmt = $conn->prepare("SELECT * FROM beatmaps WHERE BeatmapID = ? ORDER BY SR DESC;");
        $stmt->bind_param("i", $beatmapID);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();;
        if ($result != null) {
            $response = array(
                "SetID" => $result["SetID"],
                "Artist" => $result["Artist"],
                "Title" => $result["Title"],
                "Difficulty" => $result["DifficultyName"],
                "ChartRank" => $result["ChartRank"],
                "ChartYearRank" => $result["ChartYearRank"],
                "RatingCount" => $result["RatingCount"],
                "WeightedAvg" => $result["WeightedAvg"],
            );
			
            $stmt = $conn->prepare("SELECT Score FROM ratings WHERE BeatmapID = ? AND UserID = ?");
            $stmt->bind_param("ii", $beatmapID, $userID);
            $stmt->execute();
            $ownRating = $stmt->get_result()->fetch_assoc();
            $stmt->close();
			
			$response["OwnRating"] = $ownRating["Score"] ?? null;
			
			$stmt = $conn->prepare("SELECT Score, COUNT(*) as Count FROM ratings WHERE BeatmapID = ? GROUP BY Score ORDER BY Score");
			$stmt->bind_param("i", $beatmapID);
			$stmt->execute();
			$ratingsResult = $stmt->get_result();
			$stmt->close();
			$ratingsCounts = array();
			while ($ratingRow = $ratingsResult->fetch_assoc()) {
				$ratingsCounts[$ratingRow["Score"]] = $ratingRow["Count"];
			}
			
			$response["Ratings"] = $ratingsCounts;
			$stmt = $conn->prepare("
                                SELECT d.Name
                                FROM descriptor_votes 
                                JOIN descriptors d on descriptor_votes.DescriptorID = d.DescriptorID
                                WHERE BeatmapID = ?
                                GROUP BY d.DescriptorID
                                HAVING SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) > (SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END) + 0)
                                ORDER BY (SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) - SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END)) DESC, d.DescriptorID
                                LIMIT 5;");
            $stmt->bind_param("i", $beatmapID);
            $stmt->execute();
            $descriptorResult = $stmt->get_result()->fetch_all();
            $stmt->close();

            $descriptors = implode(', ', array_column($descriptorResult, 0));
            $response["Descriptors"] = $descriptors;
			
			$stmt = $conn->prepare("SELECT bn.NominatorID as UserID, m.Username as Username FROM beatmapset_nominators bn 
                                          JOIN mappernames m ON bn.NominatorID = m.UserID WHERE bn.SetID = ?;");
            $stmt->bind_param("i", $result["SetID"]);
            $stmt->execute();
            $nominatorResult = $stmt->get_result();

            $nominators = [];
            while($nominator = $nominatorResult->fetch_assoc()) {
                $nominators[] = array(
                    "UserID" => $nominator["UserID"],
                    "Username" => $nominator["Username"]
                );
            }
            $response["Nominators"] = $nominators;
        }

        if (sizeof($response) == 0)
            $response = array("error" => "Difficulty not found");
    } elseif ($uri[2] == "user") {
        $userID = $uri[3];
        if ($uri[4] == "ratings") {
            $query = "SELECT r.*, b.SetID, s.Artist, s.Title, b.DifficultyName FROM ratings r INNER JOIN beatmaps b ON r.BeatmapID = b.BeatmapID LEFT JOIN beatmapsets s ON b.SetID = s.SetID WHERE r.UserID = ?";

            $year = $_GET["year"] ?? -1;
            $score = $_GET["score"] ?? -1;
			$min_score = $_GET["min_score"] ?? -1;
            $mode = $_GET["mode"] ?? -1;

            $types = "i";
            $params = [$userID];

            if ($year != -1) {
                $query .= " AND YEAR(b.DateRanked) = ?";
                $types .= "i";
                $params[] = $year;
            }

            if ($score != -1) {
                $query .= " AND `Score`=?";
                $types .= "d";
                $params[] = $score;
            }
			
			if ($min_score != -1) {
                $query .= " AND `Score` >= ?";
                $types .= "d";
                $params[] = $min_score;
            }

            if ($mode != -1) {
                $query .= " AND `Mode`=?";
                $types .= "i";
                $params[] = $mode;
            }

            $query .= " ORDER BY r.RatingID;";

            $stmt = $conn->prepare($query);

            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $response[] = array(
                    "SetID" => $row["SetID"],
                    "BeatmapID" => $row["BeatmapID"],
                    "Score" => $row["Score"],
                    "Artist" => $row["Artist"],
                    "Title" => $row["Title"],
                    "Difficulty" => $row["DifficultyName"],
                );
            }
        }

        if (sizeof($response) == 0)
            $response = array("error" => "Invalid request");
    } elseif ($uri[2] == "rate"){
        $beatmapID = $uri[3];
        if (isset($_GET["score"])){
            $score = $_GET["score"];
            $result = SubmitRating($conn, $beatmapID, $userID, $score);

            if ($result)
                $response = array("success" => "rating submitted");
            else
                $response = array("error" => "rating not submitted");
        }
    } else {
        $response = array("error" => "Invalid request");
    }

    echo json_encode($response);