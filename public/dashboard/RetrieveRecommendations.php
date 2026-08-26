<?php
    function RetrieveRecommendations($conn, $userID) {
        $number_of_recommendations = 100;

        $stmt = $conn->prepare("SELECT IF(user1_id = ?, user2_id, user1_id) AS correlated_user, correlation
                                FROM user_correlations
                                WHERE ? IN (user1_id, user2_id) AND correlation > 0.33
                                ORDER BY correlation DESC LIMIT 150;");
        $stmt->bind_param("ii", $userID, $userID);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            return array("error" => "No correlated users");
        }

        $correlated_users = [];
        while ($row = $result->fetch_assoc()) {
            $correlated_users[$row['correlated_user']] = $row['correlation'];
        }
        $stmt->close();

        $correlated_ids = implode(', ', array_keys($correlated_users));

        $affinity_sql = "
                WITH UserStats AS (
                    SELECT AVG(Score) AS UserAvg FROM ratings WHERE UserID = ?
                )
                SELECT
                    bd.DescriptorID,
                    (AVG(r.Score) - MAX(us.UserAvg)) AS RawAffinity
                FROM ratings r
                JOIN beatmap_descriptors bd ON r.BeatmapID = bd.BeatmapID
                JOIN descriptors d ON bd.DescriptorID = d.DescriptorID
                CROSS JOIN UserStats us
                WHERE r.UserID = ? AND d.Usable = 1
                GROUP BY bd.DescriptorID
                HAVING COUNT(r.RatingID) >= 3;";

        $stmt = $conn->prepare($affinity_sql);
        $stmt->bind_param("ii", $userID, $userID);
        $stmt->execute();
        $aff_result = $stmt->get_result();

        $user_affinities = [];
        while ($row = $aff_result->fetch_assoc()) {
            $user_affinities[$row['DescriptorID']] = (float)$row['RawAffinity'];
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT r.BeatmapID
                                FROM ratings r
                                JOIN beatmap_creators bc ON r.BeatmapID = bc.BeatmapID
                                JOIN beatmaps b ON bc.BeatmapID = b.BeatmapID
                                JOIN beatmapsets s ON b.SetID = s.SetID
                                WHERE r.UserID IN ($correlated_ids)
                                AND r.BeatmapID NOT IN (SELECT BeatmapID FROM ratings WHERE UserID = ?)
                                AND bc.CreatorID <> ? AND s.CreatorID <> ?
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

        if (empty($rated_beatmaps)) {
            return array("error" => "No candidate recommendations found");
        }

        $beatmap_ids_str = implode(',', $rated_beatmaps);

        $result_correlated_ratings = $conn->query("SELECT UserID, BeatmapID, Score FROM ratings WHERE UserID IN ($correlated_ids) AND BeatmapID IN ($beatmap_ids_str)");

        $correlated_ratings = [];
        while ($row = $result_correlated_ratings->fetch_assoc()) {
            $beatmap_id = $row['BeatmapID'];
            $user_id = $row['UserID'];
            $rating = $row['Score'];

            if (!isset($correlated_ratings[$beatmap_id])) {
                $correlated_ratings[$beatmap_id] = ['sum_similarities' => 0, 'weighted_sum' => 0];
            }

            $correlation = $correlated_users[$user_id];
            $correlated_ratings[$beatmap_id]['sum_similarities'] += $correlation;
            $correlated_ratings[$beatmap_id]['weighted_sum'] += $rating * $correlation;
        }

        $beatmap_affinities = [];
        if (!empty($user_affinities)) {
            $desc_result = $conn->query("SELECT BeatmapID, DescriptorID, Weight FROM beatmap_descriptors WHERE BeatmapID IN ($beatmap_ids_str)");

            $bm_descriptor_sums = [];
            while ($row = $desc_result->fetch_assoc()) {
                $b_id = $row['BeatmapID'];
                $d_id = $row['DescriptorID'];
                $weight = (float)($row['Weight'] ?? 1);

                if (isset($user_affinities[$d_id])) {
                    if (!isset($bm_descriptor_sums[$b_id])) {
                        $bm_descriptor_sums[$b_id] = ['weighted_aff_sum' => 0, 'total_weight' => 0];
                    }
                    $bm_descriptor_sums[$b_id]['weighted_aff_sum'] += $user_affinities[$d_id] * $weight;
                    $bm_descriptor_sums[$b_id]['total_weight'] += $weight;
                }
            }

            foreach ($bm_descriptor_sums as $b_id => $data) {
                if ($data['total_weight'] > 0) {
                    $beatmap_affinities[$b_id] = $data['weighted_aff_sum'] / $data['total_weight'];
                }
            }
        }

        $recommendation_scores = [];
        foreach ($rated_beatmaps as $beatmap_id) {
            if (isset($correlated_ratings[$beatmap_id]) && $correlated_ratings[$beatmap_id]['sum_similarities'] > 0) {
                $collaborative_predicted_rating = $correlated_ratings[$beatmap_id]['weighted_sum'] / $correlated_ratings[$beatmap_id]['sum_similarities'];

                $affinity_modifier = $beatmap_affinities[$beatmap_id] ?? 0;
                $final_score = $collaborative_predicted_rating + ($affinity_modifier * 2);
            } else {
                $final_score = -1;
            }

            $recommendation_scores[$beatmap_id] = $final_score;
        }

        arsort($recommendation_scores);
        $sorted_recommendations = array_slice($recommendation_scores, 0, $number_of_recommendations, true);

        $final_ids = implode(',', array_keys($sorted_recommendations));
        $details_result = $conn->query("SELECT b.BeatmapID, artist, title, difficultyname, s.setid, SR, DateRanked
                                        FROM beatmaps b
                                        JOIN beatmapsets s ON b.SetID = s.SetID
                                        WHERE BeatmapID IN ($final_ids)");

        $details_map = [];
        while ($row = $details_result->fetch_assoc()) {
            $details_map[$row['BeatmapID']] = $row;
        }

        $beatmap_details_array = [];
        foreach ($sorted_recommendations as $beatmap_id => $score) {
            if (isset($details_map[$beatmap_id])) {
                $details = $details_map[$beatmap_id];
                $beatmap_details_array[] = [
                    'BeatmapID' => $beatmap_id,
                    'SetID' => $details['setid'],
                    'Artist' => $details['artist'],
                    'Title' => $details['title'],
                    'DifficultyName' => $details['difficultyname'],
                    'SR' => $details['SR'],
                    'DateRanked' => $details['DateRanked'],
                    'Score' => round($score, 2),
                ];
            }
        }

        return $beatmap_details_array;
    }
