<?php
    $PageTitle = "Ratings";

    require "../../base.php";
    require '../../header.php';

    
    $order = $_GET['o'] ?? "0";
    $rating = $_GET['r'] ?? "";
    $tagArgument = urldecode($_GET['t'] ?? "") ?? "";

    $tokensRaw = json_decode(urldecode($_GET['tokens'] ?? '[]'), true);
    if (!is_array($tokensRaw)) $tokensRaw = [];

    $parsedTokens = parseFilterTokens($tokensRaw);

    $genres = $parsedTokens['genres'];
    $exGenres = $parsedTokens['exGenres'];
    $languages = $parsedTokens['languages'];
    $exLanguages = $parsedTokens['exLanguages'];
    $countries = $parsedTokens['countries'];
    $exCountries = $parsedTokens['exCountries'];
    $statuses = $parsedTokens['statuses'];
    $exStatuses = $parsedTokens['exStatuses'];
    $descriptors = $parsedTokens['descriptors'];
    $exDescriptors = $parsedTokens['exDescriptors'];

    $srFilters = array_map(function($cond) {
        return " AND " . $cond;
    }, $parsedTokens['srFilters']);
	
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

    if (!empty($genres)) {
        $placeholders = implode(',', array_fill(0, count($genres), '?'));
        $filterConditions .= " AND s.Genre IN ($placeholders)";
        $filterTypes .= str_repeat('i', count($genres));
        $filterValues = array_merge($filterValues, $genres);
    }

    if (!empty($languages)) {
        $placeholders = implode(',', array_fill(0, count($languages), '?'));
        $filterConditions .= " AND s.Lang IN ($placeholders)";
        $filterTypes .= str_repeat('i', count($languages));
        $filterValues = array_merge($filterValues, $languages);
    }

    if (!empty($countries)) {
        $placeholders = implode(',', array_fill(0, count($countries), '?'));
        $filterConditions .= " AND EXISTS (SELECT 1 FROM beatmap_creators bc JOIN mappernames mn ON bc.CreatorID = mn.UserID WHERE bc.BeatmapID = b.BeatmapID AND mn.Country IN ($placeholders))";
        $filterTypes .= str_repeat('s', count($countries));
        $filterValues = array_merge($filterValues, $countries);
    }

    if (!empty($statuses)) {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $filterConditions .= " AND b.Status IN ($placeholders)";
        $filterTypes .= str_repeat('i', count($statuses));
        $filterValues = array_merge($filterValues, $statuses);
    }

    if (!empty($descriptors)) {
        $descriptorClauses = [];
        foreach ($descriptors as $descriptorId) {
            $descriptorClauses[] = "EXISTS (SELECT 1 FROM beatmap_descriptors bd WHERE bd.BeatmapID = b.BeatmapID AND bd.DescriptorID = ?)";
            $filterTypes .= "i";
            $filterValues[] = $descriptorId;
        }
        $filterConditions .= " AND (" . implode(" AND ", $descriptorClauses) . ")";
    }

    if (!empty($exGenres)) {
        $placeholders = implode(',', array_fill(0, count($exGenres), '?'));
        $filterConditions .= " AND s.Genre NOT IN ($placeholders)";
        $filterTypes .= str_repeat('i', count($exGenres));
        $filterValues = array_merge($filterValues, $exGenres);
    }

    if (!empty($exLanguages)) {
        $placeholders = implode(',', array_fill(0, count($exLanguages), '?'));
        $filterConditions .= " AND s.Lang NOT IN ($placeholders)";
        $filterTypes .= str_repeat('i', count($exLanguages));
        $filterValues = array_merge($filterValues, $exLanguages);
    }

    if (!empty($exCountries)) {
        $placeholders = implode(',', array_fill(0, count($exCountries), '?'));
        $filterConditions .= " AND NOT EXISTS (SELECT 1 FROM beatmap_creators bc JOIN mappernames mn ON bc.CreatorID = mn.UserID WHERE bc.BeatmapID = b.BeatmapID AND mn.Country IN ($placeholders))";
        $filterTypes .= str_repeat('s', count($exCountries));
        $filterValues = array_merge($filterValues, $exCountries);
    }

    if (!empty($exStatuses)) {
        $placeholders = implode(',', array_fill(0, count($exStatuses), '?'));
        $filterConditions .= " AND b.Status NOT IN ($placeholders)";
        $filterTypes .= str_repeat('i', count($exStatuses));
        $filterValues = array_merge($filterValues, $exStatuses);
    }

    if (!empty($exDescriptors)) {
        $exDescriptorClauses = [];
        foreach ($exDescriptors as $descriptorId) {
            $exDescriptorClauses[] = "NOT EXISTS (SELECT 1 FROM beatmap_descriptors bd WHERE bd.BeatmapID = b.BeatmapID AND bd.DescriptorID = ?)";
            $filterTypes .= "i";
            $filterValues[] = $descriptorId;
        }
        $filterConditions .= " AND (" . implode(" AND ", $exDescriptorClauses) . ")";
    }

    if (!empty($srFilters)) {
        $filterConditions .= implode("", $srFilters);
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

<?php
    $filterConfig = [
        'sortOptions' => [
            '1' => 'Latest',
            '2' => 'Oldest',
            '3' => 'Highest rated',
            '4' => 'Lowest rated'
        ],
        'showRating' => true,
        'showTag' => true,
        'categories' => ['status', 'descriptor', 'genre', 'language', 'country'] 
    ];
    require "../../functions/filter.php";
?><br>

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
                case "2":
                    $orderString = "ORDER BY r.DATE ASC";
                    break;
                case "3":
                    $orderString = "ORDER BY r.SCORE DESC, r.DATE ASC";
                    break;
                case "4":
                    $orderString = "ORDER BY r.SCORE ASC, r.DATE ASC";
                    break;
                case "1":
                default:
                    $orderString = "ORDER BY r.DATE DESC";
            }

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
        
        window.location.href = "?id=<?php echo $profileId; ?>" +
            "&r=" + encodeURIComponent(payload.rating) + 
            "&o=" + encodeURIComponent(payload.order) + 
            "&t=" + encodeURIComponent(payload.tag) + 
            "&p=" + page + 
            "&y=" + encodeURIComponent(payload.year) + 
            "&sr=" + encodeURIComponent(payload.sr) + 
            "&tokens=" + encodeURIComponent(JSON.stringify(payload.tokens));
    }

    $(document).on('omdbFiltersSubmitted', function() {
        changePage(1);
    });
</script>

<?php
	require '../../footer.php';
?>