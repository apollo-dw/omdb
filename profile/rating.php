<?php
    include_once '../connection.php';
    include_once '../functions.php';

    $stmt = $conn->prepare("SELECT Count(*) as count FROM rating_tags WHERE UserID = ?;");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $tagCount = $stmt->get_result()->fetch_assoc()["count"];
    $stmt->close();

    $stmt = $conn->prepare("SELECT Count(*) as count FROM beatmapset_nominators WHERE NominatorID = ?;");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $nominationCount = $stmt->get_result()->fetch_assoc()["count"];
    $stmt->close();

?>

<style>
    #tabbed-stats .year-box{
        width: 3.5em;
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
        color: rgba(0, 0, 0, 0.7);
    }

    #tabbed-ratings .profile-rating-distribution-bar{
        background-color: lightgray;
        margin: 0;
        padding: 0;
        height: 2em;
        text-align: left;
        white-space: nowrap;
    }

    #tabbed-ratings table, #tabbed-ratings tr, #tabbed-ratings td{
        text-align: center;
        vertical-align: middle;
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        border-spacing: 0;
    }

    #tabbed-ratings tr, #tabbed-ratings td {
        padding: 0.1em;
        height: 4em;
    }
</style>

<div class="tabbed-container-nav">
    <?php
    $tabs = [
        ["id" => "tabbed-latest", "text" => "Latest", "hide" => !$isValidUser],
        ["id" => "tabbed-ratings", "text" => "Ratings", "hide" => !$isValidUser],
        ["id" => "tabbed-stats", "text" => "Stats", "hide" => !$isValidUser],
        ["id" => "tabbed-tags", "text" => "Tags ($tagCount)", "hide" => $tagCount == 0],
        ["id" => "tabbed-nominations", "text" => "Nominations ($nominationCount)", "hide" => $nominationCount == 0]
    ];

    $firstButtonActive = false;
    $initiallyActiveTab = "";

    foreach ($tabs as $tab) {
        if (!$tab["hide"]) {
            if (!$firstButtonActive) {
                $class = ' class="active"';
                $firstButtonActive = true;
                $initiallyActiveTab = $tab['id'];
            } else {
                $class = '';
            }

            echo "<button$class onclick=\"openTab('{$tab['id']}')\">{$tab['text']}</button>";
        }
    }
    ?>
</div>

