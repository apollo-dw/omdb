<?php
    require_once __DIR__ . '/../../app/base.php';

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

    $stmt = $conn->prepare("
        SELECT TournamentID, Acronym
        FROM tournaments
        WHERE SeriesID = ? AND StartDate < ?
        ORDER BY StartDate DESC
        LIMIT 1
    ");
    $stmt->bind_param("is", $tournament['SeriesID'], $tournament['StartDate']);
    $stmt->execute();
    $prevTournament = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT TournamentID, Acronym
        FROM tournaments
        WHERE SeriesID = ? AND StartDate > ?
        ORDER BY StartDate ASC
        LIMIT 1
    ");
    $stmt->bind_param("is", $tournament['SeriesID'], $tournament['StartDate']);
    $stmt->execute();
    $nextTournament = $stmt->get_result()->fetch_assoc();
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
    <h1><?php echo safe_htmlspecialchars($tournament["Name"]); ?></h1>
    <span class="subText">
        <a href="series/?id=<?php echo $tournament["SeriesID"]; ?>">
            <?php echo safe_htmlspecialchars($tournament["SeriesName"]); ?>
        </a>
        //
        <?php echo safe_htmlspecialchars($tournament["StartDate"]); ?> - <?php echo safe_htmlspecialchars($tournament["EndDate"] ?? "N/A"); ?>
    </span>

    <br><br>

    <?php if ($prevTournament) { ?>
        <a href="?id=<?php echo $prevTournament['TournamentID']; ?>" title="Previous Tournament">
            <?php echo safe_htmlspecialchars($prevTournament['Acronym']); ?>
        </a> <
    <?php } ?>

    <strong><?php echo safe_htmlspecialchars($tournament["Acronym"]); ?></strong>

    <?php if ($nextTournament) { ?>
        >
        <a href="?id=<?php echo $nextTournament['TournamentID']; ?>" title="Next Tournament">
            <?php echo safe_htmlspecialchars($nextTournament['Acronym']); ?>
        </a>
    <?php } ?>
</div>

<?php if ($loggedIn) { ?>
    <span class="subText"><a href="edit/create.php?id=<?php echo $tournamentId; ?>"><i class="icon-edit"></i> Edit tournament</a></span>
    <br>
<?php } ?>

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
            <h2><?php echo safe_htmlspecialchars($stage['Name']) ?></h2>
            <hr />
            <?php
                $stmt = $conn->prepare("SELECT tm.*, b.DifficultyName, b.SetID, s.Artist, s.Title, r_user.Score
                                        FROM tournament_maps tm
                                        LEFT JOIN beatmaps b ON tm.BeatmapID = b.BeatmapID
                                        LEFT JOIN beatmapsets s ON b.SetID = s.SetID
                                        LEFT JOIN ratings r_user ON r_user.BeatmapID = b.BeatmapID AND r_user.UserID = ?
                                        WHERE tm.StageID = ?
                                        ORDER BY tm.SortOrder ASC;");
                $stmt->bind_param("ii", $userId, $stageId);
                $stmt->execute();
                $results = $stmt->get_result();
                while ($map = $results->fetch_assoc()) {
                    $hasSetId = !empty($map["SetID"]);

                    $mapUrl = $hasSetId
                        ? "/mapset/" . $map["SetID"]
                        : "https://osu.ppy.sh/b/" . $map["BeatmapID"];

                    $thumbUrl = $hasSetId
                        ? "https://b.ppy.sh/thumb/" . $map["SetID"] . "l.jpg"
                        : "/assets/img/missing-map-thumbnail.png";
                    ?>
                    <div class="alternating-bg flex-container" style="align-items:center; padding: 1em; box-sizing: border-box;">
                        <div class="flex-item" style="min-width: 4em; text-align: center;">
                            <b><?php echo safe_htmlspecialchars($map["Slot"]); ?></b>
                        </div>

                        <div class="flex-item" style="padding: 0 1em; box-sizing: content-box;">
                            <a href="<?php echo $mapUrl; ?>" <?php echo !$hasSetId ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                                <img src="<?php echo $thumbUrl; ?>"
                                style="aspect-ratio: 1 / 1; width:100px; height:auto;"
                                class="diffThumb"
                                onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';">
                            </a>
                        </div>

                        <div class="flex-item">
                            <a href="<?php echo $mapUrl; ?>" <?php echo !$hasSetId ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                                <?php if ($hasSetId) { ?>
                                    <?php echo safe_htmlspecialchars($map["Artist"]); ?> - <?php echo safe_htmlspecialchars($map["Title"]); ?>
                                    [<?php echo safe_htmlspecialchars($map["DifficultyName"]); ?>]
                                <?php } else { ?>
                                    Map not in OMDB (ID: <?php echo (int)$map["BeatmapID"]; ?>)
                                <?php } ?>
                            </a>

                            <?php if ((int)$map["IsCustom"] === 1) { ?>
                                <span class="badge" title="Custom Map">Custom</span>
                            <?php } ?>
                        </div>

                        <div style="margin-left: auto;">
                            <?php
                                if (isset($map["Score"])) {
                                    echo RenderRating($map["Score"]);
                                }
                            ?>
                        </div>
                    </div>
                    <?php
                }
                $stmt->close();
            ?>

        <?php
        echo "</div>";
        $first = false;
    }
?>

<?php
    require "../footer.php";
?>
