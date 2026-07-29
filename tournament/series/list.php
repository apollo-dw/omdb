<?php
    require "../../base.php";

    $stmt = $conn->query("SELECT * from tournament_series;");

    $allSeries = [];
    while ($row = $stmt->fetch_assoc()) {
        $allSeries[] = $row;
    }

    $stmt->close();

    if (is_null($allSeries)) {
        http_response_code(404);
        exit();
    }

    $PageTitle = "Tournament series listing";
    require "../../header.php";
?>

<style>
    h1 {
        margin: 0;
    }
</style>

<h1>Tournament series listing</h1>
<span class="subText">All tournaments series that are added to OMDB.</span>
<hr>
<?php if ($loggedIn) { ?>
    <span class="subText"><a href="edit/create.php"><i class="icon-plus"></i> Add tournament series</a></span>
<?php } ?>

<br><br>

<?php
    $stmt = $conn->prepare("SELECT * from tournaments WHERE SeriesID = ? ORDER BY EndDate ASC;");
    $stmt->bind_param("i", $seriesId);
    $stmt->execute();
    $results = $stmt->get_result();

    foreach ($allSeries as $series) {
        ?>
        <div class="alternating-bg" style="padding: 1em; box-sizing: content-box;">
            #<?php echo safe_htmlspecialchars($series["SeriesID"]); ?> -
            <a href="./?id=<?php echo safe_htmlspecialchars($series["SeriesID"]); ?>">
                <?php echo safe_htmlspecialchars($series["Name"]); ?>
            </a>
            <span class="subText">
                <?php echo safe_htmlspecialchars($series["Acronym"]); ?>
            </span>
        </div>
        <?php
    }

    $stmt->close();
?>

<?php
    require "../../footer.php";
?>
