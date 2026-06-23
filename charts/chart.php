<?php
    include_once '../base.php';

    // read from POST first, then GET, with a fallback default
    function postOrGet(string $key, $default = null) {
        if (isset($_POST[$key]) && $_POST[$key] !== '')
			return $_POST[$key];
        if (isset($_GET[$key]) && $_GET[$key]  !== '')
			return $_GET[$key];
        return $default;
    }

    $year = postOrGet('y', 2026);
    $year = ($year === 'all-time') ? 'all-time' : (int)$year;

    $page = (int)(postOrGet('p', 1));
    $order = (int)(postOrGet('o', 1));
    $genre = (int)(postOrGet('g', 0));
    $language = (int)(postOrGet('l', 0));
    $country = postOrGet('c', ''); 

    $onlyFriends = postOrGet('f', 'false') === 'true';
    $hideAlreadyRated = postOrGet('alreadyRated', 'false') === 'true';
    $excludeGraveyard = postOrGet('excludeGraveyard', 'false') === 'true';
    $excludeLoved = postOrGet('excludeLoved', 'false') === 'true';
    $excludeRanked = postOrGet('excludeRanked', 'false') === 'true';

    $minSR = (float)(postOrGet('minSR', 0));
    $maxSR = (float)(postOrGet('maxSR', -1));

    if (!isset($selectedDescriptors)) {
        $descriptorsRaw = postOrGet('descriptors', '');
        if ($descriptorsRaw === '' || $descriptorsRaw === '[]') {
            $selectedDescriptors = [];
        } elseif ($descriptorsRaw[0] === '[') {
            $selectedDescriptors = json_decode($descriptorsRaw, true) ?? [];
        } else {
            $names = array_filter(array_map('trim', explode(',', $descriptorsRaw)));
            $selectedDescriptors = [];
            if (!empty($names)) {
                $placeholders = implode(',', array_fill(0, count($names), '?'));
                $stmt = $conn->prepare("SELECT DescriptorID AS id, Name AS name FROM descriptors WHERE Name IN ($placeholders) AND Usable = 1");
                $types = str_repeat('s', count($names));
                $stmt->bind_param($types, ...$names);
                $stmt->execute();
                $selectedDescriptors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        }
    }
?>

<div class="flex-item" style="padding:0.5em;">
    <?php
        $types = "ii";
        $params = [$userId, $mode];

        $lim = 50;
        $offset = ($page > 1) ? ($page - 1) * $lim : 0;
        $counter = $offset;
        $pageString = ($page > 1) ? "LIMIT {$offset}, {$lim}" : "LIMIT {$lim}";
        $orderString = ($order == 2) ? "ASC" : "DESC";

        $yearString = "";
        if ($year !== "all-time") {
            $yearString = "AND YEAR(s.DateRanked) = ?";
            $types .= "s";
            $params[] = (string)$year;
        }

        $genreString = "";
        if ($genre > 0) {
            $genreString = "AND s.Genre = ?";
            $types .= "i";
            $params[] = $genre;
        }

        $languageString = "";
        if ($language > 0) {
            $languageString = "AND s.Lang = ?";
            $types .= "i";
            $params[] = $language;
        }

        $countryString = "";
        if ($country !== '' && $country !== '0' && $country !== 0) {
            $countryString = "
                AND b.BeatmapID IN (
                    SELECT bc.BeatmapID
                    FROM beatmap_creators bc
                    JOIN mappernames mn ON mn.UserID = bc.CreatorID
                    GROUP BY bc.BeatmapID
                    HAVING COUNT(DISTINCT mn.Country) = 1
                       AND MAX(mn.Country = ?) = 1
                )";
            $types .= "s";
            $params[] = $country;
        }

        $descriptorString = "";
        if (!empty($selectedDescriptors)) {
            $clauses = [];
            foreach ($selectedDescriptors as $descriptor) {
                $id = (int)$descriptor['id'];
                $clauses[] = "
                    EXISTS (
                        SELECT 1 FROM beatmap_descriptors bd
                        WHERE bd.BeatmapID = b.BeatmapID
                          AND bd.DescriptorID = {$id}
                    )";
            }
            $descriptorString = "AND (" . implode(" AND ", $clauses) . ")";
        }

        $hideAlreadyRatedString = $hideAlreadyRated ? "AND r_user.Score IS NULL" : "";
        $excludeLovedString = $excludeLoved ? "AND b.Status != 4" : "";
        $excludeGraveyardString = $excludeGraveyard ? "AND b.Status != -2"  : "";
        $excludeRankedString = $excludeRanked ? "AND b.Status NOT IN (1,2)" : "";

        $srRangeString = "";
        if ($minSR > 0)
			$srRangeString .= " AND b.SR >= " . (float)$minSR;
        if ($maxSR > 0)
			$srRangeString .= " AND b.SR <= " . (float)$maxSR;

        $statsJoin = "";
        $priorJoin = "";
        $statsTypes = "";
        $statsParams = [];

        if ($onlyFriends) {
            $statsJoin = "
                INNER JOIN (
                    SELECT
                        r.BeatmapID,
                        SUM(r.Score) AS TotalScore,
                        COUNT(*) AS RatingCount,
                        AVG(r.Score) AS WeightedAvg,
                        STDDEV_POP(r.Score) * SQRT(COUNT(*)) AS Controversy
                    FROM user_relations ur
                    JOIN ratings r ON r.UserID = ur.UserIDTo
                    WHERE ur.UserIDFrom = ?
                      AND ur.Type = 1
                    GROUP BY r.BeatmapID
                ) friend_stats ON friend_stats.BeatmapID = b.BeatmapID";

            $priorJoin = "
                CROSS JOIN (
                    SELECT AVG(Score) AS prior_rating, COUNT(*) AS prior_count
                    FROM ratings
                ) prior";

            $statsTypes = "i";
            $statsParams[] = $userId;
        }

        if ($onlyFriends) {
            $ratingField = "friend_stats.WeightedAvg";
            $countField = "friend_stats.RatingCount";
            $bayesField = "(prior.prior_rating * prior.prior_count + friend_stats.TotalScore) / (prior.prior_count + friend_stats.RatingCount)";
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
                $columnString = $onlyFriends ? "friend_stats.Controversy" : "b.controversy";
                break;
            case 5:
                $columnString = $onlyFriends ? "(friend_stats.WeightedAvg - b.Rating) * SQRT(friend_stats.RatingCount)" : "(b.WeightedAvg - b.Rating) * SQRT(b.RatingCount)";
                break;
            default:
                $columnString = "BayesianAverage";
                break;
        }

        $sql = "
        SELECT
            b.*,
            s.*,
            {$ratingField} AS WeightedAvg,
            {$countField} S RatingCount,
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
            " . (!$onlyFriends ? "AND b.Rating IS NOT NULL" : "") . "
            {$genreString}
            {$languageString}
            {$yearString}
            {$countryString}
            {$descriptorString}
            {$hideAlreadyRatedString}
            {$excludeLovedString}
            {$excludeGraveyardString}
            {$excludeRankedString}
            {$srRangeString}
        ORDER BY
            {$columnString} {$orderString},
            b.BeatmapID
        {$pageString}";

        $finalTypes = $statsTypes . $types;
        $finalParams = array_merge($statsParams, $params);

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("<pre>" . $conn->error . "\n\n" . safe_htmlspecialchars($sql) . "</pre>");
        }

        $stmt->bind_param($finalTypes, ...$finalParams);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $stmt2 = $conn->prepare("
                SELECT
                    bd.DescriptorID,
                    d.Name,
                    d.ShortDescription
                FROM beatmap_descriptors bd
                JOIN descriptors d ON bd.DescriptorID = d.DescriptorID
                WHERE bd.BeatmapID = ?
                ORDER BY bd.Weight DESC, bd.DescriptorID
                LIMIT 10");
            $stmt2->bind_param("i", $row["BeatmapID"]);
            $stmt2->execute();
            $descriptorResult = $stmt2->get_result();

            $counter++;
        ?>
        <div class="flex-container chart-container alternating-bg">
            <div style="text-align:right; flex: 0 0 5%;">
                <b><?php echo "#" . $counter; ?></b>
            </div>
            <div style="flex: 0 0 0;">
                <a href="/mapset/<?php echo $row['SetID']; ?>">
                    <img src="https://b.ppy.sh/thumb/<?php echo $row['SetID']; ?>l.jpg"
                         class="diffThumb" style="height:80px;width:80px;"
                         onerror="this.onerror=null; this.src='INF.png';" />
                </a>
            </div>
            <div style="flex: 0 0 46%;">
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
            <div style="flex: auto auto 0;">
                <b><?php echo number_format((float)$row["WeightedAvg"], 2); ?></b>
                <span class="subText">/ 5.00 from
                    <span style="color:white"><?php echo $row["RatingCount"]; ?></span> votes
                </span><br>
            </div>
            <div style="flex: 0 auto 0;">
                <b style="font-weight:900;"><?php echo $row["Score"]; ?></b>
            </div>
        </div>
        <?php
        }
    ?>
</div>