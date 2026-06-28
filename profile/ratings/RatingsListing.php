<?php
    include '../../base.php';

    $profileId = (int)(postOrGet('id', -1));
    $page = max(1, (int)(postOrGet('p', 1)));
    $order = postOrGet('o', '1');
    $rating = postOrGet('r', '');
    $tagArgument = urldecode(postOrGet('t', ''));
    
    $year = postOrGet('y', 'all-time');
    $year = ($year === 'all-time') ? 'all-time' : (int)$year;

    $tokensRaw = json_decode(urldecode(postOrGet('tokens', '[]')), true);
    if (!is_array($tokensRaw)) $tokensRaw = [];

    $parsedTokens  = parseFilterTokens($tokensRaw);
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
    $srFilters = $parsedTokens['srFilters'];

    $isSelf = $loggedIn && ($profileId === $userId);
    $hideBlacklistedMapsCondition = $isSelf ? "" : "AND b.Blacklisted = 0";

    $baseTable = "`ratings` r JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID";
    $userCondition = "r.UserID = ?";

    $filterJoins = "";
    $filterConditions = "";
    $filterTypes = "";
    $filterValues = [];

    if ($rating !== '') {
        $filterConditions .= " AND r.Score = ?";
        $filterTypes .= "d";
        $filterValues[] = (float)$rating;
    }

    if ($year !== 'all-time') {
        $filterConditions .= " AND YEAR(s.DateRanked) = ?";
        $filterTypes .= "i";
        $filterValues[] = (int)$year;
    }

    if (!empty($genres)) {
        $ph = implode(',', array_fill(0, count($genres), '?'));
        $filterConditions .= " AND s.Genre IN ($ph)";
        $filterTypes .= str_repeat('i', count($genres));
        $filterValues = array_merge($filterValues, $genres);
    }

    if (!empty($exGenres)) {
        $ph = implode(',', array_fill(0, count($exGenres), '?'));
        $filterConditions .= " AND s.Genre NOT IN ($ph)";
        $filterTypes .= str_repeat('i', count($exGenres));
        $filterValues = array_merge($filterValues, $exGenres);
    }

    if (!empty($languages)) {
        $ph = implode(',', array_fill(0, count($languages), '?'));
        $filterConditions .= " AND s.Lang IN ($ph)";
        $filterTypes .= str_repeat('i', count($languages));
        $filterValues = array_merge($filterValues, $languages);
    }

    if (!empty($exLanguages)) {
        $ph = implode(',', array_fill(0, count($exLanguages), '?'));
        $filterConditions .= " AND s.Lang NOT IN ($ph)";
        $filterTypes .= str_repeat('i', count($exLanguages));
        $filterValues = array_merge($filterValues, $exLanguages);
    }

    if (!empty($countries)) {
        $ph = implode(',', array_fill(0, count($countries), '?'));
        $filterConditions .= " AND EXISTS (
            SELECT 1 FROM beatmap_creators bc
            JOIN mappernames mn ON bc.CreatorID = mn.UserID
            WHERE bc.BeatmapID = b.BeatmapID AND mn.Country IN ($ph)
        )";
        $filterTypes .= str_repeat('s', count($countries));
        $filterValues = array_merge($filterValues, $countries);
    }
    if (!empty($exCountries)) {
        $ph = implode(',', array_fill(0, count($exCountries), '?'));
        $filterConditions .= " AND NOT EXISTS (
            SELECT 1 FROM beatmap_creators bc
            JOIN mappernames mn ON bc.CreatorID = mn.UserID
            WHERE bc.BeatmapID = b.BeatmapID AND mn.Country IN ($ph)
        )";
        $filterTypes .= str_repeat('s', count($exCountries));
        $filterValues = array_merge($filterValues, $exCountries);
    }

    if (!empty($statuses)) {
        $ph = implode(',', array_fill(0, count($statuses), '?'));
        $filterConditions .= " AND b.Status IN ($ph)";
        $filterTypes .= str_repeat('i', count($statuses));
        $filterValues = array_merge($filterValues, $statuses);
    }
    if (!empty($exStatuses)) {
        $ph = implode(',', array_fill(0, count($exStatuses), '?'));
        $filterConditions .= " AND b.Status NOT IN ($ph)";
        $filterTypes .= str_repeat('i', count($exStatuses));
        $filterValues = array_merge($filterValues, $exStatuses);
    }

    if (!empty($descriptors)) {
        $clauses = [];
        foreach ($descriptors as $dId) {
            $clauses[]= "EXISTS (SELECT 1 FROM beatmap_descriptors bd WHERE bd.BeatmapID = b.BeatmapID AND bd.DescriptorID = ?)";
            $filterTypes .= "i";
            $filterValues[] = $dId;
        }
        $filterConditions .= " AND (" . implode(" AND ", $clauses) . ")";
    }
    if (!empty($exDescriptors)) {
        $clauses = [];
        foreach ($exDescriptors as $dId) {
            $clauses[] = "NOT EXISTS (SELECT 1 FROM beatmap_descriptors bd WHERE bd.BeatmapID = b.BeatmapID AND bd.DescriptorID = ?)";
            $filterTypes .= "i";
            $filterValues[] = $dId;
        }
        $filterConditions .= " AND (" . implode(" AND ", $clauses) . ")";
    }

    foreach ($srFilters as $cond) {
        $filterConditions .= " AND $cond";
    }

    if ($tagArgument !== '') {
        $baseTable = "`rating_tags` rt
            JOIN `beatmaps` b ON rt.BeatmapID = b.BeatmapID
            LEFT JOIN `ratings` r ON b.BeatmapID = r.BeatmapID AND r.UserID = rt.UserID";
        $userCondition = "rt.UserID = ?";
        $filterConditions .= " AND rt.Tag = ?";
        $filterTypes .= "s";
        $filterValues[] = $tagArgument;
    }

    switch ($order) {
        case '2':
            $orderString = "ORDER BY r.DATE ASC";
            break;
        case '3':
            $orderString = "ORDER BY r.SCORE DESC, r.DATE ASC";
            break;
        case '4':
            $orderString = "ORDER BY r.SCORE ASC, r.DATE ASC";
            break;
        default:
            $orderString = "ORDER BY r.DATE DESC";
    }

    $limit = 25;
    $countSql = "SELECT COUNT(*)
        FROM {$baseTable}
        LEFT JOIN beatmapsets s ON b.SetID = s.SetID
        {$filterJoins}
        WHERE {$userCondition} AND b.Mode = ? {$filterConditions} {$hideBlacklistedMapsCondition}";

    $stmt = $conn->prepare($countSql);
    $stmt->bind_param("ii" . $filterTypes, $profileId, $mode, ...$filterValues);
    $stmt->execute();
    $stmt->bind_result($totalCount);
    $stmt->fetch();
    $stmt->close();

    $totalPages = max(1, (int)ceil($totalCount / $limit));
    $page = min($page, $totalPages);
    $prevPage = $page - 1;
    $nextPage = $page + 1;
    $offset = ($page - 1) * $limit;
    $pageString = $offset > 0 ? "LIMIT {$offset}, {$limit}" : "LIMIT {$limit}";

    $sql = "SELECT r.*, s.SetID, s.Artist, s.Title, b.DifficultyName, b.Blacklisted, b.BeatmapID
        FROM {$baseTable}
        LEFT JOIN beatmapsets s ON b.SetID = s.SetID
        {$filterJoins}
        WHERE {$userCondition} AND b.Mode = ? {$filterConditions} {$hideBlacklistedMapsCondition}
        {$orderString} {$pageString}";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii" . $filterTypes, $profileId, $mode, ...$filterValues);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
