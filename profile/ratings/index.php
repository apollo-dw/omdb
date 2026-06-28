<?php
    $order = $_GET['o'] ?? "0";
	$rating = $_GET['r'] ?? "";
    $starRating = $_GET['sr'] ?? "";
    $genre = $_GET['g'] ?? "";
    $language = $_GET['lang'] ?? "";
    $country = $_GET['c'] ?? "";
    $tagArgument = urldecode($_GET['t'] ?? "") ?? "";

    $PageTitle = "Ratings";

    require "../../base.php";
    require '../../header.php';
	
	$profileId = GetIntParam('id', null, "Invalid page bro");
	$page = GetIntParam('p', 1, "Invalid page bro");
	$year = ($_GET['y'] ?? "all-time") === "all-time" ? "all-time" : GetIntParam('y', null, "Invalid page bro");

    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `UserID` = ?");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();
    $isUser = true;

    if ($profile == NULL)
        die("Can't view this bros friends cuz they aint an OMDB user");
	
	$isSelf = false;
	if ($loggedIn)
		$isSelf = $profileId == $userId;

	$limit = 25;
	$prevPage = $page - 1;
	$nextPage = $page + 1;

    // Filter Building <3
    $filterJoins = "";
    $filterConditions = "";
    $filterTypes = "";
    $filterValues = array();

    $baseTable = "`ratings` r JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID";
    $userCondition = "r.UserID = ?";

    if ($rating != "") {
        $filterConditions .= " AND r.Score = ?";
        $filterTypes .= "d";
        $filterValues[] = floatval($rating);
    }

    if ($year != "all-time") {
        $filterConditions .= " AND YEAR(s.DateRanked) = ?";
        $filterTypes .= "i";
        $filterValues[] = intval($year);
    }

    if ($starRating !== "") {
        $filterConditions .= " AND LEAST(b.SR DIV 1, 12) = ?";
        $filterTypes .= "i";
        $filterValues[] = intval($starRating);
    }

    if ($genre !== "") {
        $filterConditions .= " AND s.Genre = ?";
        $filterTypes .= "i";
        $filterValues[] = intval($genre);
    }

    if ($language !== "") {
        $filterConditions .= " AND s.Lang = ?";
        $filterTypes .= "i";
        $filterValues[] = intval($language);
    }

    if ($country !== "") {
        $filterConditions .= " AND EXISTS (SELECT 1 FROM beatmap_creators bc JOIN mappernames mn ON bc.CreatorID = mn.UserID WHERE bc.BeatmapID = b.BeatmapID AND mn.Country = ?)";
        $filterTypes .= "s";
        $filterValues[] = $country;
    }

    if ($tagArgument != "") {
        // Change the foundation to rating_tags so we can include unrated maps
        $baseTable = "`rating_tags` rt 
                      JOIN `beatmaps` b ON rt.BeatmapID = b.BeatmapID 
                      LEFT JOIN `ratings` r ON b.BeatmapID = r.BeatmapID AND r.UserID = rt.UserID";
        $userCondition = "rt.UserID = ?";
        $filterConditions .= " AND rt.Tag = ?";
        $filterTypes .= "s";
        $filterValues[] = $tagArgument;
    }

    $hideBlacklistedMapsCondition = $isSelf ? "" : "AND b.Blacklisted = 0";

    $countSql = "SELECT COUNT(*)
                 FROM {$baseTable}
                 LEFT JOIN beatmapsets s ON b.SetID = s.SetID
                 {$filterJoins}
                 WHERE {$userCondition} AND b.Mode = ? {$filterConditions} {$hideBlacklistedMapsCondition}";
    $stmt = $conn->prepare($countSql);
    $countTypes = "ii" . $filterTypes;
    $countValues = array_merge(array($profileId, $mode), $filterValues);
    $stmt->bind_param($countTypes, ...$countValues);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $amntOfPages = floor($count / $limit) + 1;
?>

<center><h1><a href="/profile/<?php echo safe_htmlspecialchars($profileId, ENT_QUOTES, 'UTF-8'); ?>"><?php echo safe_htmlspecialchars(GetUserNameFromId($profileId, $conn), ENT_QUOTES); ?></a>'s ratings</h1></center>

<hr>

<label for="rating">Rating</label>
<select id="rating" name="rating" onchange="changePage(1, 'rating')">
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
<select id="order" name="order" onchange="changePage(1, 'order')">
    <option value="0" <?php if ($order == 0) echo "selected='selected'"; ?>>Latest</option>
    <option value="1" <?php if ($order == 1) echo "selected='selected'"; ?>>Oldest</option>
    <option value="2" <?php if ($order == 2) echo "selected='selected'"; ?>>Highest rated</option>
    <option value="3" <?php if ($order == 3) echo "selected='selected'"; ?>>Lowest rated</option>
</select> <br>

<label for="year">Year</label>
<select name="year" id="year" autocomplete="off" onchange="changePage(1);">
    <?php
    echo '<option value="all-time"';
    if ($year == -1) {
        echo ' selected="selected"';
    }
    echo '>All Time</option>';

    for ($i = 2007; $i <= date('Y'); $i++) {
        echo '<option value="' . $i . '"';
        if ($year == $i) {
            echo ' selected="selected"';
        }
        echo '>' . $i . '</option>';
    }
    ?>
</select> <br>

