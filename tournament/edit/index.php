<?php
    require "../../base.php";
    $PageTitle = "Tournament edit";
    require "../../header.php";

    function generateChangelog(array $EditData, array $tournament, array $tournamentStages, array $tournamentMaps): array {
        $diffs = [];

        $newTournament = $EditData['Tournament'] ?? [];

        if (isset($newTournament['Name']) && $newTournament['Name'] !== $tournament['Name']) {
            $diffs[] = sprintf(
                'Tournament name changed from <b>%s</b> to <b>%s</b>',
                htmlspecialchars($tournament['Name']),
                htmlspecialchars($newTournament['Name'])
            );
        }

        $oldAcronym = $tournament['Acronym'] ?? '';
        $newAcronym = $newTournament['Acronym'] ?? '';
        if ($oldAcronym !== $newAcronym) {
            $diffs[] = sprintf(
                'Tournament acronym changed from <b>%s</b> to <b>%s</b>',
                htmlspecialchars($oldAcronym ?: 'None'),
                htmlspecialchars($newAcronym ?: 'None')
            );
        }

        if (isset($newTournament['StartDate']) && $newTournament['StartDate'] !== $tournament['StartDate']) {
            $diffs[] = sprintf(
                'Start date changed from <b>%s</b> to <b>%s</b>',
                htmlspecialchars($tournament['StartDate'] ?? "None"),
                htmlspecialchars($newTournament['StartDate'])
            );
        }

        if (isset($newTournament['EndDate']) && $newTournament['EndDate'] !== $tournament['EndDate']) {
            $diffs[] = sprintf(
                'End date changed from <b>%s</b> to <b>%s</b>',
                htmlspecialchars($tournament['EndDate'] ?? "None"),
                htmlspecialchars($newTournament['EndDate'])
            );
        }

        $dbStagesById = [];
        foreach ($tournamentStages as $stage) {
            $dbStagesById[$stage['StageID']] = $stage;
        }

        $payloadStages = $EditData['Stages'] ?? [];
        $payloadStageIds = [];

        foreach ($payloadStages as $payloadStage) {
            $stageId = !empty($payloadStage['StageID']) ? (int)$payloadStage['StageID'] : null;
            $stageName = $payloadStage['Name'] ?? 'Unnamed Stage';

            if (!$stageId || !isset($dbStagesById[$stageId])) {
                $diffs[] = sprintf('"%s" stage added', htmlspecialchars($stageName));

                foreach ($payloadStage['Maps'] ?? [] as $map) {
                    $diffs[] = sprintf(
                        'Beatmap ID %d added to %s',
                        (int)$map['BeatmapID'],
                        htmlspecialchars($stageName)
                    );
                }
                continue;
            }

            $payloadStageIds[$stageId] = true;
            $dbStage = $dbStagesById[$stageId];

            if ($payloadStage['Name'] !== $dbStage['Name']) {
                $diffs[] = sprintf(
                    '"%s" stage renamed to "%s"',
                    htmlspecialchars($dbStage['Name']),
                    htmlspecialchars($payloadStage['Name'])
                );
            }

            $dbStageAcronym = $dbStage['Acronym'] ?? '';
            $payloadStageAcronym = $payloadStage['Acronym'] ?? '';
            if ($payloadStageAcronym !== $dbStageAcronym) {
                $diffs[] = sprintf(
                    '"%s" stage acronym changed from <b>%s</b> to <b>%s</b>',
                    htmlspecialchars($payloadStage['Name']),
                    htmlspecialchars($dbStageAcronym ?: 'None'),
                    htmlspecialchars($payloadStageAcronym ?: 'None')
                );
            }
        }

        foreach ($dbStagesById as $stageId => $dbStage) {
            if (!isset($payloadStageIds[$stageId])) {
                $diffs[] = sprintf('"%s" stage removed', htmlspecialchars($dbStage['Name']));
            }
        }

        $dbMapsByStage = [];
        foreach ($tournamentMaps as $map) {
            $dbMapsByStage[$map['StageID']][$map['BeatmapID']] = $map;
        }

        foreach ($payloadStages as $payloadStage) {
            $stageId = $payloadStage['StageID'] ?? null;
            if (!$stageId || !isset($dbStagesById[$stageId])) {
                continue;
            }

            $stageName = $payloadStage['Name'];
            $existingStageMaps = $dbMapsByStage[$stageId] ?? [];
            $payloadMaps = $payloadStage['Maps'] ?? [];
            $seenBeatmapIds = [];

            foreach ($payloadMaps as $pMap) {
                $beatmapId = (int)$pMap['BeatmapID'];
                $seenBeatmapIds[$beatmapId] = true;

                if (!isset($existingStageMaps[$beatmapId])) {
                    $diffs[] = sprintf(
                        'Beatmap ID %d added to %s',
                        $beatmapId,
                        htmlspecialchars($stageName)
                    );
                    continue;
                }

                $dbMap = $existingStageMaps[$beatmapId];

                $pIsCustom = (int)($pMap['IsCustom'] ?? 0);
                $dbIsCustom = (int)($dbMap['IsCustom'] ?? 0);
                if ($pIsCustom !== $dbIsCustom) {
                    if ($pIsCustom === 1) {
                        $diffs[] = sprintf('Beatmap ID %d set to IsCustom', $beatmapId);
                    } else {
                        $diffs[] = sprintf('Beatmap ID %d unset from IsCustom', $beatmapId);
                    }
                }

                $pSlot = $pMap['Slot'] ?? '';
                $dbSlot = $dbMap['Slot'] ?? '';
                if ($pSlot !== $dbSlot) {
                    $diffs[] = sprintf(
                        'Beatmap ID %d slot changed from <b>%s</b> to <b>%s</b>',
                        $beatmapId,
                        htmlspecialchars($dbSlot ?: 'None'),
                        htmlspecialchars($pSlot ?: 'None')
                    );
                }
            }

            foreach ($existingStageMaps as $beatmapId => $dbMap) {
                if (!isset($seenBeatmapIds[$beatmapId])) {
                    $diffs[] = sprintf(
                        'Beatmap ID %d removed from %s',
                        $beatmapId,
                        htmlspecialchars($stageName)
                    );
                }
            }
        }

        return $diffs;
    }

    $editId = GetIntParam('id', -1);

    $stmt = $conn->prepare("SELECT * FROM `tournament_edit_requests` WHERE `EditID` = ?;");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();

    if (is_null($edit)) {
        http_response_code(404);
        exit();
    }

    $editData = json_decode($edit['EditData'] ?? '{}', true);
    $tournamentName = $editData['Tournament']['Name'] ?? 'Unknown Tournament';
    $tournamentAcronym = $editData['Tournament']['Acronym'] ?? '';
    $tournamentSeriesId = $editData['Tournament']['SeriesID'] ?? '';
    $requestType = empty($edit['TournamentID']) ? 'New' : 'Edit';
    $meta = trim($editData['Meta'] ?? '');
    $stages = $editData['Stages'] ?? [];

    $originalTournament = null;
    $changelog = null;

    $stmt = $conn->prepare("SELECT * FROM `tournament_series` WHERE `SeriesID` = ?;");
    $stmt->bind_param("i", $tournamentSeriesId);
    $stmt->execute();
    $series = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!is_null($edit["TournamentID"])) {
        $stmt = $conn->prepare("SELECT * FROM `tournaments` WHERE `TournamentID` = ?;");
        $stmt->bind_param("i", $edit["TournamentID"]);
        $stmt->execute();
        $originalTournament = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM tournament_stages WHERE TournamentID = ? ORDER BY SortOrder ASC");
        $stmt->bind_param("i", $edit["TournamentID"]);
        $stmt->execute();
        $results = $stmt->get_result();

        $tournamentStages = [];
        while ($row = $results->fetch_assoc()) {
            $tournamentStages[] = $row;
        }

        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM tournament_maps WHERE TournamentID = ? ORDER BY SortOrder ASC");
        $stmt->bind_param("i", $edit["TournamentID"]);
        $stmt->execute();
        $results = $stmt->get_result();

        $tournamentMaps = [];
        while ($row = $results->fetch_assoc()) {
            $tournamentMaps[] = $row;
        }

        $stmt->close();

        $changelog = generateChangelog($editData, $originalTournament, $tournamentStages, $tournamentMaps);
    }

    $allBeatmapIds = [];
    foreach ($stages as $stage) {
        foreach ($stage['Maps'] ?? [] as $map) {
            if (!empty($map['BeatmapID'])) {
                $allBeatmapIds[] = (int)$map['BeatmapID'];
            }
        }
    }
    $allBeatmapIds = array_unique($allBeatmapIds);

    $mapMetadata = [];
    if (!empty($allBeatmapIds)) {
        $placeholders = implode(',', array_fill(0, count($allBeatmapIds), '?'));
        $types = str_repeat('i', count($allBeatmapIds));

        $sql = "SELECT b.BeatmapID, b.DifficultyName, b.SetID, s.Artist, s.Title
                FROM beatmaps b
                INNER JOIN beatmapsets s ON b.SetID = s.SetID
                WHERE b.BeatmapID IN ($placeholders)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$allBeatmapIds);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $mapMetadata[$row['BeatmapID']] = $row;
        }
        $stmt->close();
    }
