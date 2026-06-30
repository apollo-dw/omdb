<?php
    include_once '../base.php';

    $year = postOrGet('y', 2026);
    $year = ($year === 'all-time') ? 'all-time' : (int)$year;

    $page = (int)(postOrGet('p', 1));
    $order = (int)(postOrGet('o', 1));

    $tokensRaw = decodeTokens(postOrGet('tokens', '[]'));
    if (!is_array($tokensRaw)) $tokensRaw = [];

    $parsedTokens = parseFilterTokens($tokensRaw);
    $filter = buildBeatmapFilterSQL($parsedTokens);
    $friendsStatus = $parsedTokens['friendsStatus'];
    $ratedStatus = $parsedTokens['ratedStatus'];
?>

<div class="flex-item" style="padding:0.5em;">
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

        if ($ratedStatus === 'exclude') $sqlFilters[] = "r_user.Score IS NULL";
        if ($ratedStatus === 'only')    $sqlFilters[] = "r_user.Score IS NOT NULL";

        $statsJoin = "";
        $priorJoin = "";

        if ($friendsStatus !== 'any') {
            $relationCondition = ($friendsStatus === 'only') ? "IN" : "NOT IN";
            $selfCondition = ($friendsStatus === 'only') ? "OR r.UserID = ?" : "AND r.UserID != ?";

            $statsJoin = "
                INNER JOIN (
                    SELECT
                        r.BeatmapID,
                        SUM(r.Score) AS TotalScore,
                        COUNT(*) AS RatingCount,
                        AVG(r.Score) AS WeightedAvg,
                        STDDEV_POP(r.Score) * SQRT(COUNT(*)) AS Controversy
                    FROM ratings r
                    WHERE (r.UserID {$relationCondition} (
                        SELECT UserIDTo FROM user_relations WHERE UserIDFrom = ? AND Type = 1
                    ) {$selfCondition})
                    GROUP BY r.BeatmapID
                ) friend_stats ON friend_stats.BeatmapID = b.BeatmapID";

            $priorJoin = "
                CROSS JOIN (
                    SELECT AVG(Score) AS prior_rating, COUNT(*) AS prior_count
                    FROM ratings
                ) prior";

            $statsTypes = "ii";
            $statsParams[] = $userId;
            $statsParams[] = $userId;
        }

        if ($friendsStatus !== 'any') {
            $ratingField = "friend_stats.WeightedAvg";
            $countField = "friend_stats.RatingCount";
            $bayesField = "((prior.prior_rating * prior.prior_count) + friend_stats.TotalScore) / (prior.prior_count + friend_stats.RatingCount)";
        } else {
            $ratingField = "b.WeightedAvg";
            $countField = "b.RatingCount";
            $bayesField = "b.Rating";
        }

        switch ($order) {
            case 3:
                $columnString = $countField;
                break;
            case 4:
                $columnString = ($friendsStatus !== 'any') ? "friend_stats.Controversy" : "b.controversy";
                break;
            case 5:
                $columnString = ($friendsStatus !== 'any') ? "(friend_stats.WeightedAvg - b.Rating) * SQRT(friend_stats.RatingCount)" : "(b.WeightedAvg - b.Rating) * SQRT(b.RatingCount)";
                break;
            default:
                $columnString = "BayesianAverage";
                break;
        }

        $whereClause = !empty($sqlFilters) ? "AND " . implode("\nAND ", $sqlFilters) : "";
        $nullRatingClause = ($friendsStatus === 'any') ? "AND b.Rating IS NOT NULL" : "";

        $sql = "
        SELECT
            b.*,
            s.*,
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
        {$priorJoin}
        WHERE
            b.Mode = ?
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
            die("<pre>" . $conn->error . "\n\n" . safe_htmlspecialchars($sql) . "</pre>");
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
            <div class="flex-child" style="text-align:right;flex:0 0 6%">
                <b><?php echo "#" . $counter; ?></b>
            </div>
            <div class="flex-child" style="flex:0 0 80px;">
                <a href="/mapset/<?php echo $row['SetID']; ?>">
                    <img src="https://b.ppy.sh/thumb/<?php echo $row['SetID']; ?>l.jpg"
                         class="diffThumb" style="height:80px;width:80px;"
                         onerror="this.onerror=null; this.src='../assets/img/missing-map-thumbnail.png';" />
                </a>
            </div>
            <div class="flex-child" style="flex:1 0 40%;">
                <a href="/mapset/<?php echo $row['SetID']; ?>">
                    <?php echo safe_htmlspecialchars($row['Artist'], ENT_QUOTES); ?>
                    - <?php echo safe_htmlspecialchars($row['Title'], ENT_QUOTES); ?><br>
                </a>
                <a href="/mapset/<?php echo $row['SetID']; ?>">
                    <b><?php echo safe_htmlspecialchars(mb_strimwidth($row['DifficultyName'], 0, 35, "..."), ENT_QUOTES); ?></b>
                </a>
                <span class="subText"><?php echo number_format((float)$row['SR'], 2, '.', ''); ?>*</span><br>
                <?php echo date("M jS, Y", strtotime($row['DateRanked'])); ?><br>
                <?php RenderBeatmapCreators($row['BeatmapID'], $conn); ?><br>
                <span class="subText map-descriptors">
                    <?php
                        $descriptorLinks = [];
                        while ($descriptor = $descriptorResult->fetch_assoc()) {
                            $name = safe_htmlspecialchars($descriptor["Name"]);
                            $id = (int)$descriptor["DescriptorID"];
                            $short = safe_htmlspecialchars($descriptor["ShortDescription"]);
                            $descriptorLinks[] = '
                                <span class="tooltip-wrapper">
                                    <a style="color:inherit;" href="../descriptor/?id=' . $id . '">' . $name . '</a>
                                    <span class="tooltip-box">' . $short . '</span>
                                </span>';
                        }
                        echo implode(', ', $descriptorLinks);
                    ?>
                </span>
            </div>
            <div class="flex-child" style="flex:1;">
                <b><?php echo number_format((float)$row["WeightedAvg"], 2); ?></b>
                <span class="subText">/ 5.00 from
                    <span style="color:white"><?php echo $row["RatingCount"]; ?></span> votes
                </span><br>
            </div>
            <div class="flex-child" style="flex:0 0 10%">
                <b style="font-weight:900;"><?php echo $row["Score"]; ?></b>
            </div>
        </div>
        <?php } ?>
</div>
