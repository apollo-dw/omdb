<?php
    require_once __DIR__ . '/../../../app/base.php';

    $seriesId = GetIntParam('id', -1);
    $stmt = $conn->prepare("SELECT * from tournament_series WHERE SeriesID = ?;");
    $stmt->bind_param("i", $seriesId);
    $stmt->execute();
    $series = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (is_null($series)) {
        http_response_code(404);
        exit();
    }

    $PageTitle = $series["Name"];
    require "../../header.php";
?>

<style>
    h1 {
        margin: 0;
    }
</style>

<h1><?php echo safe_htmlspecialchars($series["Name"]); ?></h1>
<span class="subText"><?php echo safe_htmlspecialchars($series["Acronym"]); ?></span>
<hr>

<?php
    $stmt = $conn->prepare("SELECT * from tournaments WHERE SeriesID = ? ORDER BY StartDate ASC;");
    $stmt->bind_param("i", $seriesId);
    $stmt->execute();
    $results = $stmt->get_result();

    while ($tournament = $results->fetch_assoc()) {
        ?>
        <div class="alternating-bg" style="padding: 1em; box-sizing: content-box;">
            <a href="../?id=<?php echo $tournament["TournamentID"]; ?>">
                <?php echo safe_htmlspecialchars($tournament["Name"]); ?>
            </a>
            <span class="subText">
                <?php echo $tournament["StartDate"]; ?>
            </span>
        </div>
        <?php
    }

    $stmt->close();
?>

<?php if ($loggedIn) { ?>
    <span class="subText"><a href="../edit/create.php?seriesID=<?php echo $seriesId; ?>"><i class="icon-plus"></i> Add tournament</a></span>
    |
    <span class="subText"><a href="edit/create.php?id=<?php echo $seriesId; ?>"><i class="icon-edit"></i> Edit series</a></span>
<?php } ?>

<?php
    require "../../footer.php";
?>