?>

<style>
    .header {
        background-color: DarkSlateGrey;
        text-align: center;
        width: 100%;
        padding: 2em;
        box-sizing: border-box;
    }

    .bordered-container {
        width:100%;
        border:1px solid DarkSlateGrey;
        padding: 0.5em;
        box-sizing: border-box;
    }

    .bordered-container td {
        padding: 0.5em;
    }

    .right {
        text-align: right;
        font-weight: bold;
    }

    .proposal-box .actions {
        font-size: 1.5em;
    }

    .proposal-box .actions i {
        margin-left: 0.25em;
        cursor:pointer;
        color: grey;
    }

    .diff-old-value {
        color: #ff9090;
        font-size: 0.85em;
    }
</style>

<div class="header">
    <h1 style="margin:0;"><?php echo safe_htmlspecialchars($tournamentName, ENT_QUOTES); ?></h1>
    <span class="subText"><?php echo $edit["Status"]; ?></span>
</div>

<br>

<?php if (!is_null($changelog) && $edit["Status"] === 'Pending') { ?>
    <div class="bordered-container">
        <h2 style="margin:0">Changelog</h2>
        <ul>
        <?php
            foreach ($changelog as $entry) {
                echo "<li>" . $entry . "</li>";
            }
        ?>
        </ul>
    </div class="bordered-container">
    <br>
<?php } ?>

