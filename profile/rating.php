<?php
	include_once '../connection.php';
	include_once '../functions.php';
    ?>

<style>
    .tabbed-container-nav{
        width:100%;
        border-bottom: 1px solid white;
    }

    .tabbed-container-nav button {
        height:3em;
        border: 0;
        margin: 0;
        background-color: darkslategray;
    }

    .tabbed-container-nav button:hover {
        background-color: #182828;
    }

    .tabbed-container-nav button.active{
        background-color: #0C1515;
    }

    #tabbed-stats .year-box{
        width: 3em;
        display: flex;
        padding: 0.5em;
        text-align: center;
        aspect-ratio: 1 / 1;
        vertical-align: middle;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        border: 1px solid white;
        background-color: black;
    }
</style>

<div class="tabbed-container-nav">
    <button class="active" onclick="openTab('tabbed-ratings')">Latest</button><button onclick="openTab('tabbed-tags')">Tags</button><button onclick="openTab('tabbed-stats')">Stats</button>
</div>

<div id="tabbed-ratings" class="tab">
    <?php
        $stmt = $conn->prepare("SELECT r.*, b.*, t.Tags
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

<div id="tabbed-tags" class="tab" style="display:none;padding: 2em;">
    <?php
        $stmt = $conn->prepare("SELECT Tag, COUNT(*) AS TagCount FROM rating_tags WHERE UserID = ? GROUP BY Tag ORDER BY TagCount;");
        $stmt->bind_param('i', $profileId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $tag = htmlspecialchars($row["Tag"], ENT_COMPAT, "ISO-8859-1");
            echo "{$tag} ({$row["TagCount"]}) <br>";
        }
    ?>
</div>

<div id="tabbed-stats" class="tab" style="display:none;padding: 2em;">
    <?php
        $stmt = $conn->prepare("
                SELECT YEAR(b.`dateranked`) AS Year, AVG(r.`Score`) AS AverageRating
                FROM `ratings` r
                JOIN `beatmaps` b ON r.`BeatmapID` = b.`BeatmapID`
                WHERE r.`UserID` = ?
                GROUP BY YEAR(b.`dateranked`)
                ORDER BY YEAR(b.`dateranked`);");
        $stmt->bind_param('i', $profileId);
        $stmt->execute();
        $result = $stmt->get_result();

        $years = array();
        while ($row = $result->fetch_assoc())
            $years[$row["Year"]] = $row["AverageRating"];
    ?>
    Year affinities:
    <div class="flex-row-container" style="width: 18em;">
        <?php
            for ($year = date('Y'); $year >= 2007; $year--) {
                $averageRating = "none";
                if (array_key_exists($year, $years)){
                    $averageRating = $years[$year];
                    $deviation = ($averageRating) * 30;
                    $hue = $deviation;

                    $saturation = min(50, (abs($averageRating - 2.0) * 25) + 10);
                } else {
                    $hue = null;
                    $saturation = null;
                }

                echo '<div class="year-box" style="background-color: hsl(' . $hue . ', ' . $saturation .'%, 40%);"><span title=' . $averageRating . ' style="border-bottom:1px dotted white;">' . substr($year, -2) . '</span></div>';
            }
        ?>
    </div>
</div>


<script>
    function openTab(name) {
        let x = document.getElementsByClassName("tab");
        for (let i = 0; i < x.length; i++)
            x[i].style.display = "none";

        let buttons = document.getElementsByClassName("tabbed-container-nav")[0].getElementsByTagName("button");
        for (let i = 0; i < buttons.length; i++)
            buttons[i].classList.remove("active");

        document.getElementById(name).style.display = "block";
        event.target.classList.add("active");
    }
</script>


