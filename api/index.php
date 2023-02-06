<?php
    require '../base.php';
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

    if($result == null)
        die(json_encode(array("error" => "Invalid api key")));

    $row = $result->fetch_assoc();
    $userID = $row["UserID"];

    if ($uri[2] == "set") {
        $setID = $uri[3];
        $stmt = $conn->prepare("SELECT * FROM beatmaps WHERE SetID = ? AND mode = '0' ORDER BY SR DESC;");
        $stmt->bind_param("i", $setID);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response[] = array(
                "BeatmapID" => $row["BeatmapID"],
                "Artist" => $row["Artist"],
                "Title" => $row["Title"],
                "Difficulty" => $row["DifficultyName"],
                "ChartRank" => $row["ChartRank"],
                "ChartYearRank" => $row["ChartYearRank"],
                "RatingCount" => $row["RatingCount"],
                "WeightedAvg" => $row["WeightedAvg"],
            );
        }

        if (sizeof($response) == 0)
            $response = array("error" => "Mapset not found");
    } elseif ($uri[2] == "beatmap") {
        $beatmapID = $uri[3];
        $stmt = $conn->prepare("SELECT * FROM beatmaps WHERE BeatmapID = ? AND mode = '0' ORDER BY SR DESC;");
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
        }

        if (sizeof($response) == 0)
            $response = array("error" => "Difficulty not found");
    } elseif ($uri[2] == "user") {
        $userID = $uri[3];
        if ($uri[4] == "ratings") {
            // monkas
            $base_query = "SELECT r.*, b.SetID, b.Artist, b.Title, b.DifficultyName FROM ratings r";
            $join_query = "INNER JOIN beatmaps b ON r.BeatmapID = b.BeatmapID";
            $where_query = "WHERE r.UserID = ?";

            $year = $_GET["year"] ?? -1;
            $score = $_GET["score"] ?? -1;
            if ($year != -1) {
                $year_query = "AND YEAR(b.DateRanked) = ?";
                $query = $base_query . " " . $join_query . " " . $where_query . " " . $year_query;
            } else {
                $query = $base_query . " " . $join_query . " " . $where_query;
            }
            if ($score != -1) {
                $score_query = "AND `Score`=?";
                $query .= " " . $score_query;
            }
            $query .= " ORDER BY r.RatingID;";

            $stmt = $conn->prepare($query);
            $types = "i";
            $params = [$userID];
            if ($year != -1) {
                $types .= "i";
                $params[] = $year;
            }
            if ($score != -1) {
                $types .= "d";
                $params[] = $score;
            }
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