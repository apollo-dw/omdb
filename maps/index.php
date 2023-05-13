<?php
	$PageTitle = "Maps";
	require "../base.php";
	require '../header.php';
	
	$month = $_GET['m'] ?? date("m");
	$year = $_GET['y'] ?? date("Y");
	$page = $_GET['p'] ?? 1;

    $minMonth = 1;
    $maxMonth = 12;

    // In 2007, ranked maps started in October.
    if ($year == 2007)
        $minMonth = 10;

    // In the current year, they should show up until the latest month only.
    if ($year == date("Y"))
        $maxMonth = date("m");

    // Clamp month value.
    $month = max($minMonth, min($maxMonth, $month));
	
	$limit = 20;
	$amntOfPages = floor($conn->query("SELECT Count(DISTINCT SetID, Artist, Title, SetCreatorID, DateRanked) FROM `beatmaps` WHERE MONTH(DateRanked)='{$month}' AND YEAR(DateRanked)='{$year}' AND `Mode`='0';")->fetch_row()[0] / $limit) + 1;
    $prevPage = max($page - 1, 1);
    $nextPage = min($page + 1, $amntOfPages);
?>

<h1>Map List - <?php echo DateTime::createFromFormat('!m', $month)->format('F') . " " . $year; ?></h1>

<style>
    .pagination {
        display: inline-block;
        color: DarkSlateGrey;
    }

    .pagination span {
        float: left;
        padding: 8px 16px;
        text-decoration: none;
        cursor: pointer;
    }

    .pagination a {
        color: inherit;
        box-sizing: inherit;
    }

    .active {
        font-weight: 900;
        color: white;
    }
</style>

<select name="year" onchange="location = '?m=<?php echo $month; ?>&y=' + this.value;">
    <?php
    for ($i = 2007; $i <= date('Y'); $i++) {
        echo '<option value="' . $i . '"';
        if ($year == $i) {
            echo ' selected="selected"';
        }
        echo '>' . $i . '</option>';
    }
    ?>
</select>
<select name="month" onchange="location = '?m=' + this.value + '&y=<?php echo $year; ?>';">
    <?php
    for ($i = $minMonth; $i <= $maxMonth; $i++) {
        echo '<option value="' . $i . '"';
        if ($month == $i) {
            echo ' selected="selected"';
        }
        echo '>' . DateTime::createFromFormat('!m', $i)->format('F') . '</option>';
    }
    ?>
</select>


<div style="text-align:left;">
    <div class="pagination">
        <a href="<?php echo "?m={$month}&y={$year}&p={$prevPage}"; ?>"><span>&laquo;</span></a>
        <?php for ($i = 1; $i <= $amntOfPages; $i++) { ?>
            <a href="<?php echo "?m={$month}&y={$year}&p={$i}"; ?>"><span class="pageLink <?php if ($page == $i) echo 'active' ?>"><?php echo $i ?></span></a>
        <?php } ?>
        <a href="<?php echo "?m={$month}&y={$year}&p={$nextPage}"; ?>"><span>&raquo;</span></a>
    </div>
</div>

<?php
	$pageString = "LIMIT {$limit}";
			
	if ($page > 1){
		$lower = ($page - 1) * $limit;
		$pageString = "LIMIT {$lower}, {$limit}";
	}
	
	$counter = 0;
	$result = $conn->query("SELECT DISTINCT SetID, Artist, Title, SetCreatorID, DateRanked FROM `beatmaps` WHERE MONTH(DateRanked)='{$month}' AND YEAR(DateRanked)='{$year}' AND `Mode`='0' ORDER BY `DateRanked` DESC {$pageString};");
		while($row = $result->fetch_assoc()) {
			$counter += 1;
			$mapperName = GetUserNameFromId($row["SetCreatorID"], $conn);
?>
<div class="flex-container ratingContainer mapList" <?php if($counter % 2 == 1){ echo "style='background-color:#203838;'"; } ?>>
	<div class="flex-child" style="flex: 0 0 8%;">
		<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb" style="height:82px;width:82px;" onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
	</div>
	<div class="flex-child" style="flex: 0 0 50%;min-width: 0;">
		<a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo "{$row["Artist"]} - {$row["Title"]}</a> by <a href='/profile/{$row["SetCreatorID"]}'>{$mapperName}</a>"; ?> <a href="osu://s/<?php echo $row['SetID']; ?>"><i class="icon-download-alt">&ZeroWidthSpace;</i></a>
	</div>
	<div class="flex-child" style="flex: 0 0 3%;min-width: 0;">
		<?php echo $row["DateRanked"]; ?>
	</div>
	<div class="flex-child" style="flex: 0 0 32%;text-align:right;min-width:0;">
		<b><?php echo $conn->query("SELECT ROUND(AVG(Score), 2) FROM `ratings` WHERE BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID='{$row["SetID"]}');")->fetch_row()[0]; ?></b> <span style="font-size:12px;color:grey;">/ 5.00 from <?php echo $conn->query("SELECT Count(*) FROM `ratings` WHERE BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID='{$row["SetID"]}');")->fetch_row()[0]; ?> votes</span><br>
	</div>
</div>
<?php
		}
?>

<div style="text-align:left;">
    <div class="pagination">
        <a href="<?php echo "?m={$month}&y={$year}&p={$prevPage}"; ?>"><span>&laquo;</span></a>
        <?php for ($i = 1; $i <= $amntOfPages; $i++) { ?>
            <a href="<?php echo "?m={$month}&y={$year}&p={$i}"; ?>"><span class="pageLink <?php if ($page == $i) echo 'active' ?>"><?php echo $i ?></span></a>
        <?php } ?>
        <a href="<?php echo "?m={$month}&y={$year}&p={$nextPage}"; ?>"><span>&raquo;</span></a>
    </div>
</div>

<?php
	require '../footer.php';
?>