<label for="sr">Star rating</label>
<select id="sr" name="sr" onchange="changePage(1)">
    <option value=''>All</option>
    <?php
        for ($i = 0; $i < 12; $i++) {
            $selected = $starRating !== "" && intval($starRating) === $i ? " selected='selected'" : "";
            echo "<option value='{$i}'{$selected}>{$i}&#9733; - " .  ($i + 1) . "&#9733;</option>";
        }
        $selected = $starRating !== "" && intval($starRating) >= 12 ? " selected='selected'" : "";
        echo "<option value='12'{$selected}>12&#9733;+</option>";
    ?>
</select> <br>

<label for="genre">Genre</label>
<select id="genre" name="genre" onchange="changePage(1)">
    <option value=''>Any</option>
    <?php
        for ($i = 0; $i <= 14; $i++) {
            $genreString = getGenre($i);
            if (is_null($genreString))
                continue;
            $selected = $genre !== "" && intval($genre) === $i ? " selected='selected'" : "";
            echo "<option value='{$i}'{$selected}>" . safe_htmlspecialchars($genreString, ENT_QUOTES) . "</option>";
        }
    ?>
</select> <br>

<label for="language">Language</label>
<select id="language" name="language" onchange="changePage(1)">
    <option value=''>Any</option>
    <?php
        for ($i = 0; $i <= 14; $i++) {
            $languageString = getLanguage($i);
            if (is_null($languageString))
                continue;
            $selected = $language !== "" && intval($language) === $i ? " selected='selected'" : "";
            echo "<option value='{$i}'{$selected}>" . safe_htmlspecialchars($languageString, ENT_QUOTES) . "</option>";
        }
    ?>
</select> <br>

<label for="country">Country</label>
<select id="country" name="country" onchange="changePage(1)">
    <option value=''>Any</option>
    <?php
        $stmt = $conn->prepare("
            SELECT DISTINCT mn.Country FROM ratings r
            JOIN beatmaps b ON r.BeatmapID = b.BeatmapID
            JOIN beatmap_creators bc ON b.BeatmapID = bc.BeatmapID
            JOIN mappernames mn ON bc.CreatorID = mn.UserID
            WHERE r.UserID = ? AND mn.Country IS NOT NULL;");
        $stmt->bind_param('i', $profileId);
        $stmt->execute();
        $result = $stmt->get_result();

        $countryOptions = array();
        while ($row = $result->fetch_assoc()) {
            $name = getFullCountryName($row["Country"]);
            if ($name == "")
                continue;
            $countryOptions[$row["Country"]] = $name;
        }
        $stmt->close();
        asort($countryOptions);

        foreach ($countryOptions as $code => $name) {
            $selected = $country === strval($code) ? " selected='selected'" : "";
            echo "<option value='" . safe_htmlspecialchars($code, ENT_QUOTES) . "'{$selected}>" . safe_htmlspecialchars($name, ENT_QUOTES) . "</option>";
        }
    ?>
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
            $tag = safe_htmlspecialchars($row["Tag"], ENT_QUOTES, "ISO-8859-1");
            $encodedTag = urlencode($row["Tag"]);
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

            $queryParameterTypes = "ii" . $filterTypes;
            $queryParameterValues = array_merge(array($profileId, $mode), $filterValues);

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

            // Note: Explicitly selecting b.BeatmapID last ensures it is never overridden with a NULL r.BeatmapID
            $stmt = "SELECT r.*, s.SetID, s.Artist, s.Title, b.DifficultyName, b.Blacklisted, b.BeatmapID
                    FROM {$baseTable}
                    LEFT JOIN beatmapsets s ON b.SetID = s.SetID
                    {$filterJoins}
                    WHERE {$userCondition} AND b.Mode = ? {$filterConditions} {$hideBlacklistedMapsCondition}
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
                $tags = safe_htmlspecialchars($tags ?? "", ENT_QUOTES, "ISO-8859-1");
        ?>
			<div class="flex-container ratingContainer alternating-bg">
				<div class="flex-child">
					<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='../../assets/img/missing-map-thumbnail.png';"></a>
				</div>
				<div class="flex-child" style="flex:0 0 60%;">
					<?php echo isset($row["Score"]) ? RenderUserRating($conn, $row) . " on" : ""; ?> <a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo safe_htmlspecialchars("{$row["Artist"]} - {$row["Title"]} [{$row["DifficultyName"]}]", ENT_QUOTES);?></a>
                    <br> <span class="subText"><?php echo $tags; ?></span>
                </div>
				<div class="flex-child" style="width:100%;text-align:right;">
					<?php if ($row["Blacklisted"] && $isSelf) { echo '<span class="subText">(only you can see this rating)</span>'; } ?>
					<?php echo isset($row["date"]) ? GetHumanTime($row["date"]) : ""; ?>
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
    function changePage(page, changed) {
        var order = document.getElementById("order").value;
        var rating = document.getElementById("rating").value;
        var tag = document.getElementById("tag").value;
        var year = document.getElementById("year").value;
        var sr = document.getElementById("sr").value;
        var genre = document.getElementById("genre").value;
        var language = document.getElementById("language").value;
        var country = document.getElementById("country").value;

        // Prio which is changed (specific rating vs rating ordering)
        if (changed === "rating" && rating !== "" && (order == 2 || order == 3))
            order = "0";
        else if (order == 2 || order == 3)
            rating = "";

        window.location.href = "?id=<?php echo $profileId; ?>&r=" + rating + "&o=" + order + "&t=" + tag + "&p=" + page + "&y=" + year + "&sr=" + sr + "&g=" + genre + "&lang=" + language + "&c=" + encodeURIComponent(country);
    }
</script>

<?php
	require '../../footer.php';
?>