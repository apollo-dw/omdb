<?php
	$profileId = $_GET['id'] ?? -1;
	$page = $_GET['p'] ?? 1;
	$rating = $_GET['r'] ?? "";
    $PageTitle = "Profile";

    require "../../base.php";
    require '../../header.php';
	
	if($profileId == -1){
		die("Invalid page bro");
	}

    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `UserID` = ?");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();
    $isUser = true;

    if ($profile == NULL)
        die("Can't view this bros friends cuz they aint an OMDB user");

	$limit = 25;
	$prevPage = $page - 1;
	$nextPage = $page + 1;

    if ($rating != ""){
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `ratings` WHERE `UserID` = ? AND `Score` = ?");
        $stmt->bind_param("ii", $profileId, $rating);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `ratings` WHERE `UserID` = ?;");
        $stmt->bind_param("i", $profileId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
    }

    //  echo number_format($rating, 1, '.', '');

    $amntOfPages = floor($count / $limit) + 1;
?>
<center><h1><a href="/profile/<?php echo $profileId; ?>"><?php echo GetUserNameFromId($profileId, $conn); ?></a>'s ratings</h1></center>

<hr>

<div style="text-align:center;">
	<div class="pagination">
	  <b><span><?php if($page > 1) { echo "<a href='?id={$profileId}&r={$rating}&p={$prevPage}'>&laquo; </a>"; } ?></span></b>
	  <span id="page"><?php echo $page; ?></span>
	  <b><span><?php if($page < $amntOfPages) { echo "<a href='?id={$profileId}&r={$rating}&p={$nextPage}'>&raquo; </a>"; } ?></span></b>
	</div>
</div>

<div class="flex-container">
	<div class="flex-child" style="width:100%;">
        <?php
            $pageString = "LIMIT {$limit}";

            if ($page > 1){
                $lower = ($page - 1) * $limit;
                $pageString = "LIMIT {$lower}, {$limit}";
            }

            if ($rating != ""){
                $stmt = $conn->prepare("
                    SELECT r.*, b.SetID, b.Artist, b.Title, b.DifficultyName
                    FROM `ratings` r
                    JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID
                    WHERE r.UserID = ? AND r.Score = ?
                    ORDER BY r.date DESC {$pageString};");
                $stmt->bind_param('id', $profileId, $rating);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $stmt = $conn->prepare("
                    SELECT r.*, b.SetID, b.Artist, b.Title, b.DifficultyName
                    FROM `ratings` r
                    JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID
                    WHERE r.UserID = ?
                    ORDER BY r.date DESC {$pageString};");
                $stmt->bind_param('i', $profileId);
                $stmt->execute();
                $result = $stmt->get_result();
            }


            while ($row = $result->fetch_assoc()) {
        ?>
			<div class="flex-container ratingContainer alternating-bg">
				<div class="flex-child">
					<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='../../charts/INF.png';"></a>
				</div>
				<div class="flex-child" style="flex:0 0 60%;">
					<?php echo renderRating($conn, $row); ?> on <a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo htmlspecialchars("{$row["Artist"]} - {$row["Title"]} [{$row["DifficultyName"]}]");?></a>
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
        <b><span><?php if($page > 1) { echo "<a href='?id={$profileId}&r={$rating}&p={$prevPage}'>&laquo; </a>"; } ?></span></b>
        <span id="page"><?php echo $page; ?></span>
        <b><span><?php if($page < $amntOfPages) { echo "<a href='?id={$profileId}&r={$rating}&p={$nextPage}'>&raquo; </a>"; } ?></span></b>
    </div>
</div>

<?php
	require '../../footer.php';
?>