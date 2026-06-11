<?php
	include_once '../base.php';

	$page = GetIntParam('p', 1, "NOO");
	$year = $_POST['y'] ?? $_GET['y'] ?? $year;
	if ($year !== "all-time")
		$year = (int)$year;
	$order = GetIntParam('o', 1, "NOO");
    $genre = GetIntParam('g', 0, "NOO");
    $language = GetIntParam('l', 0, "NOO");
    $country = $_POST['c'] ?? 0;
    $onlyFriends = $_POST['f'] ?? "false";
    $hideAlreadyRated = $_POST['alreadyRated'] ?? "false";
    $excludeGraveyard = $_POST['excludeGraveyard'] ?? "false";
    $excludeLoved = $_POST['excludeLoved'] ?? "false";
	$excludeRanked = $_POST['excludeRanked'] ?? "false";
	$minSR = (float)($_POST['minSR'] ?? 0);
	$maxSR = (float)($_POST['maxSR'] ?? -1);

    $descriptorsJSON = $_POST['descriptors'] ?? "[]";

    if (!isset($selectedDescriptors)) {
        $selectedDescriptors = json_decode($descriptorsJSON, true);
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
			$excludeRanked = $excludeRanked == "true";

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
            if ($year !== "all-time")
                $yearString = "AND YEAR(s.DateRanked) = '{$year}'";

            $genreString = "";
            if ($genre > 0)
                $genreString = "AND `Genre`='{$genre}'";

            $languageString = "";
            if ($language > 0)
                $languageString = "AND `Lang`='{$language}'";

            $countryString = "";
            if ($country !== 0 && $country !== "0") {
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
					$descriptorID = (int)$descriptor['id'];

					$subqueries[] = "EXISTS (SELECT 1 FROM beatmap_descriptors bd WHERE bd.BeatmapID = b.BeatmapID AND bd.DescriptorID = $descriptorID)";
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
			
			$excludeRankedString = "";
            if ($excludeRanked) 
                $excludeRankedString = "AND b.Status != 1 AND b.Status != 2";
			
			$srRangeString = "";
			if ($maxSR > 0)
				$srRangeString += "AND b.SR <= {$maxSR}";
			if ($minSR > 0)
				$srRangeString += "AND b.SR >= {$minSR}";
			
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
                                                      {$genreString} {$languageString} {$yearString} {$descriptorString} {$countryString} {$excludeLovedString} {$excludeGraveyardString} {$excludeRankedString}
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
                                              {$hideAlreadyRatedString} {$excludeLovedString} {$excludeGraveyardString} {$excludeRankedString}
                                              ORDER BY {$columnString} {$orderString}, BeatmapID 
                                              {$pageString}");
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

			while($row = $result->fetch_assoc()) {
				$stmt = $conn->prepare("
					SELECT 
						bd.DescriptorID,
						d.Name
					FROM beatmap_descriptors bd
					JOIN descriptors d ON bd.DescriptorID = d.DescriptorID
					WHERE bd.BeatmapID = ?
					ORDER BY bd.Weight DESC, bd.DescriptorID
					LIMIT 10
				");
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
					<a href="/mapset/<?php echo $row['SetID']; ?>"><?php echo $row['Artist']; ?> - <?php echo htmlspecialchars($row['Title'], ENT_QUOTES); ?> <br></a>
					<a href="/mapset/<?php echo $row['SetID']; ?>"><b><?php echo mb_strimwidth(htmlspecialchars($row['DifficultyName'], ENT_QUOTES), 0, 35, "..."); ?></b></a> <span class="subText"><?php echo number_format((float)$row['SR'], 2, '.', ''); ?>*</span><br>
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