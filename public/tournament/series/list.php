<?php
    require "../../base.php";

    $stmt = $conn->query("
        SELECT ts.*, COUNT(t.TournamentID) AS TournamentCount
        FROM tournament_series ts
        LEFT JOIN tournaments t ON ts.SeriesID = t.SeriesID
        GROUP BY ts.SeriesID
        ORDER BY TournamentCount DESC, ts.Name ASC;
    ");

    $allSeries = [];
    while ($row = $stmt->fetch_assoc()) {
        $allSeries[] = $row;
    }

    $stmt->close();

    if (is_null($allSeries) || empty($allSeries)) {
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
            <span class="subText" style="float: right;">
                <?php echo safe_htmlspecialchars($series["TournamentCount"]); ?> iteration<?php echo $series["TournamentCount"] == 1 ? '' : 's'; ?>
            </span>
        </div>
        <?php
    }
?>

<?php
    require "../../footer.php";
?>
