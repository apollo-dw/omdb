<?php
    require_once __DIR__ . '/../../app/base.php';

    $year = postOrGet('y', 2026);
    $year = ($year === 'all-time') ? 'all-time' : (int)$year;

    $page = (int)(postOrGet('p', 1));
    $order = (int)(postOrGet('o', 1));

    $tokensRaw = decodeTokens(postOrGet('tokens', '[]'));
    if (!is_array($tokensRaw)) {
        $tokensRaw = [];
    }

    $parsedTokens = parseFilterTokens($tokensRaw);
    $filter = buildBeatmapFilterSQL($parsedTokens, $conn);
    $ratedStatus = $parsedTokens['ratedStatus'];
    $rankedMappersStatus = $parsedTokens['rankedMappersStatus'];
    $disagreeStatus = $parsedTokens['disagreeStatus'];
    $commentsStatus = $parsedTokens['commentsStatus'];
    $useRatingSubset = filterUsesRaterSubset($parsedTokens);
?>

<div>
    <?php
        $lim = 50;
        $offset = ($page > 1) ? ($page - 1) * $lim : 0;
        $counter = $offset;
        $pageString = ($page > 1) ? "LIMIT {$offset}, {$lim}" : "LIMIT {$lim}";
        $orderString = ($order == 2) ? "ASC" : "DESC";

        $baseTypes = "i";
        $baseParams = [$userId];

        $statsTypes = "";
        $statsParams = [];

        $whereTypes = "i";
        $whereParams = [$mode];

        $sqlFilters = [];

        if ($year !== "all-time") {
            $sqlFilters[] = "YEAR(s.DateRanked) = ?";
            $whereTypes .= "s";
            $whereParams[] = (string)$year;
        }

        if ($filter['sql'] !== '') {
            $sqlFilters[] = preg_replace('/^\s*AND\s+/i', '', trim($filter['sql']));
            $whereTypes .= $filter['types'];
            $whereParams = array_merge($whereParams, $filter['values']);
        }

        if ($ratedStatus === 'exclude') {
            $sqlFilters[] = "r_user.Score IS NULL";
        }
        if ($ratedStatus === 'only') {
            $sqlFilters[] = "r_user.Score IS NOT NULL";
        }

        if ($commentsStatus !== 'any') {
            $commentsExists = filterHasCommentsCondition('b.SetID');
            $sqlFilters[] = ($commentsStatus === 'only') ? $commentsExists : "NOT {$commentsExists}";
        }

        $statsJoin = "";

        if ($useRatingSubset) {
            $raterJoin = "";
            $raterWhere = "";
            $raterConditions = [];

            if ($rankedMappersStatus !== 'any') {
                $raterJoin = "JOIN (" . filterRankedMapperRatersSQL($rankedMappersStatus) . ") rm ON rm.UserID = r.UserID";
            }

            foreach (array_keys(filterViewerRaterGroups()) as $statusKey) {
                $groupStatus = $parsedTokens[$statusKey];
                if ($groupStatus === 'any') {
                    continue;
                }

                $groupIds = filterViewerRaterIds($conn, (int)$userId, $statusKey);
                $groupIds[] = (int)$userId;
                $raterConditions[] = "r.UserID " . (($groupStatus === 'only') ? "IN" : "NOT IN") . " (" . implode(',', $groupIds) . ")";
            }

            if (!empty($raterConditions)) {
                $raterWhere = "WHERE " . implode(" AND ", $raterConditions);
            }

            $statsJoin = "
                INNER JOIN (
                    SELECT
                        r.BeatmapID,
                        SUM(r.Score * u.Weight) / NULLIF(SUM(u.Weight), 0) AS WeightedAvg,
                        SUM(u.Weight) AS weight_sum,
                        COUNT(*) AS RatingCount,
                        STDDEV_POP(r.Score) * SQRT(COUNT(*)) AS Controversy
                    FROM ratings r
                    JOIN users u ON r.UserID = u.UserID
                    {$raterJoin}
                    {$raterWhere}
                    GROUP BY r.BeatmapID
                ) subset_stats ON subset_stats.BeatmapID = b.BeatmapID";
        }

        if ($useRatingSubset) {
            $ratingField = "subset_stats.WeightedAvg";
            $countField = "subset_stats.RatingCount";
            
            $m = 3.00;
            $confidence = 10;

            $bayesField = "
                CASE
                    WHEN subset_stats.weight_sum IS NULL OR subset_stats.weight_sum < 1.5 THEN NULL
                    ELSE ((subset_stats.weight_sum * subset_stats.WeightedAvg) + ({$m} * {$confidence})) / (subset_stats.weight_sum + {$confidence})
                END";
            
        } else {
            $ratingField = "b.WeightedAvg";
            $countField = "b.RatingCount";
            $bayesField = "b.Rating";
        }

        if ($disagreeStatus !== 'any') {
            $disagrees = "(r_user.Score IS NOT NULL AND " . filterDisagreeCondition('r_user.Score', $ratingField) . ")";
            $sqlFilters[] = ($disagreeStatus === 'only') ? $disagrees : "NOT {$disagrees}";
        }

        switch ($order) {
            case 3:
                $columnString = $countField;
                break;
            case 4:
                $columnString = $useRatingSubset ? "subset_stats.Controversy" : "b.controversy";
                break;
            case 5:
                $columnString = $useRatingSubset ? "(subset_stats.WeightedAvg - b.Rating) * SQRT(subset_stats.RatingCount)" : "(b.WeightedAvg - b.Rating) * SQRT(b.RatingCount)";
                break;
            default:
                $columnString = "BayesianAverage";
                break;
        }

        $whereClause = !empty($sqlFilters) ? "AND " . implode("\nAND ", $sqlFilters) : "";
        $nullRatingClause = $useRatingSubset ? "" : "AND b.Rating IS NOT NULL";

        $sql = "
        SELECT
            b.DifficultyName, b.SR, b.BeatmapID,
            s.Artist, s.Title, s.DateRanked, s.SetID,
            {$ratingField} AS WeightedAvg,
            {$countField} AS RatingCount,
            {$bayesField} AS BayesianAverage,
            r_user.Score
        FROM beatmaps b
        LEFT JOIN beatmapsets s
            ON s.SetID = b.SetID
        LEFT JOIN ratings r_user
            ON r_user.BeatmapID = b.BeatmapID
           AND r_user.UserID = ?
        {$statsJoin}
        WHERE
            b.Mode = ?
            AND b.Blacklisted = 0
            {$nullRatingClause}
            {$whereClause}
        ORDER BY
            {$columnString} {$orderString},
            b.BeatmapID
        {$pageString}";

        $finalTypes = $baseTypes . $statsTypes . $whereTypes;
        $finalParams = array_merge($baseParams, $statsParams, $whereParams);

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Chart query error: " . $conn->error);
            http_response_code(500);
            exit();
        }

        $stmt->bind_param($finalTypes, ...$finalParams);
        $stmt->execute();
        $result = $stmt->get_result();

        $descStmt = $conn->prepare("SELECT
                bd.DescriptorID,
                d.Name,
                d.ShortDescription
            FROM beatmap_descriptors bd
            JOIN descriptors d ON bd.DescriptorID = d.DescriptorID
            WHERE bd.BeatmapID = ?
            ORDER BY bd.Weight DESC, bd.DescriptorID
            LIMIT 10");
        while ($row = $result->fetch_assoc()) {
            $descStmt->bind_param("i", $row["BeatmapID"]);
            $descStmt->execute();
            $descriptorResult = $descStmt->get_result();
            $counter++;
        ?>
        <div class="flex-container chart-container alternating-bg" style="padding:0.5em 0;">
            <div class="flex-child" style="text-align:right;flex:0 0 5%">
                <b><?php echo "#" . $counter; ?></b>
            </div>
            <div class="flex-child" style="flex:0 0 80px;">
                <a href="/mapset/<?php echo $row['SetID']; ?>">
                    <img src="https://b.ppy.sh/thumb/<?php echo $row['SetID']; ?>l.jpg"
                         class="diffThumb" style="height:80px;width:80px;"
                         onerror="this.onerror=null; this.src='../assets/img/missing-map-thumbnail.png';" />
                </a>
            </div>
            <div class="flex-child" style="flex:1 0 60%;">
                <a href="/mapset/<?php echo $row['SetID']; ?>">
                    <?php echo safe_htmlspecialchars($row['Artist'], ENT_QUOTES); ?>
                    - <?php echo safe_htmlspecialchars($row['Title'], ENT_QUOTES); ?><br>
                </a>
                <a href="/mapset/<?php echo $row['SetID']; ?>">
                    <b style="word-break: break-word;"><?php echo safe_htmlspecialchars($row['DifficultyName'], ENT_QUOTES); ?></b>
                </a>
                <span class="subText"><?php echo number_format((float)$row['SR'], 2, '.', ''); ?>*</span><br>
                <?php echo date("M jS, Y", strtotime($row['DateRanked'])); ?><br>
                <?php RenderBeatmapCreators($row['BeatmapID'], $conn); ?><br>
                <span class="subText map-descriptors">
                    <?php echo BuildDescriptorLinks($conn, $descriptorResult); ?>
                </span>
            </div>
            <div class="flex-child" style="flex: 1 1 15%;">
                <b><?php echo number_format((float)$row["WeightedAvg"], 2); ?></b>
                <span class="subText">from
                    <span style="color:white"><?php echo $row["RatingCount"]; ?></span> votes
                </span><br>
            </div>
            <div class="flex-child" style="flex:0 1 8%;">
                <?php
                    if (isset($row["Score"])) {
                        echo RenderRating($row["Score"]);
                    }
                ?>
            </div>
        </div>
        <?php } ?>
</div>
