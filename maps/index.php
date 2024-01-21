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
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT s.SetID) FROM `beatmapsets` s LEFT JOIN beatmaps b ON s.SetID = b.SetID WHERE MONTH(DateRanked) = ? AND YEAR(DateRanked) = ? AND EXISTS (
                                    SELECT 1
                                    FROM `beatmaps` bm
                                    WHERE bm.SetID = s.SetID AND bm.Mode = ?
                                );");
    $stmt->bind_param("iii", $month, $year, $mode);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $amntOfPages = floor($count / $limit) + 1;    $prevPage = max($page - 1, 1);
    $nextPage = min($page + 1, $amntOfPages);
	
	$year = htmlspecialchars($year, ENT_QUOTES, 'UTF-8');
	$month = htmlspecialchars($month, ENT_QUOTES, 'UTF-8');
?>

<h1>Map List - <?php echo DateTime::createFromFormat('!m', $month)->format('F') . " " . $year; ?></h1>

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

    $stmt = $conn->prepare("SELECT s.SetID, s.Artist, s.Title, s.CreatorID as SetCreatorID, s.DateRanked,
                                    COUNT(DISTINCT r.BeatmapID) as RatedMapCount,
                                    COUNT(DISTINCT b.BeatmapID) as MapCount
                            FROM `beatmapsets` s
                            LEFT JOIN `beatmaps` b ON s.SetID = b.SetID
                            LEFT JOIN `ratings` r ON b.BeatmapID = r.BeatmapID AND r.UserID = ?
                            WHERE MONTH(s.DateRanked) = ? AND YEAR(s.DateRanked) = ? 
                                AND EXISTS (
                                    SELECT 1
                                    FROM `beatmaps` bm
                                    WHERE bm.SetID = s.SetID AND bm.Mode = ?
                                )
                            GROUP BY s.SetID
                            ORDER BY s.DateRanked DESC 
                            {$pageString};");

    $stmt->bind_param("iiii", $userId, $month, $year, $mode);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
			$mapperName = GetUserNameFromId($row["SetCreatorID"], $conn);

            $userRatedState = 0;
            if ($row["RatedMapCount"] == $row["MapCount"])
                $userRatedState = 2;
            elseif ($row["RatedMapCount"] > 0)
                $userRatedState = 1;
?>
<div class="flex-container ratingContainer mapList alternating-bg">
	<div class="flex-child" style="flex: 0 0 8%;">
		<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb" style="height:82px;width:82px;" onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
	</div>
	<div class="flex-child" style="flex: 0 0 50%;min-width: 0;">
		<a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo "{$row["Artist"]} - {$row["Title"]}</a> by <a href='/profile/{$row["SetCreatorID"]}'>{$mapperName}</a>"; ?> <a href="osu://s/<?php echo $row['SetID']; ?>"><i class="icon-download-alt">&ZeroWidthSpace;</i></a><br>
        <?php
            switch ($userRatedState) {
                case 1:
                    echo "<span class='subText' style='color:#ffefa1;'>rated</span>";
                    break;
                case 2:
                    echo "<span class='subText' style='color:#ffb7dc;'>fully rated</span>";
                    break;
            }
        ?>
    </div>
	<div class="flex-child" style="flex: 0 0 3%;min-width: 0;">
		<?php echo $row["DateRanked"]; ?>
	</div>
    <div class="flex-child" style="flex: 0 0 32%;text-align:right;min-width:0;">
        <?php
            $stmt = $conn->prepare("SELECT ROUND(AVG(Score), 2), COUNT(*) FROM `ratings` WHERE BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID = ?)");
            $stmt->bind_param("i", $row["SetID"]);
            $stmt->execute();
            $stmt->bind_result($averageScore, $voteCount);
            $stmt->fetch();
            $stmt->close();
        ?>

        <b><?php echo $averageScore; ?></b> <span style="font-size:12px;color:grey;">/ 5.00 from <?php echo $voteCount; ?> votes</span><br>
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