<?php if ($isValidUser) { ?>
    <div id="tabbed-latest" class="tab">
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

    <div id="tabbed-ratings" class="tab" style="display:none;">
        <table style="width:100%;">
            <?php for ($rating = 5.0; $rating >= 0.0; $rating -= 0.5){ ?>
                <?php
                $formattedRating = number_format($rating, 1);
                $ratingCount = $ratingCounts[$formattedRating] ?? 0;
                $ratingBarWidth = ($ratingCount / $maxRating) * 90;
                ?>
                <tr class="alternating-bg">
                    <td style="width:20%;">
                        <a href="ratings/?id=<?php echo $profileId; ?>&r=<?php echo $formattedRating; ?>&p=1"><?php echo $formattedRating; ?><br>
                            <?php if ($profile["Custom" . str_replace('.', '', $formattedRating) . "Rating"] != ""){ ?>
                                <span class="subText"><?php echo htmlspecialchars($profile["Custom" . str_replace('.', '', $formattedRating) . "Rating"]); ?></span>
                            <?php } ?>
                        </a>
                    </td>
                    <td style="width:5%;">
                        <?php echo $ratingCount; ?>
                    </td>
                    <td style="width:100%;">
                        <a href="ratings/?id=<?php echo $profileId; ?>&r=<?php echo $formattedRating; ?>&p=1"> <div class="profile-rating-distribution-bar" style="width: <?php echo $ratingBarWidth; ?>%;">&nbsp;</div></a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>

    <?php if ($tagCount > 0 ) { ?>
        <div id="tabbed-tags" class="tab" style="display:none;padding: 2em;">
            <?php
            $stmt = $conn->prepare("SELECT Tag, COUNT(*) AS TagCount FROM rating_tags WHERE UserID = ? GROUP BY Tag ORDER BY TagCount DESC;");
            $stmt->bind_param('i', $profileId);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $tag = htmlspecialchars($row["Tag"], ENT_COMPAT, "ISO-8859-1");
                $encodedTag = urlencode($tag);
                echo "<a href='ratings/?id={$profileId}&t={$encodedTag}'>{$tag} ({$row["TagCount"]})</a> <br>";
            }
            ?>
        </div>
    <?php } ?>

    <div id="tabbed-stats" class="tab" style="display:none;padding: 2em;">
        <div class="flex-container">
            <div class="flex-child" style="width:50%;">
                <?php
                $stmt = $conn->prepare("
                    SELECT YEAR(b.`dateranked`) AS Year, AVG(r.`Score`) AS AverageRating, COUNT(*) AS RatingCount
                    FROM `ratings` r
                    JOIN `beatmaps` b ON r.`BeatmapID` = b.`BeatmapID`
                    WHERE r.`UserID` = ?
                    GROUP BY YEAR(b.`dateranked`)
                    ORDER BY YEAR(b.`dateranked`);");
                $stmt->bind_param('i', $profileId);
                $stmt->execute();
                $result = $stmt->get_result();

                $years = array();
                while ($row = $result->fetch_assoc()) {
                    $year = $row["Year"];
                    $years[$year] = array(
                        "AverageRating" => $row["AverageRating"],
                        "RatingCount" => $row["RatingCount"]
                    );
                }
                ?>
                Year affinities:
                <div class="flex-row-container" style="width: 22em;">
                    <?php
                    $minRating = min(array_column($years, "AverageRating"));
                    $maxRating = max(array_column($years, "AverageRating"));

                    for ($year = date('Y'); $year >= 2007; $year--) {
                        $averageRating = "none";
                        $ratingCount = 0;

                        if (array_key_exists($year, $years)) {
                            $averageRating = $years[$year]["AverageRating"];
                            $ratingCount = $years[$year]["RatingCount"];

                            if ($ratingCount > 5)
                                $value = $averageRating / 5.0;
                            else
                                $value = null;
                        }

                        echo "<div class='year-box' value='{$value}'><span title='({$ratingCount}) {$averageRating}' style='border-bottom:1px dotted black;'>" . substr($year, -2) . "</span></div>";
                    }
                    ?>
                </div> <br>

                <?php
                $stmt = $conn->prepare("
                SELECT (b.`SR` DIV 1) AS SRRange, AVG(r.`Score`) AS AverageRating, COUNT(*) AS RatingCount
                FROM `ratings` r
                JOIN `beatmaps` b ON r.`BeatmapID` = b.`BeatmapID`
                WHERE r.`UserID` = ?
                GROUP BY SRRange
                ORDER BY SRRange;
                ");
                $stmt->bind_param('i', $profileId);
                $stmt->execute();
                $result = $stmt->get_result();

                $starRatings = array();
                while ($row = $result->fetch_assoc()) {
                    $SRRange = $row["SRRange"];
                    $starRatings[$SRRange] = array(
                        "AverageRating" => $row["AverageRating"],
                        "RatingCount" => $row["RatingCount"]
                    );
                }
                ?>

                Star rating affinities:
                <div class="flex-row-container" style="width: 22em;">
                    <?php
                    $minSR = min(array_column($starRatings, "AverageRating"));
                    $maxSR = max(array_column($starRatings, "AverageRating"));
                    for ($SR = 0; $SR <= 13; $SR++) {
                        $averageRating = "none";
                        $ratingCount = 0;

                        if (array_key_exists($SR, $starRatings)) {
                            $averageRating = $starRatings[$SR]["AverageRating"];
                            $ratingCount = $starRatings[$SR]["RatingCount"];

                            if ($ratingCount > 5)
                                $value = $averageRating / 5.0;
                            else
                                continue;
                        } else {
                            continue;
                        }

                        echo "<div class='year-box' value='{$value}'><span title='({$ratingCount}) {$averageRating}' style='border-bottom:1px dotted black;'>" . $SR . "*</span></div>";
                    }
                    ?>
                </div> <br>

                <?php
                $stmt = $conn->prepare("
                    SELECT b.`Genre`, AVG(r.`Score`) AS AverageRating, COUNT(*) AS RatingCount
                    FROM `ratings` r
                    JOIN `beatmaps` b ON r.`BeatmapID` = b.`BeatmapID`
                    WHERE r.`UserID` = ?
                    GROUP BY b.`Genre`
                    ORDER BY b.`Genre`;
                    ");
                $stmt->bind_param('i', $profileId);
                $stmt->execute();
                $result = $stmt->get_result();

                $genres = array();
                while ($row = $result->fetch_assoc()) {
                    $genre = $row["Genre"];
                    $genres[$genre] = array(
                        "AverageRating" => $row["AverageRating"],
                        "RatingCount" => $row["RatingCount"]
                    );
                }
                ?>

                Genre affinities:
                <div class="flex-row-container" style="width: 22em;">
                    <?php
                    $minGenre = min(array_column($genres, "AverageRating"));
                    $maxGenre = max(array_column($genres, "AverageRating"));

                    for ($genre = 0; $genre <= 14; $genre++) {
                        $genreString = getGenre($genre);

                        if (is_null($genreString))
                            continue;

                        $averageRating = "none";
                        $ratingCount = 0;

                        if (array_key_exists($genre, $genres)) {
                            $averageRating = $genres[$genre]["AverageRating"];
                            $ratingCount = $genres[$genre]["RatingCount"];

                            if ($ratingCount > 5)
                                $value = $averageRating / 5.0;
                            else
                                $value = null;
                        }

                        echo "<div class='year-box' value='{$value}'><span title='({$ratingCount}) {$averageRating}' style='border-bottom:1px dotted black;font-size: 8px;'>{$genreString}</span></div>";
                    }
                    ?>
                </div> <br>

                <?php
                $stmt = $conn->prepare("
                    SELECT b.`Lang`, AVG(r.`Score`) AS AverageRating, COUNT(*) AS RatingCount
                    FROM `ratings` r
                    JOIN `beatmaps` b ON r.`BeatmapID` = b.`BeatmapID`
                    WHERE r.`UserID` = ?
                    GROUP BY b.`Lang`
                    ORDER BY b.`Lang`;
                    ");
                $stmt->bind_param('i', $profileId);
                $stmt->execute();
                $result = $stmt->get_result();

                $languages = array();
                while ($row = $result->fetch_assoc()) {
                    $language = $row["Lang"];
                    $languages[$language] = array(
                        "AverageRating" => $row["AverageRating"],
                        "RatingCount" => $row["RatingCount"]
                    );
                }
                ?>

                Language affinities:
                <div class="flex-row-container" style="width: 22em;">
                    <?php
                    $minGenre = min(array_column($languages, "AverageRating"));
                    $maxGenre = max(array_column($languages, "AverageRating"));

                    for ($language = 0; $language <= 14; $language++) {
                        $languageString = getLanguage($language);

                        if (is_null($languageString))
                            continue;

                        $averageRating = "none";
                        $ratingCount = 0;

                        if (array_key_exists($language, $languages)) {
                            $averageRating = $languages[$language]["AverageRating"];
                            $ratingCount = $languages[$language]["RatingCount"];

                            if ($ratingCount > 5)
                                $value = $averageRating / 5.0;
                            else
                                continue;
                        } else {
                            continue;
                        }

                        echo "<div class='year-box' value='{$value}'><span title='({$ratingCount}) {$averageRating}' style='border-bottom:1px dotted black;font-size: 8px;'>{$languageString}</span></div>";
                    }
                    ?>
                </div>
            </div>
            <div class="flex-child" style="width:50%;">
                <?php
                $stmt = $conn->prepare("SELECT AVG(r.`Score`) AS AverageScore, 
                                       IFNULL(STDDEV(r.`Score`), 0) AS StandardDeviation
                               FROM ratings r
                               WHERE r.`UserID` = ?");
                $stmt->bind_param("i", $profileId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();

                $averageScore = $row["AverageScore"];
                $standardDeviation = $row["StandardDeviation"];

                echo "Average score: {$averageScore} <br>";
                echo "Standard deviation: {$standardDeviation} <br><br>";
                ?>

                Set completion: <br>
                <?php
                $stmt = $conn->prepare("SELECT YEAR(`dateranked`) as Year, 
                                      COUNT(DISTINCT SetID) as SetCount,
                                      COUNT(DISTINCT CASE WHEN `BeatmapID` IN (SELECT DISTINCT `BeatmapID` FROM ratings WHERE UserID = ?) THEN SetID END) as RatedSetCount 
                                      FROM beatmaps 
                                      GROUP BY YEAR(`dateranked`) 
                                      ORDER BY YEAR(`dateranked`);");
                $stmt->bind_param('i', $profileId);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    echo "<span class='subText'><b>{$row["Year"]}</b>: {$row["RatedSetCount"]} / {$row["SetCount"]}</span> <br>";
                }
                ?>
            </div>
        </div>
    </div>
<?php } ?>

<?php if ($nominationCount > 0 ) { ?>
    <div id="tabbed-nominations" class="tab" style="padding-top:0.5em;<?php if ($initiallyActiveTab != "tabbed-nominations") echo "display:none;" ?>">
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
<?php } ?>

<script>
    function setBackgroundColors() {
        const colors = [
            '#742d2d',
            '#953d3d',
            '#b64545',
            '#bf5a4d',
            '#d2872f',
            '#db9a25',
            '#e0bc20',
            '#e4d541',
            '#b9d141',
            '#adc63b',
            '#92c22a',
            '#599e3c',
            '#477e3d',
            '#284a25'
        ]

        const yearBoxes = document.querySelectorAll('.year-box');

        yearBoxes.forEach((box) => {
            let proportion = parseFloat(box.getAttribute('value'));
            color = chroma.scale(colors)(proportion).hex();
            box.style.backgroundColor = color;
        });
    }

    setBackgroundColors();
</script>