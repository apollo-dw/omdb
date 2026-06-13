<?php
	// Recommends mapsets similar to the given set's highest rated difficulty
	// Tune via weights + settings instead of editing the SQL
	// $seed is the diff the recs are based on
	function GetSimilarBeatmaps($conn, $setId, $maxResults = 8, &$seed = null, $seedBeatmapId = null, $overrides = []) {
		// tgese are just multiplier coeffs rn
		$weights = [
			"avgScore" => 5, // weighted avg rating from users who like the diff
			"descriptorMatch" => 1, // per descriptor shared with the diff
			"descriptorPrecision" => 1.5, // similar to descriptorMatch but divided by the total descriptor count (aka map with 2/3 descriptors rated higher than map with 2/20 descriptors)
			"monthProximity" => 6, // when ranked within settings.yearWindow years of the seed
			"sharedNominator" => 1, // per nominator shared with the set
			"sharedMapper" => 4, // per mapper shared with the diff
			"cohortLift" => 8, // how much higher the fans rate the diff vs everyone else
			"cohortCoverage" => 16, // share of the fans vs everyone so big fanbases of the diff get no bump in this
			"correlation" => 0, // how the similar users rated both diffs generally (PEARSON CORRELATION COEFF)
			"srProximity" => 1, // how close the diffs are in star rating
		];

		$settings = [
			"likedThreshold" => 3.5, // ratings at/above this count as "positive"
			"proximityMonths" => 24,  // abs(diff rank date - TARGET) <= window
			"bayesMean" => 3.0, // site-wide mean for bayesian avg
			"bayesN" => 2, // n for bayesian avg
			"minAvgScore" => 3, // similar diffs need at least this bayesian avg rating
			"minScoreShare" => 0.06, // min fraction of fans
			"maxScoreShare" => 0.5, // max fraction of fans
			"maxScoreFloor" => 80, // avoid overfiltering cuz of the share settings
			"liftShrink" => 10, // n = u need 50% of the cohortLift value
			"coverageFade" => 80, // diminish the effect of cohort if the fanbase is n large
			"coverageCurve" => 2, // exponent setting so 90% fans is more than twice vs 45% fans
			"corrShrink" => 10, // similar to bayes avg, correlations are shrunk by n/(n+this)
			"minCoRaters" => 3, // diffs need at least this many users who rated BOTH maps
			"minCorrelation" => 0, // ignore candidates correlated below this (0 = anything negatively),
			"srWindow" => 0.5, // SR diff via fraction, so 0.5 = 50% of the diff's SR as the limit
		];

		$weights = array_merge($weights, $overrides["weights"] ?? []);
		$settings = array_merge($settings, $overrides["settings"] ?? []);

		if ($seedBeatmapId !== null) {
			$stmt = $conn->prepare("SELECT BeatmapID, DifficultyName, Mode, Timestamp, SR FROM beatmaps WHERE SetID = ? AND BeatmapID = ? AND Blacklisted = 0 LIMIT 1");
			$stmt->bind_param("ii", $setId, $seedBeatmapId);
		} else {
			$stmt = $conn->prepare("SELECT BeatmapID, DifficultyName, Mode, Timestamp, SR FROM beatmaps WHERE SetID = ? AND Blacklisted = 0 ORDER BY RatingCount DESC, ChartRank IS NULL, ChartRank ASC, WeightedAvg IS NULL, WeightedAvg DESC");
			$stmt->bind_param("i", $setId);
		}
		$stmt->execute();
		$seed = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if ($seed === null)
			return [];

		$stmt = $conn->prepare("SELECT DISTINCT UserID FROM ratings WHERE BeatmapID = ? AND Score >= ?");
		$stmt->bind_param("id", $seed["BeatmapID"], $settings["likedThreshold"]);
		$stmt->execute();
		$result = $stmt->get_result();

		$userIDs = [];
		while ($row = $result->fetch_assoc())
			$userIDs[] = $row["UserID"];
		$stmt->close();

		if (empty($userIDs))
			return [];

		$stmt = $conn->prepare("
			SELECT DescriptorID
			FROM beatmap_descriptors
			WHERE BeatmapID = ?
		");
		$stmt->bind_param("i", $seed["BeatmapID"]);
		$stmt->execute();
		$result = $stmt->get_result();

		$descriptorIDs = [];
		while ($row = $result->fetch_assoc())
			$descriptorIDs[] = $row["DescriptorID"];
		$stmt->close();

		$stmt = $conn->prepare("SELECT DISTINCT NominatorID FROM beatmapset_nominators WHERE SetID = ? AND Mode = ? AND NominatorID IS NOT NULL");
		$stmt->bind_param("ii", $setId, $seed["Mode"]);
		$stmt->execute();
		$result = $stmt->get_result();

		$nominatorIDs = [];
		while ($row = $result->fetch_assoc())
			$nominatorIDs[] = $row["NominatorID"];
		$stmt->close();

		$stmt = $conn->prepare("SELECT CreatorID FROM beatmap_creators WHERE BeatmapID = ?");
		$stmt->bind_param("i", $seed["BeatmapID"]);
		$stmt->execute();
		$result = $stmt->get_result();

		$creatorIDs = [];
		while ($row = $result->fetch_assoc())
			$creatorIDs[] = $row["CreatorID"];
		$stmt->close();

		// empty -> IN (NULL) to keep query valid
		if (empty($descriptorIDs))
			$descriptorIDs = [null];
		if (empty($nominatorIDs))
			$nominatorIDs = [null];
		if (empty($creatorIDs))
			$creatorIDs = [null];

		$descriptorPlaceholders = implode(',', array_fill(0, count($descriptorIDs), '?'));
		$nominatorPlaceholders = implode(',', array_fill(0, count($nominatorIDs), '?'));
		$creatorPlaceholders = implode(',', array_fill(0, count($creatorIDs), '?'));
		$userPlaceholders = implode(',', array_fill(0, count($userIDs), '?'));

		$bayesAvg = "(AVG(r.Score) * COUNT(DISTINCT r.RatingID) + ?) / (COUNT(DISTINCT r.RatingID) + ?)";
		$cohortLift = "(AVG(r.Score) - COALESCE(b.WeightedAvg, ?)) * (COUNT(DISTINCT r.RatingID) / (COUNT(DISTINCT r.RatingID) + ?))";

		// I hope I never have to write some bullshit like this ever again
		$stmt = $conn->prepare("SELECT b.BeatmapID, b.SetID, b.DifficultyName, s.Artist, s.Title, s.CreatorID, AVG(r.Score) AS AvgScore, COUNT(DISTINCT r.RatingID) AS ScoreCount, $bayesAvg AS BayesAvg, (
				$bayesAvg * ? +
				$cohortLift * ? +
				POW(COUNT(DISTINCT r.RatingID) / ?, ?) * ? +
				COUNT(DISTINCT bd.DescriptorID) * ? +
				COUNT(DISTINCT bd.DescriptorID) / GREATEST(MAX(td.TotalDescriptors), 1) * ? +
				GREATEST(0, 1 - ABS(TIMESTAMPDIFF(MONTH, ?, b.Timestamp)) / ?) * ? +
				COUNT(DISTINCT bn.NominatorID) * ? +
				COUNT(DISTINCT bc.CreatorID) * ? +
				COALESCE(corr.Correlation, 0) * (corr.CoRaters / (corr.CoRaters + ?)) * ? +
				GREATEST(0, 1 - ABS(b.SR - ?) / (? * ?)) * ?
			) AS RecScore
			FROM ratings r
			INNER JOIN beatmaps b ON b.BeatmapID = r.BeatmapID
			INNER JOIN beatmapsets s ON s.SetID = b.SetID
			LEFT JOIN beatmap_descriptors bd
				ON bd.BeatmapID = r.BeatmapID
				AND bd.DescriptorID IN ($descriptorPlaceholders)
			LEFT JOIN (
				SELECT BeatmapID, COUNT(*) AS TotalDescriptors
				FROM beatmap_descriptors
				GROUP BY BeatmapID
			) td ON td.BeatmapID = r.BeatmapID
			LEFT JOIN beatmapset_nominators bn ON bn.SetID = b.SetID AND bn.Mode = b.Mode AND bn.NominatorID IN ($nominatorPlaceholders)
			LEFT JOIN beatmap_creators bc ON bc.BeatmapID = r.BeatmapID AND bc.CreatorID IN ($creatorPlaceholders)
			LEFT JOIN (
				SELECT r2.BeatmapID,
					COUNT(*) AS CoRaters,
					(COUNT(*) * SUM(r1.Score * r2.Score) - SUM(r1.Score) * SUM(r2.Score)) /
					NULLIF(SQRT(
						(COUNT(*) * SUM(r1.Score * r1.Score) - POW(SUM(r1.Score), 2)) *
						(COUNT(*) * SUM(r2.Score * r2.Score) - POW(SUM(r2.Score), 2))
					), 0) AS Correlation
				FROM ratings r1
				INNER JOIN ratings r2 ON r2.UserID = r1.UserID AND r2.BeatmapID != r1.BeatmapID
				WHERE r1.BeatmapID = ?
				GROUP BY r2.BeatmapID
				HAVING CoRaters >= ? AND Correlation >= ?
			) corr ON corr.BeatmapID = r.BeatmapID
			WHERE r.UserID IN ($userPlaceholders)
				AND b.SetID != ?
				AND b.Mode = ?
				AND b.Blacklisted = 0
			GROUP BY b.BeatmapID
			HAVING BayesAvg >= ? AND ScoreCount >= ? AND ScoreCount <= ?
			ORDER BY RecScore DESC
			LIMIT ?;
		");

		// Query ranks individual diffs, so a vbunch of top diffs can be in the same set
		// So fetch extra rows to still end up with $maxResults distinct sets after deduplication
		$candidateLimit = $maxResults * 4;

		$minScoreCount = max(2, (int)ceil($settings["minScoreShare"] * count($userIDs)));
		$maxScoreCount = max($settings["maxScoreFloor"], $minScoreCount, (int)ceil($settings["maxScoreShare"] * count($userIDs)));

		$coverageWeight = $weights["cohortCoverage"] * max(0, 1 - count($userIDs) / $settings["coverageFade"]);

		$bayesSum = $settings["bayesMean"] * $settings["bayesN"];

		$types = "dd" . "dd" . "d" . "ddd" . "idd" . "dd" . "sdd" . "dd" . "dd" . "dddd"
			. str_repeat('i', count($descriptorIDs))
			. str_repeat('i', count($nominatorIDs))
			. str_repeat('i', count($creatorIDs))
			. "iid"
			. str_repeat('i', count($userIDs))
			. "ii" . "dii" . "i";

		$params = array_merge(
			[$bayesSum, $settings["bayesN"]],
			[$bayesSum, $settings["bayesN"]],
			[$weights["avgScore"]],
			[$settings["bayesMean"], $settings["liftShrink"], $weights["cohortLift"]],
			[count($userIDs), $settings["coverageCurve"], $coverageWeight],
			[$weights["descriptorMatch"]],
			[$weights["descriptorPrecision"]],
			[$seed["Timestamp"], $settings["proximityMonths"], $weights["monthProximity"]],
			[$weights["sharedNominator"]],
			[$weights["sharedMapper"]],
			[$settings["corrShrink"], $weights["correlation"]],
			[$seed["SR"], $seed["SR"], $settings["srWindow"], $weights["srProximity"]],
			$descriptorIDs, $nominatorIDs, $creatorIDs,
			[$seed["BeatmapID"], $settings["minCoRaters"], $settings["minCorrelation"]],
			$userIDs,
			[$setId, $seed["Mode"], $settings["minAvgScore"], $minScoreCount, $maxScoreCount, $candidateLimit]
		);

		$stmt->bind_param($types, ...$params);
		$stmt->execute();
		$result = $stmt->get_result();

		$recommendations = [];
		while ($row = $result->fetch_assoc()) {
			if (isset($recommendations[$row["SetID"]]))
				continue;
			$recommendations[$row["SetID"]] = $row;
			if (count($recommendations) >= $maxResults)
				break;
		}
		$stmt->close();

		return array_values($recommendations);
	}

	function RenderSimilarMapCards($conn, $similarMaps) {
		if (empty($similarMaps)) {
			echo '<span class="subText" style="padding:0.5em;">no similar maps found for this difficulty</span>';
			return;
		}

		foreach ($similarMaps as $similarMap) {
			$similarMapper = GetUserNameFromId($similarMap["CreatorID"], $conn);
			?>
			<div class="flex-child" style="text-align:center;width:11%;padding:0.5em;display: inline-block;margin-left:auto;margin-right:auto;">
				<a href="/mapset/<?php echo $similarMap["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $similarMap["SetID"]; ?>l.jpg" class="diffThumb" style="aspect-ratio: 1 / 1;width:90%;height:auto;" onerror="this.onerror=null; this.src='/charts/INF.png';"></a><br>
				<span class="subText">
					<a href="/mapset/<?php echo $similarMap["SetID"]; ?>"><?php echo htmlspecialchars(mb_strimwidth("{$similarMap["Artist"]} - {$similarMap["Title"]} [{$similarMap["DifficultyName"]}]", 0, 50, "..."), ENT_QUOTES); ?></a><br>
					by <a href="/profile/<?php echo $similarMap["CreatorID"]; ?>"><?php echo htmlspecialchars($similarMapper, ENT_QUOTES); ?></a>
				</span>
			</div>
			<?php
		}
	}
