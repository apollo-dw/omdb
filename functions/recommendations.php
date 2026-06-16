<?php
	// Recommends mapsets similar to the given set's highest rated difficulty
	// Tune via weights + settings instead of editing the SQL
	// $seed is the diff the recs are based on
	function GetSimilarBeatmaps($conn, $setId, $maxResults = 8, &$seed = null, $seedBeatmapId = null, $overrides = [], $useCaching = true) {
		// Descriptor data setup from JSON used by GetSimilarBeatmaps
		static $descriptorData = null;
		static $nullifiedBy = null;

		if ($descriptorData === null || $nullifiedBy === null) {
			$descriptorJSON = __DIR__ . "/../assets/descriptors.json";
			$descriptorData = file_exists($descriptorJSON) ? json_decode(file_get_contents($descriptorJSON), true) : [];

			$stmt = $conn->prepare("SELECT DescriptorID, ParentID FROM descriptors");
			$stmt->execute();
			$descResult = $stmt->get_result();

			$allDescIds = [];
			$childrenMap = [];
			while ($row = $descResult->fetch_assoc()) {
				$id = (int)$row['DescriptorID'];
				$pid = $row['ParentID'] === null ? null : (int)$row['ParentID'];
				$allDescIds[] = $id;
				if ($pid !== null) {
					$childrenMap[$pid][] = $id;
				}
			}
			$stmt->close();

			$descendantsMap = [];
			foreach ($allDescIds as $id) {
				$stack = $childrenMap[$id] ?? [];
				$descendants = [];
				while (!empty($stack)) {
					$curr = array_pop($stack);
					$descendants[] = $curr;
					if (isset($childrenMap[$curr])) {
						foreach ($childrenMap[$curr] as $c) {
							$stack[] = $c;
						}
					}
				}
				$descendantsMap[$id] = $descendants;
			}

			$nullifiedBy = [];
			foreach ($allDescIds as $id) {
				$nullifiedBy[$id] = [];
			}

			foreach ($descriptorData as $jsonId => $data) {
				$a = (int)$jsonId;
				if (!in_array($a, $allDescIds)) continue;

				$nullifiers = $data['nullifiers'] ?? [];
				if (empty($nullifiers)) continue;

				$vulnerableNodes = array_merge([$a], $descendantsMap[$a] ?? []);

				foreach ($nullifiers as $b) {
					$b = (int)$b;
					$nullifyingNodes = array_merge([$b], $descendantsMap[$b] ?? []);

					foreach ($vulnerableNodes as $v) {
						foreach ($nullifyingNodes as $n) {
							$nullifiedBy[$v][] = $n;
						}
					}
				}
			}
		}

		// tgese are just multiplier coeffs rn
		$weights = [
			"avgScore" => 5, // weighted avg rating from users who like the diff
			"descriptorScore" => 1, // Overall multiplier for the descriptor scores provided in ../descriptors.json
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
			"minCoRaters" => 5, // diffs need at least this many users who rated BOTH maps
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

		if ($useCaching) {
			$stmt = $conn->prepare("
				SELECT b.BeatmapID, b.SetID, b.DifficultyName, s.Artist, s.Title, s.CreatorID, r.RecScore
				FROM beatmap_recommendations r
				INNER JOIN beatmaps b ON b.BeatmapID = r.RecMapID
				INNER JOIN beatmapsets s ON s.SetID = b.SetID
				WHERE r.MapID = ? AND r.ProcessDate >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND b.Blacklisted = 0
				ORDER BY r.RecScore DESC");
			$stmt->bind_param("i", $seed["BeatmapID"]);
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

			if (!empty($recommendations))
				return array_values($recommendations);
		}

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

		$stmt = $conn->prepare("SELECT DescriptorID FROM beatmap_descriptors WHERE BeatmapID = ?");
		$stmt->bind_param("i", $seed["BeatmapID"]);
		$stmt->execute();
		$result = $stmt->get_result();

		$seedDescriptorIDs = [];
		while ($row = $result->fetch_assoc())
			$seedDescriptorIDs[] = (int)$row["DescriptorID"];
		$stmt->close();

		$seedEffective = [];
		$nullifyListMap = [];
		foreach ($seedDescriptorIDs as $s) {
			$seedEffective[$s] = $descriptorData[$s]['weight'] ?? 1.0; // Default to weight 1.0 if not listed in the JSON file
			$sNullifiers = $nullifiedBy[$s] ?? [];
			foreach ($sNullifiers as $n) {
				$nullifyListMap[$n] = true;
			}
		}

		$nullifyList = array_keys($nullifyListMap);
		$relevantTargetDescIDs = array_unique(array_merge(array_keys($seedEffective), $nullifyList));

		// CANCER
		// Method: If target map has a nullifying descriptor, then descriptorScore = 0
		// Else sum the weights of the similar descriptors
		if (empty($relevantTargetDescIDs)) {
			$bdJoin = "";
			$descriptorScoreField = "0";
		} else {
			$relevantDescList = implode(',', $relevantTargetDescIDs);

			$scoreExprs = [];
			foreach ($seedEffective as $s => $weight) {
				$w = floatval($weight);
				$scoreExprs[] = "CASE WHEN MAX(CASE WHEN DescriptorID = $s THEN 1 ELSE 0 END) = 1 THEN $w ELSE 0 END";
			}
			$sumSql = implode(' + ', $scoreExprs);

			if (!empty($nullifyList)) {
				$nullifyListStr = implode(',', $nullifyList);
				$descriptorScoreSql = "CASE WHEN MAX(CASE WHEN DescriptorID IN ($nullifyListStr) THEN 1 ELSE 0 END) = 1 THEN 0 ELSE ($sumSql) END";
			} else {
				$descriptorScoreSql = $sumSql;
			}

			$bdJoin = "LEFT JOIN (
				SELECT BeatmapID, ($descriptorScoreSql) AS DescriptorScore
				FROM beatmap_descriptors
				WHERE DescriptorID IN ($relevantDescList)
				GROUP BY BeatmapID
			) bd ON bd.BeatmapID = r.BeatmapID";
			
			$descriptorScoreField = "COALESCE(bd.DescriptorScore, 0)";
		}

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
		if (empty($nominatorIDs))
			$nominatorIDs = [null];
		if (empty($creatorIDs))
			$creatorIDs = [null];

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
				$descriptorScoreField * ? +
				GREATEST(0, 1 - ABS(TIMESTAMPDIFF(MONTH, ?, b.Timestamp)) / ?) * ? +
				COUNT(DISTINCT bn.NominatorID) * ? +
				COUNT(DISTINCT bc.CreatorID) * ? +
				COALESCE(corr.Correlation, 0) * (corr.CoRaters / (corr.CoRaters + ?)) * ? +
				GREATEST(0, 1 - ABS(b.SR - ?) / (? * ?)) * ?
			) AS RecScore
			FROM ratings r
			INNER JOIN beatmaps b ON b.BeatmapID = r.BeatmapID
			INNER JOIN beatmapsets s ON s.SetID = b.SetID
			$bdJoin
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

		$types = "dd" . "dd" . "d" . "ddd" . "idd" . "d" . "sdd" . "dd" . "dd" . "dddd"
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
			[$weights["descriptorScore"]],
			[$seed["Timestamp"], $settings["proximityMonths"], $weights["monthProximity"]],
			[$weights["sharedNominator"]],
			[$weights["sharedMapper"]],
			[$settings["corrShrink"], $weights["correlation"]],
			[$seed["SR"], $seed["SR"], $settings["srWindow"], $weights["srProximity"]],
			$nominatorIDs, $creatorIDs,
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

		$recommendations = array_values($recommendations);
		if ($useCaching && !empty($recommendations)) {
			$stmt = $conn->prepare("DELETE FROM beatmap_recommendations WHERE MapID = ?");
			$stmt->bind_param("i", $seed["BeatmapID"]);
			$stmt->execute();
			$stmt->close();

			$stmt = $conn->prepare("INSERT INTO beatmap_recommendations (MapID, RecMapID, RecScore) VALUES (?, ?, ?)");
			foreach ($recommendations as $recommendation) {
				$stmt->bind_param("iid", $seed["BeatmapID"], $recommendation["BeatmapID"], $recommendation["RecScore"]);
				$stmt->execute();
			}

			$stmt->close();
		}

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
					<a href="/mapset/<?php echo $similarMap["SetID"]; ?>"><?php echo htmlspecialchars(mb_strimwidth("{$similarMap["Artist"]} - {$similarMap["Title"]} [{$similarMap["DifficultyName"]}]", 0, 75, "..."), ENT_QUOTES); ?></a><br>
					by <a href="/profile/<?php echo $similarMap["CreatorID"]; ?>"><?php echo htmlspecialchars($similarMapper, ENT_QUOTES); ?></a>
				</span>
			</div>
			<?php
		}
	}