<div class="bordered-container">
    <div>
        <h2 style="margin: 0;">
            <?php echo safe_htmlspecialchars($tournamentName) ?>
            <?php if ($tournamentAcronym !== '') { ?>
                <span class="subText"; ><?php echo safe_htmlspecialchars($tournamentAcronym) ?></span>
            <?php } ?>
        </h2>
        <?php echo $series["Name"]; ?> <br>
        <?php if ($meta !== '') { ?>
            <br>
            <b>Meta comment:</b>
            <div style="margin-top: 0; padding-left: 2em; border-left:2px solid var(--main-theme-subtext-color);">
                <?php echo safe_htmlspecialchars($meta) ?>
            </div>
            <br>
        <?php } ?>

        <?php if ($loggedIn && $userName === "moonpoint") { ?>
            <label for="changeStatus">Status:</label>
            <select id="changeStatus">
                <option value="Pending" <?php if ($edit["Status"] === "Pending") {
                    echo 'selected="selected"';
                } ?>>Pending</option>
                <option value="Approved" <?php if ($edit["Status"] === "Approved") {
                    echo 'selected="selected"';
                } ?>>Approved</option>
                <option value="Denied" <?php if ($edit["Status"] === "Denied") {
                    echo 'selected="selected"';
                } ?>>Denied</option>
            </select>
        <?php } ?>
    </div>

    <hr>

    <?php if (empty($stages)) { ?>
        <div>this edit request is missing stages</div>
    <?php } else { ?>
        <div class="flex-container" style="flex-direction: column; gap: 1em;">
            <?php foreach ($stages as $stage) { ?>
                <?php
                $stageName = $stage['Name'] ?? 'Unnamed Stage';
                $stageAcronym = $stage['Acronym'] ?? '';
                $sortOrder = ($stage['SortOrder'] ?? 0);
                $maps = $stage['Maps'] ?? [];
                ?>
                <div style="background-color: DarkSlateGrey; border: 1px solid #ccc; padding: 1em;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0;">
                            <?php echo safe_htmlspecialchars($stageName) ?>
                            <?php if ($stageAcronym !== '') { ?>
                                <span class="subText"><?php echo safe_htmlspecialchars($stageAcronym) ?></span>
                            <?php } ?>
                        </h3>
                    </div>

                    <?php if (empty($maps)) { ?>
                        <div>no beatmaps in this stage</div>
                    <?php } else { ?>
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 1px solid white;">
                                    <th style="padding: 0.5em;">Slot</th>
                                    <th style="padding: 0.5em;">Beatmap ID</th>
                                    <th style="padding: 0.5em;">Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($maps as $map) { ?>
                                    <?php
                                    $slot = $map['Slot'] ?? '-';
                                    $beatmapId = (int)($map['BeatmapID'] ?? 0);
                                    $isCustom = !empty($map['IsCustom']);

                                    $info = $mapMetadata[$beatmapId] ?? null;
                                    ?>
                                    <tr style="border-bottom: 1px solid white;">
                                        <td style="padding: 0.5em; font-weight: bold; width: 5em;">
                                            <?php echo safe_htmlspecialchars($slot) ?>
                                        </td>
                                        <td style="padding: 0.5em;">
                                            <a href="https://osu.ppy.sh/b/<?php echo $beatmapId ?>" target="_blank">
                                                <?php if ($info) { ?>
                                                    <?php echo safe_htmlspecialchars($info['Artist']) ?> - <?php echo safe_htmlspecialchars($info['Title']) ?> [<?php echo safe_htmlspecialchars($info['DifficultyName']) ?>]
                                                <?php } else { ?>
                                                    Beatmap #<?php echo $beatmapId ?>
                                                <?php } ?>
                                            </a>
                                        </td>
                                        <td style="padding: 0.5em; width: 100px;">
                                            <?php if ($isCustom) { ?>
                                                <span class="badge" title="Custom Map">Custom</span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<script>
$("#changeStatus").change(function() {
        var status = $(this).val();

        $.ajax({
            type: "POST",
            url: "ChangeStatus.php",
            data: {
                EditID: <?php echo $editId; ?>,
                Status: status
            },
            success: function() {
                location.reload();
            },
            error: function(xhr, status, error) {
                console.error('Error submitting status:', error);
            }
        });
    });
</script>

<?php
    require "../../footer.php";
?>
