<?php
    require "../base.php";

    $tournamentId = GetIntParam('id', -1);
    $stmt = $conn->prepare("SELECT t.*, s.Name as SeriesName, s.Acronym as SeriesAcronym from tournaments t INNER JOIN tournament_series s ON s.SeriesID = t.SeriesID WHERE t.TournamentID = ?;");
    $stmt->bind_param("i", $tournamentId);
    $stmt->execute();
    $tournament = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (is_null($tournament)) {
        http_response_code(404);
        exit();
    }

    $PageTitle = $tournament["Name"];
    require "../header.php";

    $stmt = $conn->prepare("SELECT * from tournament_stages WHERE TournamentID = ? ORDER BY SortOrder ASC;");
    $stmt->bind_param("i", $tournamentId);
    $stmt->execute();
    $results = $stmt->get_result();

    $stages = [];
    while ($row = $results->fetch_assoc()) {
        $stages[$row['StageID']] = $row;
    }

    $stmt->close();
?>

<style>
    .container {
        background-color: DarkSlateGrey;
        width: 100%;
        box-sizing: border-box;
        padding: 1em;
    }

    .container h1, .tab h2 {
        margin: 0;
    }

    .tab {
        width:100%;
        background-color: darkslategray;
        box-sizing: border-box;
        padding: 1.5em;
    }
</style>

<div class="container">
    <h1><?php echo $tournament["Name"]; ?></h1>
    <span class="subText">
        a part of <?php echo $tournament["SeriesName"]; ?>
        //
        <?php echo $tournament["Acronym"]; ?>
        //
        <?php echo $tournament["StartDate"]; ?> - <?php echo $tournament["EndDate"]; ?>
    </span>
</div>

<br>

<div class="tabbed-container-nav">
    <?php
        $first = true;

        foreach ($stages as $stageId => $stage) {
            $activeClass = $first ? 'active' : '';
            echo "<button class='{$activeClass}' onclick=\"openTab('{$stageId}', this)\">{$stage['Acronym']}</button>";
            $first = false;
        }
    ?>
</div>

<?php
    $first = true;

    foreach ($stages as $stageId => $stage) {
        $display = $first ? 'block' : 'none';

        echo "<div id='{$stageId}' class='tab' style='display: {$display};'>";
        ?>
            <h2><?= htmlspecialchars($stage['Name']) ?></h2>
            <hr />
            <?php
                $stmt = $conn->prepare("SELECT tm.*, b.DifficultyName, b.SetID, s.Artist, s.Title FROM tournament_maps tm
                                        INNER JOIN beatmaps b ON tm.BeatmapID = b.BeatmapID
                                        INNER JOIN beatmapsets s ON b.SetID = s.SetID
                                        WHERE tm.StageID = ?
                                        ORDER BY tm.SortOrder ASC;");
                $stmt->bind_param("i", $stageId);
                $stmt->execute();
                $results = $stmt->get_result();
                while ($map = $results->fetch_assoc()) {
                    ?>
                    <div class="alternating-bg flex-container" style="align-items:center; padding: 1em;">
                        <div class="flex-item" style="min-width: 4em; text-align: center;">
                            <b><?php echo $map["Slot"]; ?></b>
                        </div>

                        <div class="flex-item" style="padding: 0 1em; box-sizing: content-box;">
                            <a href="/mapset/<?php echo $map["SetID"]; ?>">
                                <img src="https://b.ppy.sh/thumb/<?php echo $map["SetID"]; ?>l.jpg"
                                style="aspect-ratio: 1 / 1; width:100px; height:auto;"
                                class="diffThumb"
                                onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';">
                            </a>
                        </div>

                        <div class="flex-item">
                            <a href="/mapset/<?php echo $map["SetID"]; ?>">
                                <?php echo safe_htmlspecialchars($map["Artist"]); ?> - <?php echo safe_htmlspecialchars($map["Title"]); ?>
                                [<?php echo safe_htmlspecialchars($map["DifficultyName"]); ?>]
                            </a>

                            <?php if ($map["IsCustom"] === 1) { ?>
                            <span class="badge" title="Custom Map">Custom</span>
                            <?php } ?>
                        </div>
                    </div>
                    <?php
                }
            ?>

        <?php
        echo "</div>";
        $first = false;
    }
?>

<?php
    require "../footer.php";
?>
