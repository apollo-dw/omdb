<?php
    function RetrieveRecommendations($conn, $userID){
        $number_of_recommendations = 100;

        $stmt = $conn->prepare("SELECT IF(user1_id = ?, user2_id, user1_id) AS correlated_user, correlation FROM user_correlations
                                WHERE ? IN (user1_id, user2_id) AND correlation > 0.33 ORDER BY correlation DESC LIMIT 150;");
        $stmt->bind_param("ii", $userID, $userID);
        $stmt->execute();

        $result = $stmt->get_result();
        if($result->num_rows == 0)
            return (array("error" => "No correlated users"));

        $correlated_users = [];
        while ($row = $result->fetch_assoc()) {
            $correlated_users[$row['correlated_user']] = $row['correlation'];
        }
        $stmt->close();

        $correlated_ids = implode(', ', array_keys($correlated_users));

        $stmt = $conn->prepare("SELECT r.BeatmapID
                                FROM ratings r
                                JOIN beatmap_creators bc ON r.BeatmapID = bc.BeatmapID
                                JOIN beatmaps b ON bc.BeatmapID = b.BeatmapID
                                WHERE r.UserID IN ($correlated_ids)
                                AND r.BeatmapID NOT IN (SELECT BeatmapID FROM ratings WHERE UserID = ?)
                                AND bc.CreatorID <> ? AND b.SetCreatorID <> ?
                                GROUP BY r.BeatmapID
                                HAVING COUNT(DISTINCT CASE WHEN r.UserID IN ($correlated_ids) THEN r.UserID END) > 5
                                ORDER BY AVG(CASE WHEN r.UserID IN ($correlated_ids) THEN r.Score END) DESC
                                LIMIT ?;");
        $stmt->bind_param('iiii', $userID, $userID, $userID, $number_of_recommendations);
        $stmt->execute();

        $result = $stmt->get_result();
        $rated_beatmaps = [];
        while ($row = $result->fetch_assoc()) {
            $rated_beatmaps[] = $row['BeatmapID'];
        }
        $stmt->close();

        $result_correlated_ratings = $conn->query("SELECT UserID, BeatmapID, Score FROM ratings WHERE UserID IN ($correlated_ids) AND BeatmapID IN (" . implode(',', $rated_beatmaps) . ")");

        $recommendation_scores = [];
        $correlated_ratings = [];

        while ($row = $result_correlated_ratings->fetch_assoc()) {
            $user_id = $row['UserID'];
            $beatmap_id = $row['BeatmapID'];
            $rating = $row['Score'];

            if (!isset($correlated_ratings[$beatmap_id])) {
                $correlated_ratings[$beatmap_id] = ['sum_similarities' => 0, 'weighted_sum' => 0];
            }

            $correlation = $correlated_users[$user_id];
            $correlated_ratings[$beatmap_id]['sum_similarities'] += $correlation;
            $correlated_ratings[$beatmap_id]['weighted_sum'] += $rating * $correlation;
        }

        foreach ($rated_beatmaps as $beatmap_id) {
            if (isset($correlated_ratings[$beatmap_id]) && $correlated_ratings[$beatmap_id]['sum_similarities'] > 0) {
                $predicted_rating = $correlated_ratings[$beatmap_id]['weighted_sum'] / $correlated_ratings[$beatmap_id]['sum_similarities'];
            } else {
                $predicted_rating = -1;
            }

            $recommendation_scores[$beatmap_id] = $predicted_rating;
        }

        arsort($recommendation_scores);
        $sorted_recommendations = array_slice($recommendation_scores, 0, $number_of_recommendations, true);

        $beatmap_details_array = [];
        foreach ($sorted_recommendations as $beatmap_id => $score) {
            $stmt = $conn->prepare("SELECT artist, title, difficultyname, setid, SR, DateRanked FROM beatmaps WHERE BeatmapID = ?;");
            $stmt->bind_param('i', $beatmap_id);
            $stmt->execute();

            $result = $stmt->get_result();
            $beatmap_details = $result->fetch_row();

            if ($beatmap_details) {
                list($artist, $title, $difficultyname, $setid, $sr, $date) = $beatmap_details;
                $beatmap_details_array[] = [
                    'BeatmapID' => $beatmap_id,
                    'SetID' => $setid,
                    'Artist' => $artist,
                    'Title' => $title,
                    'DifficultyName' => $difficultyname,
                    'SR' => $sr,
                    'DateRanked' => $date,
					'Score' => round($score, 2),
                ];
            }
        }

        return $beatmap_details_array;
    }
