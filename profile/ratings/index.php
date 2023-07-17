<?php
	$profileId = $_GET['id'] ?? -1;
	$page = $_GET['p'] ?? 1;
	$rating = $_GET['r'] ?? "";
    $PageTitle = "Ratings";

    require "../../base.php";
    require '../../header.php';
	
	if($profileId == -1 || !is_numeric($profileId)){
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

    $amntOfPages = floor($count / $limit) + 1;
?>

<center><h1><a href="/profile/<?php echo htmlspecialchars($profileId, ENT_QUOTES, 'UTF-8'); ?>"><?php echo GetUserNameFromId($profileId, $conn); ?></a>'s ratings</h1></center>

<hr>

<label for="rating">Rating</label>
<select name="rating" onchange="location = '?id=<?php echo $profileId; ?>&r=' + this.value;">
    <?php
        $selected = $rating == "" ? " selected='selected'" : "";
        echo "<option value='' {$selected}>All</option>";
        for ($i = 0; $i <= 5; $i+= 0.5) {
            echo '<option value="' . $i . '"';
            if ($rating === strval($i))
                echo " selected='selected'";
            echo '>' . $i . '</option>';
        }
    ?>

</select>

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
                $stmt = $conn->prepare("SELECT GROUP_CONCAT(Tag SEPARATOR ', ') AS Tags FROM rating_tags WHERE UserID = ? AND BeatmapID = ?;");
                $stmt->bind_param('ii', $profileId, $row["BeatmapID"]);
                $stmt->execute();
                $tags = $stmt->get_result()->fetch_assoc()["Tags"];
                $tags = htmlspecialchars($tags, ENT_COMPAT, "ISO-8859-1");
        ?>
			<div class="flex-container ratingContainer alternating-bg">
				<div class="flex-child">
					<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='../../charts/INF.png';"></a>
				</div>
				<div class="flex-child" style="flex:0 0 60%;">
					<?php echo RenderUserRating($conn, $row); ?> on <a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo htmlspecialchars("{$row["Artist"]} - {$row["Title"]} [{$row["DifficultyName"]}]");?></a>
                    <br> <span class="subText"><?php echo $tags; ?></span>
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