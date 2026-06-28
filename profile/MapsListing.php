<?php
    include '../base.php';

    $profileId = (int)(postOrGet('id', -1));

    $order = postOrGet('o', '1');
    $rating = postOrGet('r', '');

    $year = postOrGet('y', 'all-time');
    $year = ($year === 'all-time') ? 'all-time' : (int)$year;

    $tokensRaw = json_decode(urldecode(postOrGet('tokens', '[]')), true);
    if (!is_array($tokensRaw))
        $tokensRaw = [];

    $parsedTokens = parseFilterTokens($tokensRaw);
    $filter = buildBeatmapFilterSQL($parsedTokens);

    $filterConditions = "";
    $filterTypes = "";
    $filterValues = [];

    if ($year !== 'all-time') {
        $filterConditions .= " AND YEAR(s.DateRanked) = ?";
        $filterTypes .= "i";
        $filterValues[] = (int)$year;
    }

    if ($rating !== '' && $loggedIn) {
        $filterConditions .= " AND EXISTS (
            SELECT 1 FROM ratings r_filter
            WHERE r_filter.BeatmapID = b.BeatmapID
            AND r_filter.UserID = ?
            AND r_filter.Score = ?
        )";
        $filterTypes .= "id";
        $filterValues[] = $userId;
        $filterValues[] = (float)$rating;
    }

    $filterConditions .= $filter['sql'];
    $filterTypes .= $filter['types'];
    $filterValues = array_merge($filterValues, $filter['values']);

    switch ($order) {
        case '2':
            $orderSQL = "s.Timestamp ASC";
            break;
        case '3':
            $orderSQL = "MAX(b.Rating) DESC, MAX(b.WeightedAvg) DESC";
            break;
        case '4':
            $orderSQL = "MAX(b.Rating) ASC,  MAX(b.WeightedAvg) ASC";
            break;
        default: $orderSQL = "s.Timestamp DESC";
    }

    $setsQuery = "SELECT s.SetID, s.CreatorID, s.Artist, s.Title
        FROM beatmap_creators c
        LEFT JOIN beatmaps b ON b.BeatmapID = c.BeatmapID
        LEFT JOIN beatmapsets s ON b.SetID = s.SetID
        WHERE c.CreatorID = ?
        {$filterConditions}
        GROUP BY s.SetID
        ORDER BY {$orderSQL}";

    $stmt = $conn->prepare($setsQuery);
    if (!empty($filterTypes)) {
        $stmt->bind_param("i" . $filterTypes, $profileId, ...$filterValues);
    } else {
        $stmt->bind_param("i", $profileId);
    }
    $stmt->execute();
    $setsResult = $stmt->get_result();
    $stmt->close();
?>

