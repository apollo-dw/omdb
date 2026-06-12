<?php
	// Recommends mapsets similar to the given set's highest rated difficulty
	// Tune via weights + settings instead of editing the SQL
	// $seed is the diff the recs are based on
	function GetSimilarBeatmaps($conn, $setId, $maxResults = 8, &$seed = null, $seedBeatmapId = null) {
		// tgese are just multiplier coeffs rn
		$weights = [
			"avgScore" => 5, // weighted avg rating from users who like the diff
			"descriptorMatch" => 4, // per descriptor shared with the diff
			"yearProximity" => 0.5, // when ranked within settings.yearWindow years of the seed
			"sharedNominator" => 1, // per nominator shared with the set
			"sharedMapper" => 1.5, // per mapper shared with the diff
		];

		$settings = [
			"likedThreshold" => 3.5, // ratings at/above this count as "positive"
			"yearWindow" => 1, // abs(diff rank year - TARGET) <= window
			"bayesMean" => 3.0, // site-wide mean for bayesian avg
			"bayesN" => 10, // n for bayesian avg
			"minAvgScore" => 3, // similar diffs need at least this bayesian avg rating
		];

		if ($seedBeatmapId !== null) {
			$stmt = $conn->prepare("SELECT BeatmapID, DifficultyName, Mode, Timestamp FROM beatmaps WHERE SetID = ? AND BeatmapID = ? AND Blacklisted = 0 LIMIT 1");
			$stmt->bind_param("ii", $setId, $seedBeatmapId);
		} else {
			$stmt = $conn->prepare("SELECT BeatmapID, DifficultyName, Mode, Timestamp FROM beatmaps WHERE SetID = ? AND Blacklisted = 0 ORDER BY ChartYearRank IS NULL, WeightedAvg IS NULL, WeightedAvg DESC, RatingCount DESC LIMIT 1");
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
			FROM descriptor_votes
			WHERE BeatmapID = ?
			GROUP BY DescriptorID
			HAVING SUM(Vote = 1) > SUM(Vote = 0)
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

		// H0Ly Cancer
		$stmt = $conn->prepare("
			SELECT b.BeatmapID, b.SetID, b.DifficultyName, s.Artist, s.Title, s.CreatorID,
				AVG(r.Score) AS AvgScore, COUNT(DISTINCT r.RatingID) AS ScoreCount,
				$bayesAvg AS BayesAvg,
				(
					$bayesAvg * ? +
					COUNT(DISTINCT bd.DescriptorID) * ? +
					IF(ABS(TIMESTAMPDIFF(YEAR, ?, b.Timestamp)) <= ?, ?, 0) +
					COUNT(DISTINCT bn.NominatorID) * ? +
					COUNT(DISTINCT bc.CreatorID) * ?
				) AS RecScore
			FROM ratings r
			INNER JOIN beatmaps b ON b.BeatmapID = r.BeatmapID
			INNER JOIN beatmapsets s ON s.SetID = b.SetID
			LEFT JOIN (
					SELECT BeatmapID, DescriptorID
					FROM descriptor_votes
					WHERE DescriptorID IN ($descriptorPlaceholders)
					GROUP BY BeatmapID, DescriptorID
					HAVING SUM(Vote = 1) > SUM(Vote = 0)
				) bd ON bd.BeatmapID = r.BeatmapID
			LEFT JOIN beatmapset_nominators bn ON bn.SetID = b.SetID AND bn.Mode = b.Mode AND bn.NominatorID IN ($nominatorPlaceholders)
			LEFT JOIN beatmap_creators bc ON bc.BeatmapID = r.BeatmapID AND bc.CreatorID IN ($creatorPlaceholders)
			WHERE r.UserID IN ($userPlaceholders)
				AND b.SetID != ?
				AND b.Mode = ?
				AND b.Blacklisted = 0
			GROUP BY b.BeatmapID
			HAVING BayesAvg >= ?
			ORDER BY RecScore DESC
			LIMIT ?;
		");

		// Query ranks individual diffs, so a vbunch of top diffs can be in the same set
		// So fetch extra rows to still end up with $maxResults distinct sets after deduplication.
		$candidateLimit = $maxResults * 4;

		$bayesSum = $settings["bayesMean"] * $settings["bayesN"];
		$types = "dd" . "dd" . "ddsiddd"
			. str_repeat('i', count($descriptorIDs))
			. str_repeat('i', count($nominatorIDs))
			. str_repeat('i', count($creatorIDs))
			. str_repeat('i', count($userIDs))
			. "ii" . "d" . "i";
		$params = array_merge(
			[$bayesSum, $settings["bayesN"], $bayesSum, $settings["bayesN"]],
			[$weights["avgScore"], $weights["descriptorMatch"], $seed["Timestamp"], $settings["yearWindow"], $weights["yearProximity"], $weights["sharedNominator"], $weights["sharedMapper"]],
			$descriptorIDs, $nominatorIDs, $creatorIDs, $userIDs,
			[$setId, $seed["Mode"], $settings["minAvgScore"], $candidateLimit]
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

	// Same deal here
	function GetCorrelatedBeatmaps($conn, $setId, $maxResults = 8, &$seed = null, $seedBeatmapId = null) {
		$weights = [
			"correlation" => 1, // how similar users rated both diffs
			"descriptorMatch" => 0.5, // per descriptor shared with the diff
		];

		$settings = [
			"minCoRaters" => 3, // diffs need at least this many users who rated BOTH maps
			"corrShrink" => 10, // similar to bayes avg, correlations are shrunk by n/(n+this)
			"minCorrelation" => 0, // ignore candidates correlated below this (0 = anything negatively)
		];

		if ($seedBeatmapId !== null) {
			$stmt = $conn->prepare("SELECT BeatmapID, DifficultyName, Mode, Timestamp FROM beatmaps WHERE SetID = ? AND BeatmapID = ? AND Blacklisted = 0 LIMIT 1");
			$stmt->bind_param("ii", $setId, $seedBeatmapId);
		} else {
			$stmt = $conn->prepare("SELECT BeatmapID, DifficultyName, Mode, Timestamp FROM beatmaps WHERE SetID = ? AND Blacklisted = 0 ORDER BY ChartYearRank IS NULL, WeightedAvg IS NULL, WeightedAvg DESC, RatingCount DESC LIMIT 1");
			$stmt->bind_param("i", $setId);
		}
		$stmt->execute();
		$seed = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if ($seed === null)
			return [];

		$stmt = $conn->prepare("
			SELECT DescriptorID
			FROM descriptor_votes
			WHERE BeatmapID = ?
			GROUP BY DescriptorID
			HAVING SUM(Vote = 1) > SUM(Vote = 0)
		");
		$stmt->bind_param("i", $seed["BeatmapID"]);
		$stmt->execute();
		$result = $stmt->get_result();

		$descriptorIDs = [];
		while ($row = $result->fetch_assoc())
			$descriptorIDs[] = $row["DescriptorID"];
		$stmt->close();

		$seedDescriptorCount = max(1, count($descriptorIDs));
		if (empty($descriptorIDs))
			$descriptorIDs = [null];
		$descriptorPlaceholders = implode(',', array_fill(0, count($descriptorIDs), '?'));

		// HOLY CANCER CORRELATION CALC
		// t = correlation dm = descriptor matching
		$stmt = $conn->prepare("
			SELECT t.*, COALESCE(dm.MatchingDescriptors, 0) AS MatchingDescriptors,
				(
					t.Correlation * (t.CoRaters / (t.CoRaters + ?)) * ? +
					COALESCE(dm.MatchingDescriptors, 0) / ? * ?
				) AS RecScore
			FROM (
				SELECT b.BeatmapID, b.SetID, b.DifficultyName, s.Artist, s.Title, s.CreatorID,
					COUNT(*) AS CoRaters, AVG(r2.Score) AS AvgScore,
					(COUNT(*) * SUM(r1.Score * r2.Score) - SUM(r1.Score) * SUM(r2.Score)) /
					SQRT(
						(COUNT(*) * SUM(r1.Score * r1.Score) - POW(SUM(r1.Score), 2)) *
						(COUNT(*) * SUM(r2.Score * r2.Score) - POW(SUM(r2.Score), 2))
					) AS Correlation
				FROM ratings r1
				INNER JOIN ratings r2 ON r2.UserID = r1.UserID AND r2.BeatmapID != r1.BeatmapID
				INNER JOIN beatmaps b ON b.BeatmapID = r2.BeatmapID
				INNER JOIN beatmapsets s ON s.SetID = b.SetID
				WHERE r1.BeatmapID = ?
					AND b.SetID != ?
					AND b.Mode = ?
					AND b.Blacklisted = 0
				GROUP BY b.BeatmapID
				HAVING CoRaters >= ?
			) t
			LEFT JOIN (
				SELECT BeatmapID, COUNT(*) AS MatchingDescriptors
				FROM (
					SELECT BeatmapID, DescriptorID
					FROM descriptor_votes
					WHERE DescriptorID IN ($descriptorPlaceholders)
					GROUP BY BeatmapID, DescriptorID
					HAVING SUM(Vote = 1) > SUM(Vote = 0)
				) m
				GROUP BY BeatmapID
			) dm ON dm.BeatmapID = t.BeatmapID
			WHERE t.Correlation IS NOT NULL AND t.Correlation >= ?
			ORDER BY RecScore DESC
			LIMIT ?;
		");

		$candidateLimit = $maxResults * 4;
		$types = "dddd" . "iiii" . str_repeat('i', count($descriptorIDs)) . "di";
		$params = array_merge(
			[$settings["corrShrink"], $weights["correlation"], $seedDescriptorCount, $weights["descriptorMatch"]],
			[$seed["BeatmapID"], $setId, $seed["Mode"], $settings["minCoRaters"]],
			$descriptorIDs,
			[$settings["minCorrelation"], $candidateLimit]
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

	// From test.php
	function GetSimilarBeatmapsTestPhp($conn, $setId, $maxResults = 8, &$seed = null, $seedBeatmapId = null) {
		$weights = [
			"avgScore" => 1,
			"descriptorMatch" => 1.5,
			"yearProximity" => 2,
		];

		$settings = [
			"likedThreshold" => 4,
			"yearWindow" => 2,
			"minAvgScore" => 4,
			"minRatingCount" => 15,
			"maxRatingCount" => 80,
			"descriptorLimit" => 5,
		];

		if ($seedBeatmapId !== null) {
			$stmt = $conn->prepare("SELECT BeatmapID, DifficultyName, Mode, Timestamp FROM beatmaps WHERE SetID = ? AND BeatmapID = ? AND Blacklisted = 0 LIMIT 1");
			$stmt->bind_param("ii", $setId, $seedBeatmapId);
		} else {
			$stmt = $conn->prepare("SELECT BeatmapID, DifficultyName, Mode, Timestamp FROM beatmaps WHERE SetID = ? AND Blacklisted = 0 ORDER BY ChartYearRank IS NULL, WeightedAvg IS NULL, WeightedAvg DESC, RatingCount DESC LIMIT 1");
			$stmt->bind_param("i", $setId);
		}
		$stmt->execute();
		$seed = $stmt->get_result()->fetch_assoc();
		$stmt->close();

		if ($seed === null)
			return [];

		$stmt = $conn->prepare("
			SELECT DescriptorID
			FROM descriptor_votes
			WHERE BeatmapID = ?
			GROUP BY DescriptorID
			HAVING SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) > SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END)
			ORDER BY (SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) - SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END)) DESC, DescriptorID
			LIMIT ?
		");
		$stmt->bind_param("ii", $seed["BeatmapID"], $settings["descriptorLimit"]);
		$stmt->execute();
		$result = $stmt->get_result();

		$descriptorIDs = [];
		while ($row = $result->fetch_assoc())
			$descriptorIDs[] = $row["DescriptorID"];
		$stmt->close();

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

		if (empty($descriptorIDs))
			$descriptorIDs = [null];

		$descriptorPlaceholders = implode(',', array_fill(0, count($descriptorIDs), '?'));
		$userPlaceholders = implode(',', array_fill(0, count($userIDs), '?'));

		$stmt = $conn->prepare("
			SELECT r.BeatmapID, b.SetID, b.DifficultyName, s.Artist, s.Title, s.CreatorID,
				AVG(r.Score) AS AvgScore, COUNT(r.Score) AS ScoreCount,
				COUNT(DISTINCT dv.DescriptorID) AS MatchingDescriptors,
				(
					AVG(r.Score) * ? +
					COUNT(DISTINCT dv.DescriptorID) * ? +
					IF(ABS(TIMESTAMPDIFF(YEAR, ?, b.Timestamp)) <= ?, ?, 0)
				) AS RecScore
			FROM ratings r
			LEFT JOIN beatmaps b ON b.BeatmapID = r.BeatmapID
			LEFT JOIN beatmapsets s ON s.SetID = b.SetID
			LEFT JOIN descriptor_votes dv ON dv.BeatmapID = r.BeatmapID AND dv.DescriptorID IN ($descriptorPlaceholders)
			WHERE r.UserID IN ($userPlaceholders)
				AND r.BeatmapID != ?
			GROUP BY r.BeatmapID
			HAVING AvgScore >= ? AND ScoreCount > ? AND ScoreCount < ?
			ORDER BY RecScore DESC
			LIMIT ?;
		");

		$types = "ddsid"
			. str_repeat('i', count($descriptorIDs))
			. str_repeat('i', count($userIDs))
			. "i" . "dii" . "i";
		$params = array_merge(
			[$weights["avgScore"], $weights["descriptorMatch"], $seed["Timestamp"], $settings["yearWindow"], $weights["yearProximity"]],
			$descriptorIDs, $userIDs,
			[$seed["BeatmapID"], $settings["minAvgScore"], $settings["minRatingCount"], $settings["maxRatingCount"], $maxResults]
		);
		$stmt->bind_param($types, ...$params);
		$stmt->execute();
		$result = $stmt->get_result();

		$recommendations = [];
		while ($row = $result->fetch_assoc())
			$recommendations[] = $row;
		$stmt->close();

		return $recommendations;
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
