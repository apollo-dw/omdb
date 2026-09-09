<?php
    require_once __DIR__ . '/../../app/base.php';
    $descriptor_id = GetIntParam('id', -1, "Y U POST CRINGE");

    $stmt = $conn->prepare("SELECT * FROM `descriptors` WHERE `DescriptorID` = ?;");
    $stmt->bind_param("i", $descriptor_id);
    $stmt->execute();
    $descriptor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $PageTitle = "Descriptor - " . $descriptor["Name"];
    require '../header.php';

    if (is_null($descriptor)) {
        http_response_code(404);
        exit();
    }

    $stmt = $conn->prepare("
        WITH RECURSIVE DescendantDescriptors AS (
            SELECT DescriptorID
            FROM descriptors
            WHERE DescriptorID = ?
            UNION ALL
            SELECT d.DescriptorID
            FROM descriptors d
            JOIN DescendantDescriptors dd
                ON d.ParentID = dd.DescriptorID
        )
        SELECT DescriptorID
        FROM DescendantDescriptors
    ");

    $stmt->bind_param("i", $descriptor_id);
    $stmt->execute();

    $result = $stmt->get_result();

    $descendantDescriptors = [];
    while ($row = $result->fetch_assoc()) {
        $descendantDescriptors[] = (int)$row["DescriptorID"];
    }

    $descriptorPlaceholders = implode(",", array_fill(0, count($descendantDescriptors), "?"));

    $stmt->close();

    function getParentTree($descriptor, $conn) {
        if ($descriptor['ParentID'] === null) {
            return '<a href="./?id=' . $descriptor['DescriptorID'] . '">' . safe_htmlspecialchars($descriptor['Name'], ENT_QUOTES) . '</a>';
        } else {
            $parentStmt = $conn->prepare("SELECT `DescriptorID`, `Name`, `ParentID` FROM `descriptors` WHERE `DescriptorID` = ?;");
            $parentStmt->bind_param("i", $descriptor['ParentID']);
            $parentStmt->execute();
            $parentDescriptor = $parentStmt->get_result()->fetch_assoc();
            $parentStmt->close();

            $parentTree = getParentTree($parentDescriptor, $conn);
            return $parentTree . ' >> <a href="./?id=' . $descriptor['DescriptorID'] . '">' . safe_htmlspecialchars($descriptor['Name'], ENT_QUOTES) . '</a>';
        }
    }

    $types = str_repeat("i", count($descendantDescriptors)) . "i";
    $stmt = $conn->prepare("
        SELECT
            COUNT(DISTINCT bd.BeatmapID) AS count,
            AVG(r.Score) AS average_rating
        FROM beatmap_descriptors bd
        JOIN beatmaps b
            ON b.BeatmapID = bd.BeatmapID
        LEFT JOIN ratings r
            ON r.BeatmapID = bd.BeatmapID
        WHERE bd.DescriptorID IN ($descriptorPlaceholders)
        AND b.Mode = ?
    ");
    $params = [...$descendantDescriptors, $mode];
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $beatmapCount = $result["count"] ?? 0;
    $averageRating = $result["average_rating"] !== null
        ? round((float)$result["average_rating"], 2)
        : null;
    $stmt->close();

    $parentTree = getParentTree($descriptor, $conn);

    echo "<h1>" . safe_htmlspecialchars($descriptor["Name"], ENT_QUOTES) . "</h1>";
    echo "<span class='subText' style='float: right;'>[Descriptor" . $descriptor["DescriptorID"] . "]</span>";
    echo "<h3 style='color:#a8a8a8; margin-bottom: 0;'>{$beatmapCount} beatmaps // {$averageRating} average</h3>";
    echo "<span class='subText'>" . $parentTree . "</span><hr>";
    echo "<div id='descriptorDescription'>";
    echo ParseShortLinks($conn, safe_htmlspecialchars($descriptor["ShortDescription"], ENT_QUOTES));
    echo "</div>";

    if (!empty($descriptor["LongDescription"])) {
        $longDescription = nl2br(ParseShortLinks(
            $conn,
            safe_htmlspecialchars($descriptor["LongDescription"], ENT_QUOTES)
        ));

        echo "<a href='#' id='readMoreLink' onclick='showLongDescription(); return false;'>Read more</a>";

        echo "<script>
            const longDescription = " . json_encode($longDescription) . ";

            function showLongDescription() {
                document.getElementById('descriptorDescription').innerHTML = longDescription;
                document.getElementById('readMoreLink').remove();
            }
        </script>";
    }
?>

<style>
    h1 {
        margin-bottom: 0;
    }

    h3, h2 {
        margin-top: 0;
    }

    .ratingDistributionContainer {
        width: calc(100% / 20);
        height: 16em;
        margin: 0;
        color: rgba(125, 125, 125, 0.66);
        vertical-align: bottom;
        white-space: nowrap;
        box-sizing: content-box;
        padding-top: 2em;
    }

    .ratingDistributionContainer .bar {
        width: 100%;
        text-align: left;
        display: inline-block;
        vertical-align: bottom;
        background-color: rgba(125, 125, 125, 0.66);
        box-sizing: border-box;
        margin: 0;
    }

    .bar:hover {
        background-color: rgba(125, 125, 125, 0.88);
        transition: background-color 0.15s ease;
    }

    .bar > span {
        position: relative;
        top: 100%;
    }
</style>

<br><br>
<a href="../descriptors/">View all descriptors</a>
<br><br>

<h2 style="margin-bottom: 0px;">Highest ranked <?php echo safe_htmlspecialchars($descriptor["Name"], ENT_QUOTES); ?> maps</h2><br>
<div class="flex-container map-card-strip alternating-bg" style="width:100%;padding:0;justify-content: flex-start;">
    <?php
    $types = str_repeat("i", count($descendantDescriptors)) . "i";
    $stmt = $conn->prepare("
        SELECT b.*, s.Title
        FROM beatmaps b
        JOIN beatmapsets s
            ON b.SetID = s.SetID
        JOIN beatmap_descriptors bd
            ON b.BeatmapID = bd.BeatmapID
        WHERE bd.DescriptorId IN ($descriptorPlaceholders)
        AND b.Mode = ?
        AND b.Rating IS NOT NULL
        AND b.RatingCount >= 5
        ORDER BY b.Rating DESC
        LIMIT 10;");
    $params = [...$descendantDescriptors, $mode];
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $chartingMapCount = $result->num_rows;

    $counter = 0;
    while ($row = $result->fetch_assoc()) {
        $counter += 1;

        if ($counter > 9) {
            break;
        }

        $difficultyName = mb_strimwidth($row['DifficultyName'], 0, 35, "...");
        ?>
        <div class="flex-child map-card" style="max-width: 11%;">
            <a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb" style="aspect-ratio: 1 / 1;width:90%;height:auto;" onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';"></a><br>
            <span class="subText">
			    <a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo safe_htmlspecialchars("{$row["Title"]} [$difficultyName]", ENT_QUOTES); ?></a><br>
		    </span>
        </div>
        <?php
    }

    $stmt->close();
    ?>
</div>

<?php if ($counter === 10) { ?>
<a href="../charts/?y=all-time&tokens=<?php echo safe_htmlspecialchars(rawurlencode('d' . $descriptor['DescriptorID']), ENT_QUOTES); ?>">
    <div style="float:right;">
        ... view more!
    </div>
</a>
<br><br>
<?php } ?>

<h2 style="margin-bottom: 0px;">Usage over time</h2><br>
<div class="alternating-bg ratingDistributionChart" style="width:100%;padding:0px;">
    <div class="ratingDistributionContainer">
        <?php
        $types = "i" . str_repeat("i", count($descendantDescriptors)) . "i";
        $stmt = $conn->prepare("
            WITH TotalMapsPerYear AS (
                SELECT
                    YEAR(s.DateRanked) AS Year,
                    COUNT(DISTINCT b.BeatmapID) AS TotalCount
                FROM beatmaps b
                JOIN beatmapsets s
                    ON b.SetID = s.SetID
                WHERE b.Mode = ?
                GROUP BY Year
            ),
            DescriptorMapsPerYear AS (
                SELECT
                    YEAR(s.DateRanked) AS Year,
                    COUNT(DISTINCT b.BeatmapID) AS DescriptorCount
                FROM beatmaps b
                JOIN beatmapsets s
                    ON b.SetID = s.SetID
                JOIN beatmap_descriptors bd
                    ON b.BeatmapID = bd.BeatmapID
                WHERE bd.DescriptorID IN ($descriptorPlaceholders)
                AND b.Mode = ?
                GROUP BY Year
            )
            SELECT
                t.Year,
                COALESCE(d.DescriptorCount, 0) AS BeatmapCount,
                t.TotalCount,
                (COALESCE(d.DescriptorCount, 0) / t.TotalCount) * 100 AS Percentage
            FROM TotalMapsPerYear t
            LEFT JOIN DescriptorMapsPerYear d
                ON t.Year = d.Year
            ORDER BY t.Year;
        ");

        $params = [$mode, ...$descendantDescriptors, $mode];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $currentYear = date("Y");
        $yearlyData = array();
        for ($year = 2007; $year <= $currentYear; $year++) {
            $yearlyData[$year] = [
                'count' => 0,
                'percentage' => 0.0
            ];
        }

        $maxPercentage = 0.0;
        while ($row = $result->fetch_assoc()) {
            $year = $row['Year'];
            if (isset($yearlyData[$year])) {
                $yearlyData[$year]['count'] = $row['BeatmapCount'];
                $yearlyData[$year]['percentage'] = (float)$row['Percentage'];

                if ($row['Percentage'] > $maxPercentage) {
                    $maxPercentage = (float)$row['Percentage'];
                }
            }
        }

        $stmt->close();

        $logMax = $maxPercentage > 0 ? log10($maxPercentage + 1) : 1;
        foreach ($yearlyData as $year => $data) {
            $pct = $data['percentage'];

            $logCurrent = $pct > 0 ? log10($pct + 1) : 0;
            $barHeight = ($logCurrent / $logMax) * 100;
            $formattedPercent = number_format($pct, 2) . '%';

            echo "<div class='tooltip-wrapper' style='width:100%;height: {$barHeight}%;'>";
            echo "<div class='bar' style='height: 100%;'><span>{$year}</span></div>";
            echo "<div class='tooltip-box'>{$data['count']} maps ({$formattedPercent})</div>";
            echo '</div>';
        }
        ?>
    </div>
</div>

<br><br><br>

<h2 style="margin-bottom: 0px;">List of maps</h2><br>
<div id="descriptor-map-list" style="max-width: 50%;">
    <?php
    include 'descriptor-map-list.php';
    ?>
</div>

<br><br>
<hr>

<?php if ($loggedIn) { ?>
        <a href="proposal/new/?descriptor_id=<?php echo $descriptor_id; ?>"><span class="subText"><i class="icon-edit"></i> Edit descriptor</span></a>
<?php } ?>

<?php
    require '../footer.php';
?>
