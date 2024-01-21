<?php
    include "../../base.php";

    $profileId = $_GET["id"];
?>

<div id="tabbed-stats" class="tab" style="padding: 2em;">
    <div class="flex-container">
        <div class="flex-child" style="width:50%;">
            <?php
            $stmt = $conn->prepare("
                    SELECT YEAR(s.`dateranked`) AS Year, AVG(r.`Score`) AS AverageRating, COUNT(*) AS RatingCount
                    FROM `ratings` r
                    JOIN `beatmaps` b ON r.`BeatmapID` = b.`BeatmapID`
                    LEFT JOIN `beatmapsets` s on b.SetID = s.SetID
                    WHERE r.`UserID` = ?
                    GROUP BY YEAR(s.`dateranked`)
                    ORDER BY YEAR(s.`dateranked`);");
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
                    SELECT s.`Genre`, AVG(r.`Score`) AS AverageRating, COUNT(*) AS RatingCount
                    FROM `ratings` r
                    JOIN `beatmaps` b ON r.`BeatmapID` = b.`BeatmapID`
                    JOIN `beatmapsets` s on b.SetID = s.SetID
                    WHERE r.`UserID` = ?
                    GROUP BY s.`Genre`
                    ORDER BY s.`Genre`;
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

                    $value = null;
                    if (array_key_exists($genre, $genres)) {
                        $averageRating = $genres[$genre]["AverageRating"];
                        $ratingCount = $genres[$genre]["RatingCount"];

                        if ($ratingCount > 5)
                            $value = $averageRating / 5.0;
                    }

                    echo "<div class='year-box' value='{$value}'><span title='({$ratingCount}) {$averageRating}' style='border-bottom:1px dotted black;font-size: 8px;'>{$genreString}</span></div>";
                }
                ?>
            </div> <br>

            <?php
            $stmt = $conn->prepare("
                    SELECT s.`Lang`, AVG(r.`Score`) AS AverageRating, COUNT(*) AS RatingCount
                    FROM `ratings` r
                    JOIN `beatmaps` b ON r.`BeatmapID` = b.`BeatmapID`
                    JOIN `beatmapsets` s on b.SetID = s.SetID
                    WHERE r.`UserID` = ?
                    GROUP BY s.`Lang`
                    ORDER BY s.`Lang`;
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
                                      COUNT(s.SetID) as SetCount,
                                      COUNT(DISTINCT CASE WHEN `BeatmapID` IN (SELECT DISTINCT `BeatmapID` FROM ratings WHERE UserID = ?) THEN s.SetID END) as RatedSetCount 
                                      FROM beatmaps b
                                      JOIN `beatmapsets` s on b.SetID = s.SetID
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