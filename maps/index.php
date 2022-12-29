<?php
	$PageTitle = "Maps";
	require '../header.php';
	
	$month = $_GET['m'] ?? date("m");
	$year = $_GET['y'] ?? date("Y");
	$page = $_GET['p'] ?? 1;
	
	$limit = 20;
	$prevPage = $page - 1;
	$nextPage = $page + 1;
	$amntOfPages = floor($conn->query("SELECT Count(DISTINCT SetID, Artist, Title, CreatorID, DateRanked) FROM `beatmaps` WHERE MONTH(DateRanked)='${month}' AND YEAR(DateRanked)='${year}' AND `Mode`='0';")->fetch_row()[0] / $limit) + 1;
?>
<h1>Map List - <?php echo "${month}/${year}"; ?></h1>
<div style="text-align:center;">
	<div class="pagination">
	  <b><span><?php if($page > 1) { echo "<a href='?m=${month}&y=${year}&p=${prevPage}'>&laquo; </a>"; } ?></span></b>
	  <span id="page"><?php echo $page; ?></span>
	  <b><span><?php if($page < $amntOfPages) { echo "<a href='?m=${month}&y=${year}&p=${nextPage}'>&raquo; </a>"; } ?></span></b>
	</div>
</div>

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
</style>

<?php
	$pageString = "LIMIT ${limit}";
			
	if ($page > 1){
		$lower = ($page - 1) * $limit;
		$pageString = "LIMIT ${lower}, ${limit}";
	}
	
	$counter = 0;
	$result = $conn->query("SELECT DISTINCT SetID, Artist, Title, CreatorID, DateRanked FROM `beatmaps` WHERE MONTH(DateRanked)='${month}' AND YEAR(DateRanked)='${year}' AND `Mode`='0' ORDER BY `DateRanked` DESC ${pageString};");
		while($row = $result->fetch_assoc()) {
			$counter += 1;
			$mapperName = GetUserNameFromId($row["CreatorID"], $conn);
?>
<div class="flex-container ratingContainer mapList" <?php if($counter % 2 == 1){ echo "style='background-color:#203838;'"; } ?>>
	<div class="flex-child" style="flex: 0 0 8%;">
		<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb" style="height:82px;width:82px;" onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
	</div>
	<div class="flex-child" style="flex: 0 0 50%;min-width: 0;">
		<a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo "${row["Artist"]} - ${row["Title"]}</a> by <a href='/profile/${row["CreatorID"]}'>${mapperName}</a>"; ?> <a href="osu://s/<?php echo $row['SetID']; ?>"><i class="icon-download-alt">&ZeroWidthSpace;</i></a>
	</div>
	<div class="flex-child" style="flex: 0 0 3%;min-width: 0;">
		<?php echo $row["DateRanked"]; ?>
	</div>
	<div class="flex-child" style="flex: 0 0 32%;text-align:right;min-width:0;">
		<b><?php echo $conn->query("SELECT ROUND(AVG(Score), 2) FROM `ratings` WHERE BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID='${row["SetID"]}');")->fetch_row()[0]; ?></b> <span style="font-size:12px;color:grey;">/ 5.00 from <?php echo $conn->query("SELECT Count(*) FROM `ratings` WHERE BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID='${row["SetID"]}');")->fetch_row()[0]; ?> votes</span><br>
	</div>
</div>
<?php
		}
?>

<div style="text-align:center;">
	<div class="pagination">
	  <b><span><?php if($page > 1) { echo "<a href='?m=${month}&y=${year}&p=${prevPage}'>&laquo; </a>"; } ?></span></b>
	  <span id="page"><?php echo $page; ?></span>
	  <b><span><?php if($page < $amntOfPages) { echo "<a href='?m=${month}&y=${year}&p=${nextPage}'>&raquo; </a>"; } ?></span></b>
	</div>
</div>

<?php
	require '../footer.php';
?>