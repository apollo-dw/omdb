<?php
	include_once '../base.php';

	$page = GetIntParam('p', 1, "NOO");
	$year = ($_POST['y'] ?? $_GET['y'] ?? "") === "all-time" ? "all-time" : GetIntParam('y', 2026, "NOO");
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

			$counter = 0;
			$pageString = "LIMIT {$lim}";
			if ($page > 1) {
				$offset = ($page - 1) * $lim;
				$counter = $offset;
				$pageString = "LIMIT {$offset}, {$lim}";
			}

			$orderString = ($order == 2) ? "ASC" : "DESC";


			$yearString = "";
			if ($year !== "all-time") {
				$yearString = "AND YEAR(s.DateRanked) = ?";
				$types .= "s";
				$params[] = $year;
			}

			$genreString = "";
			if ($genre > 0) {
				$genreString = "AND s.Genre = ?";
				$types .= "i";
				$params[] = $genre;
			}

			$languageString = "";
			if ($language > 0) {
				$languageString = "AND s.Lang = ?";
				$types .= "i";
				$params[] = $language;
			}

			$countryString = "";
			if ($country !== 0 && $country !== "0") {
				$countryString = "
					AND b.BeatmapID IN (
						SELECT bc.BeatmapID
						FROM beatmap_creators bc
						JOIN mappernames mn
							ON mn.UserID = bc.CreatorID
						GROUP BY bc.BeatmapID
						HAVING COUNT(DISTINCT mn.Country) = 1
						   AND MAX(mn.Country = ?) = 1
					)";
				$types .= "s";
				$params[] = $country;
			}

			$descriptorString = "";
			if (!empty($selectedDescriptors)) {
				$descriptorClauses = [];
				foreach ($selectedDescriptors as $descriptor) {
					$descriptorId = (int)$descriptor['id'];
					$descriptorClauses[] = "
						EXISTS (
							SELECT 1
							FROM beatmap_descriptors bd
							WHERE bd.BeatmapID = b.BeatmapID
							  AND bd.DescriptorID = {$descriptorId}
						)
					";
				}
				$descriptorString = "AND (" . implode(" AND ", $descriptorClauses) . ")";
			}

			$hideAlreadyRatedString = $hideAlreadyRated ? "AND r_user.Score IS NULL" : "";
			$excludeLovedString = $excludeLoved ? "AND b.Status != 4" : "";
			$excludeGraveyardString = $excludeGraveyard ? "AND b.Status != -2" : "";
			$excludeRankedString = $excludeRanked ? "AND b.Status NOT IN (1,2)" : "";
			$srRangeString = "";

			if ($minSR > 0)
				$srRangeString .= " AND b.SR >= " . (float)$minSR;

			if ($maxSR > 0)
				$srRangeString .= " AND b.SR <= " . (float)$maxSR;


			$statsJoin = "";
			$priorJoin = "";
			$statsTypes = "";
			$statsParams = [];

			if ($onlyFriends) {
				$statsJoin = "
					INNER JOIN (
						SELECT
							r.BeatmapID,
							SUM(r.Score) AS TotalScore,
							COUNT(*) AS RatingCount,
							AVG(r.Score) AS WeightedAvg,
							STDDEV_POP(r.Score) * SQRT(COUNT(*)) AS Controversy
						FROM user_relations ur
						JOIN ratings r
							ON r.UserID = ur.UserIDTo
						WHERE ur.UserIDFrom = ?
						  AND ur.Type = 1
						GROUP BY r.BeatmapID
					) friend_stats
						ON friend_stats.BeatmapID = b.BeatmapID";

				$priorJoin = "
					CROSS JOIN (
						SELECT
							AVG(Score) AS prior_rating,
							COUNT(*) AS prior_count
						FROM ratings
					) prior";

				$statsTypes = "i";
				$statsParams[] = $userId;
			}

			if ($onlyFriends) {
				$ratingField = "friend_stats.WeightedAvg";
				$countField = "friend_stats.RatingCount";
				$bayesField = "
					(
						(
							prior.prior_rating * prior.prior_count
						)
						+ friend_stats.TotalScore
					)
					/
					(
						prior.prior_count + friend_stats.RatingCount
					)";
			} else {
				$ratingField = "b.WeightedAvg";
				$countField = "b.RatingCount";
				$bayesField = "b.Rating";
			}

			switch ($order) {
				case 3:
					$columnString = $countField;
					break;
				case 4:
					if ($onlyFriends)
						$columnString = "friend_stats.Controversy";
					else
						$columnString = "b.controversy";
					break;
				case 5:
					if ($onlyFriends) 
						$columnString =
							"(friend_stats.WeightedAvg - b.Rating)
							 * SQRT(friend_stats.RatingCount)";
					else 
						$columnString =
							"(b.WeightedAvg - b.Rating)
							 * SQRT(b.RatingCount)";
					break;
				default:
					$columnString = "BayesianAverage";
					break;
			}

			$sql = "
			SELECT
				b.*,
				s.*,
				{$ratingField} AS WeightedAvg,
				{$countField} AS RatingCount,
				{$bayesField} AS BayesianAverage,
				r_user.Score
			FROM beatmaps b
			LEFT JOIN beatmapsets s
				ON s.SetID = b.SetID
			LEFT JOIN ratings r_user
				ON r_user.BeatmapID = b.BeatmapID
			   AND r_user.UserID = ?
			{$statsJoin}
			{$priorJoin}
			WHERE
				b.Mode = ?
				" . (!$onlyFriends ? "AND b.Rating IS NOT NULL" : "") . "
				{$genreString}
				{$languageString}
				{$yearString}
				{$countryString}
				{$descriptorString}
				{$hideAlreadyRatedString}
				{$excludeLovedString}
				{$excludeGraveyardString}
				{$excludeRankedString}
				{$srRangeString}
			ORDER BY
				{$columnString} {$orderString},
				b.BeatmapID
			{$pageString}";

			$finalTypes = $statsTypes . $types;
			$finalParams = array_merge($statsParams, $params);

			$stmt = $conn->prepare($sql);
			if (!$stmt) {
    die(
        "<pre>" .
        $conn->error .
        "\n\n" .
        safe_htmlspecialchars($sql) .
        "</pre>"
    );
}

			$stmt->bind_param($finalTypes, ...$finalParams);
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
					LIMIT 10");
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
					<a href="/mapset/<?php echo $row['SetID']; ?>"><?php echo $row['Artist']; ?> - <?php echo safe_htmlspecialchars($row['Title'], ENT_QUOTES); ?> <br></a>
					<a href="/mapset/<?php echo $row['SetID']; ?>"><b><?php echo safe_htmlspecialchars(mb_strimwidth($row['DifficultyName'], 0, 35, "..."), ENT_QUOTES); ?></b></a> <span class="subText"><?php echo number_format((float)$row['SR'], 2, '.', ''); ?>*</span><br>
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
					<b><?php echo number_format((float)$row["WeightedAvg"], 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $row["RatingCount"]; ?></span> votes</span><br>
				</div>
				<div style="flex: 0 auto 0;">
					<b style="font-weight:900;"><?php echo $row["Score"]; ?></b>
				</div>
			</div>
		<?php
			}
		?>
	</div>