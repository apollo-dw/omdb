<?php
    $PageTitle = "Labs | Stats";
    require '../header.php';
?>

<h1>Stats</h1>

<hr>

<b>Most credited mappers</b>
    <div id="credit-ranking" style="width:32em;max-width:100%;">
    <?php
        $stmt = $conn->prepare("SELECT mn.UserID, mn.Username, COUNT(*) AS CreditCount
                                FROM beatmapset_credits AS bc
                                JOIN mappernames AS mn ON bc.UserID = mn.UserID
                                GROUP BY mn.UserID, mn.Username
                                ORDER BY CreditCount DESC, mn.Username ASC
                                LIMIT 20;");
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
    ?>

    <div class="flex-container ratingContainer alternating-bg">
        <div class="flex-child">
            <a href="/profile/<?php echo $row["UserID"]; ?>"><img class="square-thumb" src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo safe_htmlspecialchars($row["Username"] ?? GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>"/></a>
        </div>
        <div class="flex-child" style="flex:0 0 50%;">
            <a style="display:flex;" href="/profile/<?php echo $row["UserID"]; ?>">
                <?php echo safe_htmlspecialchars($row["Username"] ?? GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>
            </a>
        </div>
        <div class="flex-child" style="width:100%;text-align:right;">
            credited <?php echo $row["CreditCount"]; ?> times
        </div>
    </div>

    <?php
        }
    ?>
</div>
<br>

<h2>OMDB ratings</h2>
<hr>
Average rating by year:
<?php
$query = "
    SELECT
        YEAR(bs.Timestamp) AS RatingYear,
        COUNT(*) AS TotalRatings,
        ROUND(AVG(r.Score), 2) AS AverageRating
    FROM ratings r
    JOIN beatmaps b
        ON b.BeatmapID = r.BeatmapID
    JOIN beatmapsets bs
        ON bs.SetID = b.SetID
    GROUP BY YEAR(bs.Timestamp)
    ORDER BY RatingYear DESC;
";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $year = (int)$row["RatingYear"];
    $averageRating = (float)$row["AverageRating"];
    $totalRatings = (int)$row["TotalRatings"];
?>
    <div style="display: flex; justify-content: space-between; width:25%;" class="subText">
        <span><b><?php echo $year; ?></b></span>
        <span><?php echo number_format($averageRating, 2); ?>★ (<?php echo $totalRatings; ?> rated)</span>
    </div>
<?php } ?>

<br>

Average rating by descriptor:
<?php
$query = "
    SELECT
        d.DescriptorID,
        d.Name AS DescriptorName,
        COUNT(*) AS TotalRatings,
        ROUND(AVG(r.Score), 2) AS AverageRating
    FROM ratings r
    JOIN beatmap_descriptors bd
        ON bd.BeatmapID = r.BeatmapID
    JOIN descriptors d
        ON d.DescriptorID = bd.DescriptorID
    WHERE d.Usable = 1
    GROUP BY d.DescriptorID, d.Name
    ORDER BY AverageRating DESC, TotalRatings DESC;
";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $formattedName = htmlspecialchars($row["DescriptorName"]);
    $averageRating = (float)$row["AverageRating"];
    $totalRatings = (int)$row["TotalRatings"];
?>
        <div style="display: flex; justify-content: space-between; width:25%;" class="subText">
            <span><b><?php echo $formattedName; ?></b></span>
            <span><?php echo number_format($averageRating, 2); ?>★ (<?php echo $totalRatings; ?> rated)</span>
        </div>
<?php } ?>

<?php
    require "../footer.php";
?>
