<?php
    if (file_exists("../../base.php"))
        include "../../base.php";

    $profileId = $_GET["id"];
?>

<div id="tabbed-nominations" class="tab" style="padding-top:0.5em;">
    <span class="subText">this display will be way more epic later. for now i'll just show highest charting nominated maps</span> <hr>
    <?php
    $usedSets = array();

    $stmt = $conn->prepare("SELECT bm.* FROM beatmaps bm JOIN beatmapset_nominators bn ON bm.SetID = bn.SetID WHERE bn.NominatorID = ? AND ChartRank IS NOT NULL ORDER BY ChartRank;");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()){
        if (in_array($row["SetID"], $usedSets))
            continue;

        $artist = htmlspecialchars($row["Artist"]);
        $title = htmlspecialchars($row["Title"]);
        $diffname = htmlspecialchars($row["DifficultyName"]);
        $avgRating = number_format($row["Rating"], 2);

        ?>
        <div style='padding-left:0.25em;height:5em;display:flex;align-items: center;' class='alternating-bg'>
            <div>
                <a href="/mapset/<?php echo $row['SetID']; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row['SetID']; ?>l.jpg" class="diffThumb" style="height:4em;width:4em;margin-right:0.5em;" onerror="this.onerror=null; this.src='../charts/INF.png';" /></a>
            </div>
            <div>
                <a href="/mapset/<?php echo $row['SetID']; ?>"><?php echo "{$artist} - {$title} [$diffname]"; ?></a> <br>
                <b><?php echo number_format($row["WeightedAvg"], 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $row["RatingCount"]; ?></span> votes</span>,
                <b>#<?php echo $row["ChartRank"]; ?></b> <span class="subText">overall</span>
            </div>
        </div>
        <?php



        $usedSets[] = $row["SetID"];
    }
    ?>
</div>
