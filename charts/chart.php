<?php
	include_once '../base.php';

	$page = $_POST['p'] ?? 1;
	$year = $_POST['y'] ?? $year;
	$order = $_POST['o'] ?? 1;
    $genre = $_POST['g'] ?? 0;
    $language = $_POST['l'] ?? 0;
    $country = $_POST['c'] ?? 0;
    $onlyFriends = $_POST['f'] ?? "false";
    $hideAlreadyRated = $_POST['alreadyRated'] ?? "false";
    $excludeGraveyard = $_POST['excludeGraveyard'] ?? "false";
    $excludeLoved = $_POST['excludeLoved'] ?? "false";

    $descriptorsJSON = $_POST['descriptors'] ?? "[]";

    if (!isset($selectedDescriptors)) {
        $selectedDescriptors = json_decode($descriptorsJSON, true);
    }

	if(!is_numeric($page) || !is_numeric($order) || !is_numeric($genre) || !is_numeric($language)){
		die("NOO");
	}
?>

<div class="flex-item" style="padding:0.5em;">
		<?php
            $types = "ii";
            $params = [$userId, $mode];

            $onlyFriends = $onlyFriends == "true";
            $hideAlreadyRated = $hideAlreadyRated == "true";
            $excludeGraveyard = $excludeGraveyard == "true";
            $excludeLoved = $excludeLoved == "true";

			$lim = 50;
			$counter = ($page - 1) * $lim;

			$pageString = "LIMIT {$lim}";
			if ($page > 1){
				$lower = ($page - 1) * $lim;
				$pageString = "LIMIT {$lower}, {$lim}";
			}

            $orderString = "DESC";
            if ($order == 2)
                $orderString = "ASC";

            $columnString = "Rating";
            if ($order == 3)
                $columnString = "RatingCount";
            else if ($order == 4)
                $columnString = "controversy";
            else if ($order == 5)
                $columnString = "(WeightedAvg - CAST(Rating AS FLOAT))*SQRT(RatingCount)";

            $yearString = "";
            if ($year != "all-time"){
                if (!is_numeric($year))
                    die("NOOO!");

                $yearString = "AND YEAR(s.DateRanked) = '{$year}'";
            }

            $genreString = "";
            if ($genre > 0)
                $genreString = "AND `Genre`='{$genre}'";

            $languageString = "";
            if ($language > 0)
                $languageString = "AND `Lang`='{$language}'";

            $countryString = "";
            if ($country != 0) {
                $countryString = "AND b.BeatmapID IN (
                                        SELECT bc.BeatmapID
                                        FROM beatmap_creators bc
                                        JOIN mappernames mn ON bc.CreatorID = mn.UserID
                                        GROUP BY bc.BeatmapID
                                        HAVING COUNT(DISTINCT mn.Country) = 1 AND MAX(mn.Country = ?) = 1
                                    )";
                $types .= "s";
                $params[] = $country;
            }

            $descriptorString = "";
            if (!empty($selectedDescriptors)) {
                $subqueries = [];

                foreach ($selectedDescriptors as $descriptor) {
                    $descriptorID = $descriptor['id'];

                    if (!is_numeric($descriptorID))
                        die("NOOO");
                    $subquery = "(SELECT COUNT(*) FROM descriptor_votes dv WHERE b.BeatmapID = dv.BeatmapID AND dv.DescriptorID = '{$descriptorID}' AND dv.Vote = 1)";
                    $subqueries[] = "{$subquery} > (SELECT COALESCE(COUNT(*), 0) FROM descriptor_votes dv WHERE b.BeatmapID = dv.BeatmapID AND dv.DescriptorID = '{$descriptorID}' AND dv.Vote = 0)";
                }

                $descriptorString = "AND (" . implode(" AND ", $subqueries) . ")";
            }

            $hideAlreadyRatedString = "";
            if ($hideAlreadyRated)
                $hideAlreadyRatedString = "AND Score IS NULL";

            $excludeLovedString = "";
            if ($excludeLoved)
                $excludeLovedString = "AND b.Status != 4";

            $excludeGraveyardString = "";
            if ($excludeGraveyard)
                $excludeGraveyardString = "AND b.Status != -2";

            $stmt = null;
            if ($onlyFriends) {
                $stmt = $conn->prepare("SELECT
                                                b.*,
                                                s.*,
                                                (prior_rating * prior_count + total_score) / (prior_count + rating_count) AS BayesianAverage,
                                                rating_count AS RatingCount,
                                                friend_rating AS WeightedAvg,
                                                r_user.`Score` AS Score
                                            FROM
                                                (
                                                    SELECT
                                                        r.BeatmapID,
                                                        SUM(r.Score) AS total_score,
                                                        COUNT(r.BeatmapID) AS rating_count,
                                                        AVG(r.Score) AS friend_rating
                                                    FROM
                                                        users u
                                                            JOIN user_relations ur ON u.UserID = ur.UserIDFrom
                                                            JOIN ratings r ON r.UserID = ur.UserIDTo
                                                            JOIN beatmaps b ON b.BeatmapID = r.BeatmapID
                                                    WHERE
                                                      u.UserID = ?
                                                      AND ur.type = 1
                                                      AND b.Mode = ?
                                                      {$genreString} {$languageString} {$yearString} {$descriptorString} {$countryString} {$excludeLovedString} {$excludeGraveyardString}
                                                    GROUP BY
                                                        r.BeatmapID
                                                ) AS subquery
                                                    JOIN beatmaps b ON b.BeatmapID = subquery.BeatmapID
                                                    CROSS JOIN (
                                                    SELECT AVG(Score) AS prior_rating, COUNT(BeatmapID) AS prior_count
                                                    FROM ratings
                                                ) AS prior
                                            LEFT JOIN beatmapsets s on b.SetID = s.SetID
                                            LEFT JOIN ratings r_user ON b.BeatmapID = r_user.BeatmapID AND r_user.UserID = ?
                                            ORDER BY
                                                BayesianAverage {$orderString}, b.BeatmapID {$pageString};");
                $stmt->bind_param($types, ...$params);
            } else {
                $stmt = $conn->prepare("SELECT b.*, s.*, r.Score FROM beatmaps b 
                                              LEFT JOIN beatmapsets s on b.SetID = s.SetID
                                              LEFT JOIN ratings r ON b.BeatmapID = r.BeatmapID AND r.UserID = ?
                                              WHERE b.Rating IS NOT NULL 
                                              {$genreString} AND `Mode` = ? 
                                              {$languageString} {$yearString} {$descriptorString} {$countryString}
                                              {$hideAlreadyRatedString} {$excludeLovedString} {$excludeGraveyardString}
                                              ORDER BY {$columnString} {$orderString}, BeatmapID 
                                              {$pageString}");
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

			while($row = $result->fetch_assoc()) {
                $stmt = $conn->prepare("SELECT d.DescriptorID, d.Name
                                          FROM descriptor_votes 
                                          JOIN descriptors d on descriptor_votes.DescriptorID = d.DescriptorID
                                          WHERE BeatmapID = ?
                                          GROUP BY DescriptorID
                                          HAVING SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) > (SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END) + 0)
                                          ORDER BY (SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) - SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END)) DESC, DescriptorID
                                          LIMIT 10;");
                $stmt->bind_param("i", $row["BeatmapID"]);
                $stmt->execute();
                $descriptorResult = $stmt->get_result();

				$counter += 1;
		?>
			<div class="flex-container chart-container alternating-bg">
				<div style="text-align:right;flex: 0 0 5%;">
					<b><?php echo "#" . strval($counter); ?></b>
				</div>
				<div style="flex: 0 0 0;">
					<a href="/mapset/<?php echo $row['SetID']; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row['SetID']; ?>l.jpg" class="diffThumb" style="height:80px;width:80px;" onerror="this.onerror=null; this.src='INF.png';" /></a>
				</div>
				<div style="flex: 0 0 46%;">
					<a href="/mapset/<?php echo $row['SetID']; ?>"><?php echo $row['Artist']; ?> - <?php echo htmlspecialchars($row['Title']); ?> <a href="https://osu.ppy.sh/b/<?php echo $row['BeatmapID']; ?>" target="_blank" rel="noopener noreferrer"><i class="icon-external-link" style="font-size:10px;"></i></a><br></a>
					<a href="/mapset/<?php echo $row['SetID']; ?>"><b><?php echo mb_strimwidth(htmlspecialchars($row['DifficultyName']), 0, 35, "..."); ?></b></a> <span class="subText"><?php echo number_format((float)$row['SR'], 2, '.', ''); ?>*</span><br>
					<?php echo date("M jS, Y", strtotime($row['DateRanked']));?><br>
                    <?php RenderBeatmapCreators($row['BeatmapID'], $conn); ?><br>
                    <span class="subText map-descriptors">
                        <?php
                            $descriptorLinks = array();
                            while($descriptor = $descriptorResult->fetch_assoc()){
                                $descriptorLink = '<a style="color:inherit;" href="../descriptor/?id=' . $descriptor["DescriptorID"] . '">' . $descriptor["Name"] . '</a>';
                                $descriptorLinks[] = $descriptorLink;
                            }

                            echo implode(', ', $descriptorLinks);
                            ?>
                        </span>
                </div>
				<div style="flex: auto auto 0;">
					<b><?php echo number_format($row["WeightedAvg"], 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $row["RatingCount"]; ?></span> votes</span><br>
				</div>
				<div style="flex: 0 auto 0;">
					<b style="font-weight:900;"><?php echo $row["Score"]; ?></b>
				</div>
			</div>
		<?php
			}
		?>
	</div>