?>

<div id="ratings-list">
    <?php
        $paginationHTML = function() use ($page, $prevPage, $nextPage, $totalPages) { ?>
        <div style="text-align:center;">
            <div class="pagination">
                <b><span>
                    <?php
                        if ($page > 1)
                            echo "<a href='javascript:changePage({$prevPage})'>&laquo; </a>";
                    ?>
                </span></b>
                <span id="page"><?php echo $page; ?></span>
                <b><span>
                    <?php
                        if ($page < $totalPages)
                            echo "<a href='javascript:changePage({$nextPage})'>&raquo; </a>";
                    ?>
                </span></b>
            </div>
        </div>
    <?php };
        $paginationHTML();
    ?>

    <div class="flex-container">
        <div class="flex-child" style="width:100%;">
            <?php while ($row = $result->fetch_assoc()):
                $stmt2 = $conn->prepare("SELECT GROUP_CONCAT(Tag SEPARATOR ', ') AS Tags FROM rating_tags WHERE UserID = ? AND BeatmapID = ?");
                $stmt2->bind_param('ii', $profileId, $row["BeatmapID"]);
                $stmt2->execute();
                $tags = $stmt2->get_result()->fetch_assoc()["Tags"] ?? "";
                $stmt2->close();
                $tags = safe_htmlspecialchars($tags, ENT_QUOTES, "ISO-8859-1");
            ?>
                <div class="flex-container ratingContainer alternating-bg">
                    <div class="flex-child">
                        <a href="/mapset/<?php echo $row["SetID"]; ?>">
                            <img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg"
                                 class="diffThumb"
                                 onerror="this.onerror=null; this.src='../../assets/img/missing-map-thumbnail.png';" />
                        </a>
                    </div>
                    <div class="flex-child" style="flex:0 0 60%;">
                        <?php echo isset($row["Score"]) ? RenderUserRating($conn, $row) . " on" : ""; ?>
                        <a href="/mapset/<?php echo $row["SetID"]; ?>">
                            <?php echo safe_htmlspecialchars("{$row["Artist"]} - {$row["Title"]} [{$row["DifficultyName"]}]", ENT_QUOTES); ?>
                        </a>
                        <br>
                        <span class="subText"><?php echo $tags; ?></span>
                    </div>
                    <div class="flex-child" style="width:100%;text-align:right;">
                        <?php if ($row["Blacklisted"] && $isSelf): ?>
                            <span class="subText">(only you can see this rating)</span>
                        <?php endif; ?>
                        <?php echo isset($row["date"]) ? GetHumanTime($row["date"]) : ""; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <?php $paginationHTML(); ?>
</div>