<?php
    if (file_exists("../../base.php"))
        include "../../base.php";

    $profileId = GetIntParam("id", null, "What are you trying to do man.");
?>

<div id="tabbed-nominations" class="tab">
    <?php
    $usedSets = array();

    $stmt = $conn->prepare("SELECT bm.*, s.* FROM beatmaps bm JOIN beatmapsets s ON bm.SetID = s.SetID JOIN beatmapset_nominators bn ON bm.SetID = bn.SetID WHERE bn.NominatorID = ? AND bm.Mode = ? ORDER BY -ChartRank DESC, RatingCount DESC;");
    $stmt->bind_param("ii", $profileId, $mode);
    $stmt->execute();
    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()){
        if (in_array($row["SetID"], $usedSets))
            continue;

        $artist = safe_htmlspecialchars($row["Artist"], ENT_QUOTES);
        $title = safe_htmlspecialchars($row["Title"], ENT_QUOTES);
        $diffname = safe_htmlspecialchars($row["DifficultyName"], ENT_QUOTES);
        $avgRating = number_format((float)$row["Rating"], 2);

        ?>
        <div style='padding-left:0.25em;height:5em;display:flex;align-items: center;' class='alternating-bg'>
            <div>
                <a href="/mapset/<?php echo $row['SetID']; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row['SetID']; ?>l.jpg" class="diffThumb" style="height:4em;width:4em;margin-right:0.5em;" onerror="this.onerror=null; this.src='../assets/img/missing-map-thumbnail.png';" /></a>
            </div>
            <div>
                <a href="/mapset/<?php echo $row['SetID']; ?>"><?php echo "{$artist} - {$title} [$diffname]"; ?></a> <br>
                <?php if (!is_null($row["RatingCount"])) { ?><b><?php echo number_format((float)$row["WeightedAvg"], 2); ?></b> <span class="subText">/ 5.00 from <span style="color: var(--main-theme-text-color);"><?php echo $row["RatingCount"]; ?></span> votes</span><?php } ?><?php if (!is_null($row["ChartRank"])) { ?>,
                <b>#<?php echo $row["ChartRank"]; ?></b> <span class="subText">overall</span> <?php } ?>
            </div>
        </div>
        <?php



        $usedSets[] = $row["SetID"];
    }
    ?>
</div>
