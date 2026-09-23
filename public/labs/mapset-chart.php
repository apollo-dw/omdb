<?php
    $PageTitle = "Labs | Mapset Chart";

    require '../header.php';

    $stmt = $conn->prepare("
        SELECT
            bs.SetID,
            bs.Artist,
            bs.Title,
            bs.CreatorName,
            bs.CreatorID,
            COUNT(*) AS rated_difficulties,
            SUM(b.RatingCount) AS rating_count,
            SUM(b.WeightedAvg * b.RatingCount)
                / NULLIF(SUM(b.RatingCount), 0) AS weighted_avg,
            (
                SUM(b.WeightedAvg * b.RatingCount) + (2.9 * 25)
            ) / (SUM(b.RatingCount) + 25) AS bayesian_rating
        FROM beatmapsets bs
        JOIN beatmaps b
            ON b.SetID = bs.SetID
            AND b.Mode = 0
            AND b.Blacklisted = 0
        WHERE b.RatingCount > 15
            AND b.WeightedAvg IS NOT NULL
        GROUP BY
            bs.SetID,
            bs.Artist,
            bs.Title,
            bs.CreatorName
        HAVING COUNT(*) >= 3
        ORDER BY bayesian_rating DESC
        LIMIT 50
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    $counter = 0;
?>

<h1>Mapset Chart</h1>
<span class="subText">top 50 mapsets, counting mapsets with at least 3 difficulties with a minimum of 15 ratings each</span>
<hr>

<?php
    while ($row = $result->fetch_assoc()) {
        $counter++;
?>

<div class="flex-container chart-container alternating-bg" style="padding:0.5em 0;">

    <div class="flex-child" style="text-align:right;flex:0 0 5%">
        <b><?php echo "#" . $counter; ?></b>
    </div>

    <div class="flex-child" style="flex:0 0 80px;">
        <a href="/mapset/<?php echo $row['SetID']; ?>">
            <img
                src="https://b.ppy.sh/thumb/<?php echo $row['SetID']; ?>l.jpg"
                class="diffThumb"
                style="height:80px;width:80px;"
                onerror="this.onerror=null; this.src='../assets/img/missing-map-thumbnail.png';"
            />
        </a>
    </div>

    <div class="flex-child" style="flex:1 0 60%;">

        <a href="/mapset/<?php echo $row['SetID']; ?>">
            <?php echo safe_htmlspecialchars($row['Artist'], ENT_QUOTES); ?>
            - <?php echo safe_htmlspecialchars($row['Title'], ENT_QUOTES); ?><br>
        </a>

        <a href="/mapset/<?php echo $row['SetID']; ?>">
            <b style="word-break: break-word;">
                <?php echo safe_htmlspecialchars($row['CreatorName'] ?? GetUsernameFromId($row['CreatorID'], $conn), ENT_QUOTES); ?>
            </b>
        </a><br>

        <span class="subText">
            <?php echo $row['rated_difficulties']; ?> difficulties
        </span>

    </div>

    <div class="flex-child" style="flex: 1 1 1;">
        <span class="subText">
            <span style="color:white" style="font-weight:bold;">
                <?php echo number_format((float)$row["weighted_avg"], 2); ?>
            </span>
            / 5.00 from
            <span style="color:white" style="font-weight:bold;">
                <?php echo number_format((int)$row["rating_count"]); ?>
            </span> votes
        </span>

    </div>

</div>

<?php
    }
?>

<?php include '../footer.php'; ?>