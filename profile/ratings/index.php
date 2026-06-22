<?php
    $order = $_GET['o'] ?? "0";
	$rating = $_GET['r'] ?? "";
    $starRating = $_GET['sr'] ?? "";
    $genre = $_GET['g'] ?? "";
    $language = $_GET['lang'] ?? "";
    $country = $_GET['c'] ?? "";
    $tagArgument = urldecode($_GET['t'] ?? "") ?? "";
    $descriptorsJSON = $_GET['descriptors'] ?? "[]";

    if (!isset($selectedDescriptors)) {
        $selectedDescriptors = json_decode($descriptorsJSON, true);
    }

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

    if (!empty($selectedDescriptors)) {
        $descriptorClauses = [];
        foreach ($selectedDescriptors as $descriptor) {
            $descriptorId = (int)$descriptor['id'];
            $descriptorClauses[] = "
                EXISTS (
                    SELECT 1
                    FROM beatmap_descriptors bd
                    WHERE bd.BeatmapID = b.BeatmapID
                        AND bd.DescriptorID = {$descriptorId}
                )
            ";
        }
        $filterConditions .= "AND (" . implode(" AND ", $descriptorClauses) . ")";
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

<?php include "../../functions/filter.php"; ?><br>

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
					<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='../../charts/INF.png';"></a>
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
    function changePage(page) {
        if (page < 1) page = 1;
        var payload = window.getOmdbFilterPayload();
        
        var genre = 0, language = 0, country = 0;
        var mappedDescriptors = [];

        payload.tokens.forEach(function(t) {
            if (t.type === 'genre') genre = t.id;
            if (t.type === 'language') language = t.id;
            if (t.type === 'country') country = t.id;
            if (t.type === 'descriptor') mappedDescriptors.push({ id: t.id, name: t.name });
        });

        window.location.href = "?id=<?php echo $profileId; ?>" +
            "&r=" + payload.rating + 
            "&o=" + payload.order + 
            "&t=" + payload.tag + 
            "&p=" + page + 
            "&y=" + payload.year + 
            "&sr=" + payload.sr + 
            "&g=" + genre + 
            "&lang=" + language + 
            "&c=" + encodeURIComponent(country) + 
            "&descriptors=" + encodeURIComponent(JSON.stringify(mappedDescriptors)) +
            "&f=" + String(payload.friends) +
            "&excludeLoved=" + String(payload.exLoved) +
            "&excludeGraveyard=" + String(payload.exGraveyard) +
            "&excludeRanked=" + String(payload.exRanked);
    }

    $(document).on('omdbFiltersSubmitted', function() {
        changePage(1);
    });
</script>

<?php
	require '../../footer.php';
?>