<div id="beatmaps">
<?php
    while ($set = $setsResult->fetch_assoc()) {
        if ($set['SetID'] === null) continue;

        $stmt = $conn->prepare("
            SELECT
                b.BeatmapID,
                s.DateRanked,
                b.DifficultyName,
                b.WeightedAvg,
                b.RatingCount,
                b.SR,
                b.ChartRank,
                r.Score,
                (SELECT COUNT(DISTINCT CreatorID) FROM beatmap_creators WHERE BeatmapID = b.BeatmapID) AS NumCreators
            FROM beatmaps b
            LEFT JOIN beatmapsets s ON b.SetID = s.SetID
            INNER JOIN beatmap_creators bc ON b.BeatmapID = bc.BeatmapID
            LEFT JOIN ratings r ON b.BeatmapID = r.BeatmapID AND r.UserID = ?
            WHERE b.SetID = ? AND bc.CreatorID = ?
            ORDER BY b.ChartRank IS NULL, b.ChartRank ASC, b.RatingCount DESC
        ");
        $stmt->bind_param("iii", $userId, $set["SetID"], $profileId);
        $stmt->execute();
        $difficultyResult = $stmt->get_result();
        $stmt->close();

        $stmt2 = $conn->prepare("SELECT COUNT(*) FROM comments WHERE SetID = ?");
        $stmt2->bind_param("i", $set["SetID"]);
        $stmt2->execute();
        $commentCount = $stmt2->get_result()->fetch_row()[0];
        $stmt2->close();

        $topMap = $difficultyResult->fetch_assoc();
        $topMapIsBolded = isset($topMap["ChartRank"]) && $topMap["ChartRank"] <= 250;
        $topMapIsGD = $set["CreatorID"] != $profileId;
        $topMapIsCollab = $topMap["NumCreators"] > 1;
        $topMapRatingCount = $topMap["RatingCount"] ?? 0;
        $topMapChartRank = $topMap["ChartRank"] ?? "";
?>
        <div data-rating-count="<?php echo $topMapRatingCount; ?>"
             data-chart-rank="<?php echo $topMapChartRank; ?>"
             class="profile-top-map<?php if ($difficultyResult->num_rows > 1) echo ' clickable'; ?>">

            <a href="/mapset/<?php echo $set['SetID']; ?>">
                <img src="https://b.ppy.sh/thumb/<?php echo $set['SetID']; ?>l.jpg"
                     class="diffThumb" style="height:48px;width:48px;margin-right:0.5em;"
                     onerror="this.onerror=null; this.src='../assets/img/missing-map-thumbnail.png';"
                     loading="lazy" />
            </a>
            <div>
                <a href="/mapset/<?php echo $set['SetID']; ?>">
                    <?php echo $set['Artist']; ?> - <?php echo safe_htmlspecialchars($set['Title'], ENT_QUOTES); ?>
                    <a href="https://osu.ppy.sh/b/<?php echo $topMap['BeatmapID']; ?>"
                       target="_blank" rel="noopener noreferrer">
                        <i class="icon-external-link" style="font-size:10px;"></i>
                    </a><br>
                </a>
                <a <?php if ($topMapIsBolded) echo "style='font-weight:bolder;'"; ?>
                   href="/mapset/<?php echo $set['SetID']; ?>">
                    <?php echo safe_htmlspecialchars($topMap['DifficultyName'], ENT_QUOTES); ?>
                </a>
                <span class="subText">
                    <?php echo number_format((float)$topMap['SR'], 2, '.', ''); ?>*
                    <?php if ($topMapIsCollab) echo "(collab)"; elseif ($topMapIsGD) echo "(GD)"; ?>
                </span><br>
                <?php echo date("M jS, Y", strtotime($topMap['DateRanked'])); ?><br>
            </div>

            <div style="margin-left:auto;">
                <span style="display:inline-block;margin-right:1em;">
                    <?php if (isset($topMap["Score"])) echo RenderRating($topMap["Score"]); ?>
                </span>
                <span style="display:inline-block;margin-right:1em;min-width:8em;">
                    <?php echo $commentCount; ?>
                    <span class="subText">comment<?php if ($commentCount != 1) echo 's'; ?></span>
                </span>
                <span style="display:inline-block;min-width:13em;">
                    <?php if (isset($topMap["WeightedAvg"])): ?>
                        <b><?php echo number_format((float)$topMap["WeightedAvg"], 2); ?></b>
                        <span class="subText">/ 5.00 from
                            <span style="color:white"><?php echo $topMap["RatingCount"]; ?></span> votes
                        </span><br>
                    <?php endif; ?>
                </span>
                <span class="collapse-arrow"
                      style="display:inline-block;<?php if ($difficultyResult->num_rows == 1) echo 'visibility:hidden;'; ?>user-select:none;margin-left:0.5em;margin-right:0.5em;width:1em;">
                    ◀
                </span>
            </div>
        </div>

        <div class="lesser-maps" style="display:none;">
            <?php while ($map = $difficultyResult->fetch_assoc()): ?>
                <div class="profile-lesser-map">
                    <div style="display:inline-block;">
                        <a <?php if ($map["ChartRank"] <= 250 && isset($map["ChartRank"])) echo "style='font-weight:bolder;'"; ?>
                           href="/mapset/<?php echo $set['SetID']; ?>">
                            <?php echo safe_htmlspecialchars($map['DifficultyName'], ENT_QUOTES); ?>
                        </a>
                        <span class="subText">
                            <?php echo number_format((float)$map['SR'], 2, '.', ''); ?>*
                            <?php if ($topMapIsGD) echo "(GD)"; ?>
                        </span><br>
                    </div>
                    <div style="float:right;display:inline-block;min-width:13em;min-height:1px;text-align:right;">
                        <?php if (isset($map["ChartRank"])): ?>
                            <b><?php echo number_format((float)$map["WeightedAvg"], 2); ?></b>
                            <span class="subText">/ 5.00 from
                                <span style="color:white"><?php echo $map["RatingCount"]; ?></span> votes
                            </span><br>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($map["Score"])): ?>
                        <div style="float:right;display:inline-block;">
                            <?php echo RenderRating($map["Score"]); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
<?php
    }
?>
</div>