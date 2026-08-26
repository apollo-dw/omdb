<?php
    $PageTitle = "Tournament edit queue";
    include '../header.php';

    $stmt = $conn->prepare("SELECT * FROM tournament_edit_requests WHERE Status = 'Pending' ORDER BY CreatedAt ASC;");
    $stmt->execute();
    $edits = $stmt->get_result();
    $stmt->close();
?>

<style>
    h1 {
        margin: 0;
    }
</style>

<h1>Tournament edit queue</h1>
<hr>

<div style="max-width:50%;">
    <?php
    while ($edit = $edits->fetch_assoc()) {
        $editData = json_decode($edit['EditData'] ?? '{}', true);
        $tournamentName = $editData['Tournament']['Name'] ?? 'Unknown Tournament';
        $tournamentAcronym = $editData['Tournament']['Acronym'] ?? '';
        $requestType = empty($edit['TournamentID']) ? 'New' : 'Edit';
    ?>
        <div class="alternating-bg" style="padding:0.5em;">
            <span style="display: inline-block; width: 8em; text-align: center; margin-right: 1em; border-right: 1px solid white;">
                <b style="color: <?php echo $requestType === 'New' ? '#85C1A2' : '#E5B7B7'; ?>;">
                    <?php echo safe_htmlspecialchars($requestType); ?>
                </b>
            </span>

            <a href="../tournament/edit/?id=<?php echo $edit['EditID']; ?>">
                <b><?php echo safe_htmlspecialchars($tournamentName, ENT_QUOTES); ?></b>
                <?php if ($tournamentAcronym !== '') { ?>
                    <span class="subText">(<?php echo safe_htmlspecialchars($tournamentAcronym, ENT_QUOTES); ?>)</span>
                <?php } ?>
            </a>

            <span class="subText">by <b><?php echo safe_htmlspecialchars(GetUsernameFromID($edit['EditorID'], $conn)); ?></b></span>
            <span style="float:right;"><?php echo safe_htmlspecialchars($edit["CreatedAt"]); ?></span>
        </div>
    <?php
    }
    ?>
</div>

<?php
    include '../footer.php';
?>
