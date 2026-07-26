<?php
    require __DIR__ . '/../init.php';

    $targetUserID = $_GET['id'] ?? $userID ?? null;

    if (!$targetUserID) {
        die(json_encode(array("error" => "Invalid request")));
    }

    $query = "SELECT r.*, b.SetID, s.Artist, s.Title, b.DifficultyName FROM ratings r INNER JOIN beatmaps b ON r.BeatmapID = b.BeatmapID LEFT JOIN beatmapsets s ON b.SetID = s.SetID WHERE r.UserID = ? AND b.Blacklisted = 0";

    $year = $_GET["year"] ?? -1;
    $score = $_GET["score"] ?? -1;
    $min_score = $_GET["min_score"] ?? -1;
    $mode = $_GET["mode"] ?? -1;

    $types = "i";
    $params = [$targetUserID];

    if ($year != -1) {
        $query .= " AND YEAR(s.DateRanked) = ?";
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

    $response = array();
    while ($row = $result->fetch_assoc()) {
        $response[] = array(
            "SetID" => $row["SetID"],
            "BeatmapID" => $row["BeatmapID"],
            "Score" => $row["Score"],
            "Artist" => $row["Artist"],
            "Title" => $row["Title"],
            "Difficulty" => $row["DifficultyName"],
            "RatedAt" => $row["date"],
        );
    }

    if (sizeof($response) == 0) {
        echo json_encode(array("error" => "No ratings found"));
    } else {
        echo json_encode($response);
    }
?>