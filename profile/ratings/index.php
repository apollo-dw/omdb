<?php
	$profileId = $_GET['id'] ?? -1;
	$page = $_GET['p'] ?? 1;
	$rating = $_GET['r'] ?? -1;
    $PageTitle = "Profile";

    require "../../base.php";
    require '../../header.php';
	
	if($profileid == -1 || $rating == -1){
		die("Invalid page bro");
	}
	
	$profile = $conn->query("SELECT * FROM `users` WHERE `UserID`='${profileId}';")->fetch_row()[0];
	$isUser = true;
	
	if ($profile == NULL){
		die("Can't view this bros ratings cuz they aint an OMDB user");
	}

	$limit = 25;
	$prevPage = $page - 1;
	$nextPage = $page + 1;
	$amntOfPages = floor($conn->query("SELECT Count(*) FROM `ratings` WHERE `UserID`='${profileId}' AND `Score`='${rating}';")->fetch_row()[0] / $limit) + 1;
?>
<center><h1><a href="/profile/<?php echo $profileId; ?>"><?php echo GetUserNameFromId($profileId, $conn); ?></a>'s <?php echo number_format($rating, 1, '.', ''); ?> ratings</h1></center>

<hr>

<style>
	.pagination {
		display: inline-block;
		color: white;
	}
	
	.pagination a{
		color: white;
	}

	.pagination span {
		float: left;
		padding: 8px 16px;
		width: 1em;
		text-decoration: none;
	}
	
	.flex-child a{
		color: white;
	}
</style>

<div style="text-align:center;">
	<div class="pagination">
	  <b><span><?php if($page > 1) { echo "<a href='?id=${profileId}&r=${rating}&p=${prevPage}'>&laquo; </a>"; } ?></span></b>
	  <span id="page"><?php echo $page; ?></span>
	  <b><span><?php if($page < $amntOfPages) { echo "<a href='?id=${profileId}&r=${rating}&p=${nextPage}'>&raquo; </a>"; } ?></span></b>
	</div>
</div>

<div class="flex-container">
	<div class="flex-child" style="width:100%;">
			<?php 
				$pageString = "LIMIT ${limit}";
				
				if ($page > 1){
					$lower = ($page - 1) * $limit;
					$pageString = "LIMIT ${lower}, ${limit}";
				}
				
				$counter = 0;
				$stmt = $conn->prepare("SELECT * FROM `ratings` WHERE `UserID`=? AND `Score`=? ORDER BY `date` DESC {$pageString};");
				$stmt->bind_param('ss', $profileId, $rating);
				$stmt->execute();
				$result = $stmt->get_result();

				while($row = $result->fetch_assoc()) {
					$counter += 1;
					
					$stmt2 = $conn->prepare("SELECT SetID, Artist, Title, DifficultyName FROM `beatmaps` WHERE `BeatmapID`=?;");
					$stmt2->bind_param('s', $row['BeatmapID']);
					$stmt2->execute();
					$setIDResult = $stmt2->get_result();
					$beatmap = $setIDResult->fetch_row();
					$stmt2->close();
			?>
			<div class="flex-container ratingContainer" <?php if($counter % 2 == 1){ echo "style='background-color:#203838;'"; } ?>>
				<div class="flex-child">
					<a href="/mapset/<?php echo $setID; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $beatmap[0]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='../../charts/INF.png';"></a>
				</div>
				<div class="flex-child" style="flex:0 0 60%;">
					<?php echo $row["Score"]; ?> on <a href="/mapset/<?php echo $beatmap[0]; ?>"><?php echo htmlspecialchars("${beatmap[1]} - ${beatmap[2]} [${beatmap[3]}]");?></a>
				</div>
				<div class="flex-child" style="width:100%;text-align:right;">
					<?php echo GetHumanTime($row["date"]); ?>
				</div>
			</div>
			<?php
				}
				$stmt->close();
			?>
	</div>
</div>

<div style="text-align:center;">
	<div class="pagination">
	  <b><span><?php if($page > 1) { echo "<a href='?id=${profileId}&r=${rating}&p=${prevPage}'>&laquo; </a>"; } ?></span></b>
	  <span id="page"><?php echo $page; ?></span>
	  <b><span><?php if($page < $amntOfPages) { echo "<a href='?id=${profileId}&r=${rating}&p=${nextPage}'>&raquo; </a>"; } ?></span></b>
	</div>
</div>


<?php
	require '../../footer.php';
?>