<?php
	include_once '../connection.php';
	include_once '../functions.php';
	
	$choice = $_GET['c'] ?? -1;
	
	if (isset($_GET['p'])){
		$profileId = $_GET['p'];
	}

	$starString = "";
	if ($choice != -1){
		$starString = "AND `Score`='{$choice}'";
	}

    $stmt = $conn->prepare("SELECT r.*, b.*
                            FROM `ratings` r
                            JOIN `beatmaps` b ON r.`BeatmapID` = b.`BeatmapID`
                            WHERE r.`UserID` = ? AND b.`Mode` = ?
                            {$starString}
                            ORDER BY r.`date` DESC
                            LIMIT 50");
    $stmt->bind_param("ii", $profileId, $mode);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $beatmap = $row;
?>
	<div class="flex-container ratingContainer alternating-bg">
		<div class="flex-child">
			<a href="/mapset/<?php echo $beatmap["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $beatmap['SetID']; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='../charts/INF.png';"></a>
		</div>
		<div class="flex-child" style="flex:0 0 70%;">
			<?php echo RenderUserRating($conn, $row); ?> on <a href="/mapset/<?php echo $beatmap["SetID"]; ?>"><?php echo htmlspecialchars("{$beatmap["Artist"]} - {$beatmap["Title"]} [{$beatmap["DifficultyName"]}]");?></a>
		</div>
		<div class="flex-child" style="width:100%;">
			<?php echo GetHumanTime($row["date"]); ?>
		</div>
	</div>
<?php
	}
?>