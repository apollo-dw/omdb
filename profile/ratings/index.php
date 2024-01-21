<?php
	$profileId = $_GET['id'] ?? -1;
	$page = $_GET['p'] ?? 1;
	$rating = $_GET['r'] ?? "";
    $order = $_GET['o'] ?? "0";
    $tagArgument = urldecode($_GET['t']) ?? "";

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
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `ratings` r JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID WHERE r.`UserID` = ? AND r.`Score` = ? AND b.Mode = ?");
        $stmt->bind_param("iii", $profileId, $rating, $mode);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `ratings` r JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID WHERE r.`UserID` = ? AND b.Mode = ?;");
        $stmt->bind_param("ii", $profileId, $mode);
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
<select id="rating" name="rating" onchange="changePage(1)">
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
</select> <br>

<label for="order">Order</label>
<select id="order" name="order" onchange="changePage(1)">
    <option value="0" <?php if ($order == 0) echo "selected='selected'"; ?>>Latest</option>
    <option value="1" <?php if ($order == 1) echo "selected='selected'"; ?>>Oldest</option>
    <option value="2" <?php if ($order == 2) echo "selected='selected'"; ?>>Highest rated</option>
    <option value="3" <?php if ($order == 3) echo "selected='selected'"; ?>>Lowest rated</option>
</select> <br>

<label for="tag">Tag</label>
<select id="tag" name="tag" onchange="changePage(1)">
    <option value=''>Any</option>
    <?php
        $stmt = $conn->prepare("SELECT Tag, COUNT(*) AS TagCount FROM rating_tags WHERE UserID = ? GROUP BY Tag ORDER BY TagCount DESC;");
        $stmt->bind_param('i', $profileId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $tag = htmlspecialchars($row["Tag"], ENT_COMPAT, "ISO-8859-1");
            $encodedTag = urlencode($tag);
            $selected = $tagArgument == $row["Tag"] ? " selected='selected'" : "";
            echo "<option value='{$encodedTag}' {$selected}>{$tag} ({$row["TagCount"]})</option>";
        }
    ?>
</select>

<div style="text-align:center;">
    <div class="pagination">
        <b><span><?php if($page > 1) { echo "<a href='javascript:changePage({$prevPage})'>&laquo; </a>"; } ?></span></b>
        <span id="page"><?php echo $page; ?></span>
        <b><span><?php if($page < $amntOfPages) { echo "<a href='javascript:changePage({$nextPage})'>&raquo; </a>"; } ?></span></b>
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

            $queryParameterTypes = "ii";
            $queryParameterValues = array($profileId, $mode);

            $ratingString = "";
            if ($rating != "") {
                $ratingString = "AND r.Score = ?";
                $queryParameterTypes .= "d";
                $queryParameterValues[] = floatval($rating);
            }

            $tagJoinString = "";
            $tagAndString = "";
            if ($tagArgument != "") {
                $tagJoinString = "JOIN `rating_tags` rt ON b.BeatmapID = rt.BeatmapID";
                $tagAndString = "AND rt.Tag = ?";
                $queryParameterTypes .= "s";
                $queryParameterValues[] = $tagArgument;
            }

            switch($order) {
                case "1":
                    $orderString = "ORDER BY r.DATE ASC";
                    break;
                case "2":
                    $orderString = "ORDER BY r.SCORE DESC, r.DATE ASC";
                    break;
                case "3":
                    $orderString = "ORDER BY r.SCORE ASC, r.DATE ASC";
                    break;
                case "0":
                default:
                    $orderString = "ORDER BY r.DATE DESC";
            }

            $stmt = "SELECT r.*, s.SetID, s.Artist, s.Title, b.DifficultyName
                    FROM `ratings` r
                    JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID
                    JOIN beatmapsets s ON b.SetID = s.SetID
                    {$tagJoinString}
                    WHERE r.UserID = ? AND b.Mode = ? {$ratingString} {$tagAndString}
                    {$orderString} {$pageString};";

            $stmt = $conn->prepare($stmt);
            $stmt->bind_param($queryParameterTypes, ...$queryParameterValues);
            $stmt->execute();
            $result = $stmt->get_result();

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
        <b><span><?php if($page > 1) { echo "<a href='javascript:changePage({$prevPage})'>&laquo; </a>"; } ?></span></b>
        <span id="page"><?php echo $page; ?></span>
        <b><span><?php if($page < $amntOfPages) { echo "<a href='javascript:changePage({$nextPage})'>&raquo; </a>"; } ?></span></b>
    </div>
</div>

<script>
    function changePage(page) {
        var order = document.getElementById("order").value;
        var rating = document.getElementById("rating").value;
        var tag = document.getElementById("tag").value;

        if (order == 2 || order == 3)
            rating = "";

        window.location.href = "?id=<?php echo $profileId; ?>&r=" + rating + "&o=" + order + "&t=" + tag + "&p=" + page;
    }
</script>

<?php
	require '../../footer.php';
?>