<?php
    $proposal_id = $_GET['id'] ?? -1;
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
    $stmt = $conn->prepare("SELECT * FROM `descriptor_proposals` WHERE Status = 'pending';");
    $stmt->execute();
    $proposals = $stmt->get_result();
    $stmt->close();
?>


<center><h1>Descriptor proposals</h1></center>

<div class="flex-row-container" style="justify-content: center;">
    <?php
    while ($proposal = $proposals->fetch_assoc()) {
    ?>
        <div class="proposal-box">
            <div class="proposal-content">
                <a href="../?id=<?php echo $proposal["ProposalID"]; ?>">
                    <h2 style="margin-bottom: 0em;"><?php echo $proposal["Name"]; ?></h2>
                    <span class="subText"><?php echo $proposal["ShortDescription"]; ?></span>
                </a>
            </div>
        </div>
    <?php
    }
    ?>
</div>

<hr>

<a href="../new/">Create new descriptor proposal</a>

<?php
    require '../../../footer.php';
?>
