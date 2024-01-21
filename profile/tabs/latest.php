<?php
    if (file_exists("../../base.php"))
        include "../../base.php";

    $profileId = $_GET["id"];
?>

<div id="tabbed-latest" class="tab">
    <?php
    $stmt = $conn->prepare("SELECT r.*, b.*, t.Tags, s.*
                                FROM (
                                    SELECT r.`RatingID`, GROUP_CONCAT(t.`Tag` SEPARATOR ', ') AS Tags
                                    FROM `ratings` r
                                    JOIN `beatmaps` b ON r.`BeatmapID` = b.`BeatmapID`
                                    LEFT JOIN `rating_tags` t ON t.`BeatmapID` = b.`BeatmapID` AND t.`UserID` = r.`UserID`
                                    WHERE r.`UserID` = ? AND b.`Mode` = ?
                                    GROUP BY r.`RatingID`
                                ) AS t
                                JOIN `ratings` r ON t.`RatingID` = r.`RatingID`
                                JOIN `beatmaps` b ON r.`BeatmapID` = b.`BeatmapID`
                                JOIN `beatmapsets` s on b.SetID = s.SetID
                                ORDER BY r.`date` DESC
                                LIMIT 50");
    $stmt->bind_param("ii", $profileId, $mode);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($beatmap = $result->fetch_assoc()) {
        $tags = htmlspecialchars($beatmap['Tags'], ENT_COMPAT, "ISO-8859-1")
        ?>
        <div class="flex-container ratingContainer alternating-bg">
            <div class="flex-child">
                <a href="/mapset/<?php echo $beatmap["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $beatmap['SetID']; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='../charts/INF.png';"></a>
            </div>
            <div class="flex-child" style="flex:0 0 85%;">
                <?php echo RenderUserRating($conn, $beatmap); ?> on <a href="/mapset/<?php echo $beatmap["SetID"]; ?>"><?php echo mb_strimwidth(htmlspecialchars("{$beatmap["Title"]} [{$beatmap["DifficultyName"]}]"), 0, 80, "..."); ?></a>
                <br>
                <span class="subText"><?php echo $tags; ?></span>
            </div>
            <div class="flex-child" style="margin-left:auto;">
                <?php echo GetHumanTime($beatmap["date"]); ?>
            </div>
        </div>
        <?php
    }
    ?>
    <a href="ratings/?id=<?php echo $profileId; ?>&p=1"><span style="float:right;margin:1em;">... see more!</span></a>
    <br>
</div>

