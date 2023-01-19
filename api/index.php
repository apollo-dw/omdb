<?php
    require '../base.php';

    $args = array_keys($_GET);
    header('Content-Type: application/json; charset=utf-8');

    $response = array();

    if ($args[0] == "set") {
        $setID = $args[1];
        $result = $conn->query("SELECT * FROM `beatmaps` WHERE `SetID`='{$setID}' AND `mode`='0' ORDER BY `SR` DESC;");
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
    } elseif ($args[0] == "beatmap") {
        $beatmapID = $args[1];
        $result = $conn->query("SELECT * FROM `beatmaps` WHERE `BeatmapID`='{$beatmapID}' AND `mode`='0' ORDER BY `SR` DESC;")->fetch_assoc();
        if ($result != null) {
            $response = array(
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
    } elseif ($args[0] == "user") {
        $userID = $args[1];
        if ($args[2] == "ratings") {
            // monkas
            $base_query = "SELECT r.* FROM ratings r";
            $join_query = "INNER JOIN beatmaps b ON r.BeatmapID = b.BeatmapID";
            $where_query = "WHERE r.UserID = ?";

            $year = $_GET["year"] ?? -1;
            $score = $_GET["score"] ?? -1;
            if ($year != -1) {
                $year_query = "AND YEAR(b.DateRanked) = ?";
                $query = $base_query . " " . $join_query . " " . $where_query . " " . $year_query;
            } else {
                $query = $base_query . " " . $where_query;
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
                $types .= "i";
                $params[] = $score;
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $response[] = array(
                    "BeatmapID" => $row["BeatmapID"],
                    "Score" => $row["Score"],
                );
            }
        }

        if (sizeof($response) == 0)
            $response = array("error" => "Invalid request");
    } else {
        $response = array("error" => "Invalid request");
    }

    echo json_encode($response);