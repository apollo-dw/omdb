<?php
	include_once '../connection.php';
	include_once '../functions.php';
	include_once '../userConnect.php';
	
	$page = $_GET['p'] ?? 1;
	$year = $_GET['y'] ?? $year;
	$order = $_GET['o'] ?? 1;
    $genre = $_GET['g'] ?? 0;
    $language = $_GET['l'] ?? 0;

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
                $columnString = "WeightedAvg";

            $yearString = "ORDER BY {$columnString}";
            if ($year != "all-time"){
                if ($order <= 2)
                    $columnString = "ChartYearRank";
                $yearString = "AND YEAR(b.DateRanked) = '{$year}' ORDER BY {$columnString}";
            }

            $genreString = "";
            if ($genre > 0)
                $genreString = "AND `Genre`='{$genre}'";

            $languageString = "";
            if ($language > 0)
                $languageString = "AND `Lang`='{$language}'";

			$stmt = $conn->prepare("SELECT b.* FROM beatmaps b WHERE b.Rating IS NOT NULL {$genreString} {$languageString} {$yearString} {$orderString}, BeatmapID {$pageString};");
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
			<div class="flex-container diffContainer" <?php if($counter % 2 == 1){ echo "style='background-color:#203838;'"; } ?>>
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