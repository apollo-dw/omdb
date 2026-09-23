<?php
    require_once __DIR__ . '/../../../app/base.php';

    $profileId = GetIntParam("id", null, "What are you trying to do man.");

    $countStmt = $conn->prepare("
        SELECT
            (
                SELECT COUNT(DISTINCT SetID)
                FROM beatmapset_credits
                WHERE UserID = ?
            ) AS beatmapCreditCount,

            (
                SELECT COUNT(DISTINCT TournamentID)
                FROM tournament_credits
                WHERE UserID = ?
            ) AS tournamentCreditCount
    ");
    $countStmt->bind_param("ii", $profileId, $profileId);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $counts = $countResult->fetch_assoc();
    $beatmapCreditCount = (int)$counts['beatmapCreditCount'];
    $tournamentCreditCount = (int)$counts['tournamentCreditCount'];
    $countStmt->close();
?>

<div id="tabbed-credits" class="tab">
    <?php if ($beatmapCreditCount > 0) { ?>
        <div id="beatmap-credits">
            <h3>Beatmap Credits (<?php echo $beatmapCreditCount; ?>)</h3>
            <?php
            $stmt = $conn->prepare("
                SELECT
                    s.*,
                    GROUP_CONCAT(
                        br.Name
                        ORDER BY br.Name
                        SEPARATOR ', '
                    ) AS userCredits
                FROM beatmapsets s
                JOIN beatmapset_credits bc
                    ON s.SetID = bc.SetID
                JOIN beatmap_roles br
                    ON bc.RoleID = br.RoleID
                WHERE bc.UserID = ?
                GROUP BY s.SetID
                ORDER BY s.DateRanked DESC
            ");

            $stmt->bind_param("i", $profileId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $artist = safe_htmlspecialchars($row["Artist"], ENT_QUOTES);
                $title = safe_htmlspecialchars($row["Title"], ENT_QUOTES);
                $credits = safe_htmlspecialchars($row["userCredits"], ENT_QUOTES);
                ?>

                <div style="padding-left:0.25em;height:5em;display:flex;align-items:center;" class="alternating-bg">
                    <div>
                        <a href="/mapset/<?php echo $row['SetID']; ?>">
                            <img
                                src="https://b.ppy.sh/thumb/<?php echo $row['SetID']; ?>l.jpg"
                                class="diffThumb"
                                style="height:4em;width:4em;margin-right:0.5em;"
                                onerror="this.onerror=null; this.src='../assets/img/missing-map-thumbnail.png';"
                            />
                        </a>
                    </div>
                    <div>
                        <a href="/mapset/<?php echo $row['SetID']; ?>">
                            <?php echo "{$artist} - {$title}"; ?>
                        </a>
                        <br>
                        <b>
                            <span class="subText">
                                <?php echo $credits; ?>
                            </span>
                        </b>
                    </div>
                </div>
                <?php
            }
            $stmt->close();
            ?>
        </div>
    <?php }; ?>

    <?php if ($tournamentCreditCount > 0) { ?>
        <div id="tournament-credits">
            <h3>Tournament Credits (<?php echo $tournamentCreditCount; ?>)</h3>
            <?php
            $stmt = $conn->prepare("
                SELECT
                    t.*,
                    GROUP_CONCAT(
                        tr.Name
                        ORDER BY tr.Name
                        SEPARATOR ', '
                    ) AS userCredits
                FROM tournaments t
                JOIN tournament_credits tc
                    ON t.TournamentID = tc.TournamentID
                JOIN tournament_roles tr
                    ON tc.RoleID = tr.RoleID
                WHERE tc.UserID = ?
                GROUP BY t.TournamentID
                ORDER BY t.StartDate DESC
            ");

            $stmt->bind_param("i", $profileId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $name = safe_htmlspecialchars($row["Name"], ENT_QUOTES);
                $acronym = safe_htmlspecialchars($row["Acronym"], ENT_QUOTES);
                $credits = safe_htmlspecialchars($row["userCredits"], ENT_QUOTES);
                ?>

                <div style="padding-left:0.25em;min-height:5em;display:flex;align-items:center;" class="alternating-bg">
                    <div>
                        <div>
                            <a href="/tournament/?id=<?php echo $row['TournamentID']; ?>">
                                <?php echo $name; ?>
                            </a>
                            <?php if (!empty($acronym)) { ?>
                                <span class="subText">
                                    (<?php echo $acronym; ?>)
                                </span>
                            <?php } ?>
                        </div>
                        <b>
                            <span class="subText">
                                <?php echo $credits; ?>
                            </span>
                        </b>
                        <?php if (!empty($row["StartDate"])) { ?>
                            <br>
                            <span class="subText">
                                <?php echo safe_htmlspecialchars($row["StartDate"], ENT_QUOTES); ?>
                            </span>
                        <?php } ?>
                    </div>
                </div>
                <?php
            }
            $stmt->close();
            ?>
        </div>
    <?php } ?>
</div>
