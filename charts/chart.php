<?php
	include_once '../base.php';
	
	$page = $_GET['p'] ?? 1;
	$year = $_GET['y'] ?? $year;
	$order = $_GET['o'] ?? 1;
    $genre = $_GET['g'] ?? 0;
    $language = $_GET['l'] ?? 0;
    $onlyFriends = $_GET['f'] ?? "false";

	if(!is_numeric($page) || !is_numeric($order) || !is_numeric($genre) || !is_numeric($language)){
		die("NOO");
	}
?>

<div class="flex-item" style="padding:0.5em;">
		<?php
            $onlyFriends = $onlyFriends == "true";

			$lim = 50;
			$counter = ($page - 1) * $lim;

			$pageString = "LIMIT {$lim}";
			if ($page > 1){
				$lower = ($page - 1) * $lim;
				$pageString = "LIMIT {$lower}, {$lim}";
			}
			
			$orderString = "ASC";
			if ($order == 2 || $order == 3 || $order == 4 || $order == 5)
				$orderString = "DESC";

            $columnString = "ChartRank";
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

                $yearString = "AND YEAR(b.DateRanked) = '{$year}'";
            }

            $genreString = "";
            if ($genre > 0)
                $genreString = "AND `Genre`='{$genre}'";

            $languageString = "";
            if ($language > 0)
                $languageString = "AND `Lang`='{$language}'";

            $stmt = null;
            if ($onlyFriends) {
                $stmt = $conn->prepare("SELECT
                                                b.*,
                                                (prior_rating * prior_count + total_score) / (prior_count + rating_count) AS BayesianAverage,
                                                rating_count AS RatingCount,
                                                friend_rating AS WeightedAvg
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
                                                      {$genreString} {$languageString} {$yearString}
                                                    GROUP BY
                                                        r.BeatmapID
                                                ) AS subquery
                                                    JOIN beatmaps b ON b.BeatmapID = subquery.BeatmapID
                                                    CROSS JOIN (
                                                    SELECT AVG(Score) AS prior_rating, COUNT(BeatmapID) AS prior_count
                                                    FROM ratings
                                                ) AS prior
                                            ORDER BY
                                                -BayesianAverage {$orderString}, b.BeatmapID {$pageString};");
                $stmt->bind_param("ii", $userId, $mode);
            } else {
                $stmt = $conn->prepare("SELECT b.* FROM beatmaps b WHERE b.Rating IS NOT NULL {$genreString} AND `Mode` = ? {$languageString} {$yearString} ORDER BY {$columnString} {$orderString}, BeatmapID {$pageString}");
                $stmt->bind_param("i", $mode);
            }

            $stmt->execute();
            $result = $stmt->get_result();

			while($row = $result->fetch_assoc()) {
				$stmt2 = $conn->prepare("SELECT `Score` FROM `ratings` WHERE `BeatmapID`=? AND `UserID`=?;");
				$stmt2->bind_param('ss', $row['BeatmapID'], $userId);
				$stmt2->execute();
				$userRatingResult = $stmt2->get_result();
				$userRating = $userRatingResult->fetch_row()[0] ?? "";

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
                    <span class="subText">
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
					<b style="font-weight:900;"><?php echo $userRating; ?></b>
				</div>
			</div>
		<?php
			}
		?>
	</div>