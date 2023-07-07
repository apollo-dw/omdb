<?php
    function wilson_lower_bound($positive_ratings, $total_ratings) {
        if ($total_ratings == 0) {
            return 0;
        }

        $z = 1.6448536269514729; # Equivalent to 0.9 confidence
        $phat = $positive_ratings / $total_ratings;
        return ($phat + $z * $z / (2 * $total_ratings) - $z * sqrt(($phat * (1 - $phat) + $z * $z / (4 * $total_ratings)) / $total_ratings)) / (1 + $z * $z / $total_ratings);
    }

    function RetrieveRecommendations($conn, $userID){
        $sql = "SELECT IF(user1_id = ?, user2_id, user1_id) AS correlated_user, correlation FROM user_correlations
                WHERE ? IN (user1_id, user2_id) AND correlation > 0.25 ORDER BY correlation DESC;";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userID, $userID);
        $stmt->execute();

        $result = $stmt->get_result();
        if($result->num_rows == 0)
            return (array("error" => "No correlated users"));

        $correlated_users = [];
        while ($row = $result->fetch_assoc()) {
            $correlated_users[] = $row;
        }
        $stmt->close();

        $correlated_ids = implode(', ', array_column($correlated_users, 'correlated_user'));
        $sql = "SELECT r.BeatmapID FROM ratings r
                JOIN beatmaps b ON r.BeatmapID = b.BeatmapID WHERE r.UserID IN ($correlated_ids)
                AND r.BeatmapID NOT IN (SELECT BeatmapID FROM ratings WHERE UserID = ?)
                AND b.CreatorID <> ? AND b.SetCreatorID <> ?
                GROUP BY r.BeatmapID
                HAVING COUNT(DISTINCT CASE WHEN r.UserID IN ($correlated_ids) THEN r.UserID END) > 5
                AND AVG(CASE WHEN r.UserID IN ($correlated_ids) THEN r.Score END) > 2.5;";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iii', $userID, $userID, $userID);
        $stmt->execute();

        $result = $stmt->get_result();
        $rated_beatmaps = [];
        while ($row = $result->fetch_assoc()) {
            $rated_beatmaps[] = $row['BeatmapID'];
        }
        $stmt->close();

        $recommendation_scores = [];
        foreach ($rated_beatmaps as $beatmap_id) {
            $sql = "SELECT Score FROM ratings WHERE BeatmapID = ? AND UserID IN ($correlated_ids);";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $beatmap_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $ratings = $result->fetch_all(MYSQLI_NUM);

            $positive_ratings = count(array_filter($ratings, function ($r) {
                return floatval($r[0]) > 3.0;
            }));

            $sql = "SELECT COUNT(*) FROM ratings WHERE BeatmapID = ?;";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $beatmap_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $total_ratings = $result->fetch_row()[0];

            $predicted_rating = wilson_lower_bound($positive_ratings, $total_ratings);

            $recommendation_scores[$beatmap_id] = $predicted_rating;
        }

        arsort($recommendation_scores);
        $sorted_recommendations = array_slice($recommendation_scores, 0, 50, true);

        $beatmap_details_array = [];
        foreach ($sorted_recommendations as $beatmap_id => $score) {
            $sql = "SELECT artist, title, difficultyname, setid, SR, DateRanked, CreatorID FROM beatmaps WHERE BeatmapID = ?;";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $beatmap_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $beatmap_details = $result->fetch_row();

            if ($beatmap_details) {
                list($artist, $title, $difficultyname, $setid, $sr, $date, $creatorid) = $beatmap_details;
                $beatmap_details_array[] = [
                    'BeatmapID' => $beatmap_id,
                    'SetID' => $setid,
                    'Artist' => $artist,
                    'Title' => $title,
                    'DifficultyName' => $difficultyname,
                    'SR' => $sr,
                    'DateRanked' => $date,
                    'CreatorID' => $creatorid,
					'Score' => round($score, 2),
                ];
            } else {
                // Handle case when beatmap details are not found
                $beatmap_details_array[] = [
                    'BeatmapID' => null,
                    'SetID' => null,
                    'Artist' => null,
                    'Title' => null,
                    'DifficultyName' => null,
                    'SR' => null,
                    'DateRanked' => null,
                    'CreatorID' => $creatorid,
					'Score' => null,
                ];
            }
        }

        return $beatmap_details_array;
    }
