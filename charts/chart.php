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

<style>
	.flex-container{
		display: flex;
		width: 100%;
	}
	
	.diffContainer{
		background-color:DarkSlateGrey;
		align-items: center;
	}
	
	.diffBox{
		padding:0.5em;
		flex-grow: 1;
		height:100%;
	}
	
	.diffbox a{
		color: white;
	}
	
	.diffThumb{
		height: 80px;
		width: 80px;
		border: 1px solid #ddd;
		object-fit: cover;
	}
</style>

<div class="flex-item" style="flex: 0 0 80%; padding:0.5em;">
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
				$stmt2->close();

				$counter += 1;
		?>
			<div class="flex-container diffContainer alternating-bg">
				<div class="diffBox" style="text-align:center;padding-left:1.5em;flex: 0 0 6%;">
					<b><?php echo "#" . strval($counter); ?></b>
				</div>
				<div class="diffBox" style="flex: 0 0 6%;">
					<a href="/mapset/<?php echo $row['SetID']; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row['SetID']; ?>l.jpg" class="diffThumb" onerror="this.onerror=null; this.src='INF.png';" /></a>
				</div>
				<div class="diffBox" style="flex: 0 0 42%;">
					<a href="/mapset/<?php echo $row['SetID']; ?>"><?php echo $row['Artist']; ?> - <?php echo htmlspecialchars($row['Title']); ?> <a href="https://osu.ppy.sh/b/<?php echo $row['BeatmapID']; ?>" target="_blank" rel="noopener noreferrer"><i class="icon-external-link" style="font-size:10px;"></i></a><br></a>
					<a href="/mapset/<?php echo $row['SetID']; ?>"><b><?php echo htmlspecialchars($row['DifficultyName']); ?></b></a> <span class="subText"><?php echo number_format((float)$row['SR'], 2, '.', ''); ?>*</span><br>
					<?php echo date("M jS, Y", strtotime($row['DateRanked']));?><br>
					<a href="/profile/<?php echo $row['CreatorID']; ?>"><?php echo GetUserNameFromId($row['CreatorID'], $conn); ?></a> <a href="https://osu.ppy.sh/u/<?php echo $row['CreatorID']; ?>" target="_blank" rel="noopener noreferrer"><i class="icon-external-link" style="font-size:10px;"></i></a><br>
				</div>
				<div class="diffBox">
					<b><?php echo number_format($row["WeightedAvg"], 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $row["RatingCount"]; ?></span> votes</span><br>
				</div>
				<div class="diffBox">
					<b style="font-weight:900;"><?php echo $userRating; ?></b>
				</div>
			</div>
		<?php
			}
		?>
	</div>