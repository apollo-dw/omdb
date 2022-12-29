<?php
	include_once '../connection.php';
	include_once '../functions.php';
	
	$choice = $_GET['c'] ?? -1;
	
	if (isset($_GET['p'])){
		$profileId = $_GET['p'];
	}

?>
<style>
	.flex-container{
		display: flex;
		width: 100%;
		align-items: center;
	}
	
	.flex-child{
		margin: 0.25em;
		vertical-align: middle;
	}
	
	.flex-child a{
		color: white;
	}
	
	.profileRatingCard{
		background-color: DarkSlateGrey;
		width:100%;
	}
	
	.diffThumb{
		height: 32px;
		width: 32px;
		border: 1px solid #ddd;
		object-fit: cover;
	}
</style>
<?php
	$starString = "";
	if ($choice != -1){
		$starString = "AND `Score`='${choice}'";
	}
	
	$counter = 0;
	$result = $conn->query("SELECT * FROM `ratings` WHERE `UserID`='${profileId}' ${starString} ORDER BY `date` DESC LIMIT 50;");
	while($row = $result->fetch_assoc()){
		$counter += 1;			
		$beatmap = $conn->query("SELECT * FROM `beatmaps` WHERE `BeatmapID`='${row["BeatmapID"]}';")->fetch_assoc();
?>
	<div class="flex-container ratingContainer" <?php if($counter % 2 == 1){ echo "style='background-color:#203838;'"; } ?>>
		<div class="flex-child">
			<a href="/mapset/<?php echo $beatmap["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $beatmap['SetID']; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='../charts/INF.png';"></a>
		</div>
		<div class="flex-child" style="flex:0 0 70%;">
			<?php echo $row["Score"]; ?> on <a href="/mapset/<?php echo $beatmap["SetID"]; ?>"><?php echo htmlspecialchars("${beatmap["Artist"]} - ${beatmap["Title"]} [${beatmap["DifficultyName"]}]");?></a>
		</div>
		<div class="flex-child" style="width:100%;">
			<?php echo time_elapsed_string($row["date"]); ?>
		</div>
	</div>
<?php
	}
?>