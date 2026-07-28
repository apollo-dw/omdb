<?php
    $PageTitle = "Edit queues";
    include '../header.php';

    $stmt = $conn->prepare("SELECT
           (SELECT COUNT(*) FROM `beatmap_edit_requests` WHERE Status = 'Pending') AS beatmaps,
           (SELECT COUNT(*) FROM `descriptor_proposals` WHERE Status = 'Pending') AS descriptors,
           (SELECT COUNT(*) FROM `tournament_series_edit_requests` WHERE Status = 'Pending') AS tournament_series");
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();

?>

<style>
    h1 {
        margin: 0;
    }
</style>

<h1>Edit queues</h1>
<hr>

<div style="max-width:33%;">
    <div class="alternating-bg" style="padding:0.5em;">
        <a href="mapsets.php">Mapsets</a>
        <div style="float: right;">
            <span class="subText"><?php echo $stats["beatmaps"]; ?> open</span>
        </div>
    </div>
    <div class="alternating-bg" style="padding:0.5em;">
        <a href="../descriptor/proposal/list/">Descriptors</a>
        <div style="float: right;">
            <span class="subText"><?php echo $stats["descriptors"]; ?> open</span>
        </div>
    </div>
    <div class="alternating-bg" style="padding:0.5em;">
        <a href="#">Tournament series</a>
        <div style="float: right;">
            <span class="subText"><?php echo $stats["tournament_series"]; ?> open</span>
        </div>
    </div>
</div>

<?php
    include '../footer.php';
?>
