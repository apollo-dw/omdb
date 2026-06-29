<?php
    $PageTitle = "View Descriptor Proposals";
    require '../../../header.php';
?>

<style>
    .proposal-box {
        width: 33.33%;
        min-height: 12em;
        display: flex;
        padding: 0 1em 1em;
        text-align: center;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
    }

    .proposal-box .proposal-content {
        display: flex;
        height: 100%;
        width: 100%;
        text-align: center;
        vertical-align: middle;
        align-items: center;
        justify-content: center;
        background-color: DarkSlateGrey;
    }
</style>

<?php
    $stmt = $conn->prepare("SELECT ProposalID, Name, ShortDescription, Type FROM `descriptor_proposals` WHERE Status = 'pending';");
    $stmt->execute();
    $activeProposals = $stmt->get_result();
    $stmt->close();
?>

<center><h1>Descriptor proposals</h1></center>

<div class="flex-row-container" style="justify-content: center;">
    <?php
    while ($proposal = $activeProposals->fetch_assoc()) {
    ?>
        <div class="proposal-box">
            <div class="proposal-content">
                <a href="../?id=<?php echo $proposal["ProposalID"]; ?>">
                    <h2 style="margin-bottom: 0em;"><?php echo safe_htmlspecialchars($proposal["Name"], ENT_QUOTES); ?></h2>
                    <span class="subText"><?php echo safe_htmlspecialchars($proposal["ShortDescription"], ENT_QUOTES); ?></span>
                    <br><br>
                    <span class="subText"><b><?php echo safe_htmlspecialchars($proposal["Type"], ENT_QUOTES); ?></b></span>
                </a>
            </div>
        </div>
    <?php
    }
    ?>
</div>
<a href="../new/">Create new descriptor proposal</a>
<hr>

<center><h2>History</h2></center>

<?php
    $stmt = $conn->prepare("SELECT ProposalID, Name, ProposerID, status, timestamp FROM `descriptor_proposals` WHERE Status != 'pending' ORDER BY timestamp DESC;");
    $stmt->execute();
    $historicalProposals = $stmt->get_result();
    $stmt->close();
?>

<?php
    while ($proposal = $historicalProposals->fetch_assoc()) {
    ?>
		<div class="alternating-bg" style="padding:1em;">
			<span style="display: inline-block; width: 8em; text-align: center; margin-right: 1em; center; border-right: 1px solid white;">
				<b style="color: <?php echo $proposal['status'] === 'approved' ? '#85C1A2' : ($proposal['status'] === 'denied' ? '#E5B7B7' : 'inherit'); ?>;">
					<?php echo htmlspecialchars($proposal['status']); ?>
				</b>
			</span>
			<a href="../?id=<?php echo $proposal["ProposalID"]; ?>"><b><?php echo safe_htmlspecialchars($proposal["Name"], ENT_QUOTES); ?></b></a>
			<span class="subText">by <b><?php echo GetUsernameFromID($proposal['ProposerID'], $conn); ?></b></span>
			<span style="float:right;"><?php echo $proposal["timestamp"]; ?></span>
		</div>
    <?php
    }
    ?>

<?php
    require '../../../footer.php';
?>
