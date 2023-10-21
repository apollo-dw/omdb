<?php
$PageTitle = "Project Legacy";
require("../header.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$stmt = $conn->prepare("SELECT COUNT(DISTINCT bm.SetID) as count FROM beatmaps bm LEFT JOIN beatmapset_nominators bn ON bm.SetID = bn.SetID WHERE bn.SetID IS NULL AND Status = 1 AND bm.Mode = ?;");
$stmt->bind_param("i", $mode);
$stmt->execute();
$setsLeft = $stmt->get_result()->fetch_assoc()["count"];
$stmt->close();

$edits = $conn->query("SELECT UserID, COUNT(*) as count FROM beatmap_edit_requests WHERE setid IS NOT NULL AND status = 'approved' GROUP BY UserID ORDER BY count DESC LIMIT 40;");
$stmt->bind_param("i", $mode);
$stmt->execute();
$result = $stmt->get_result();
?>

    <div style="width:100%;text-align:center;padding-top:2em;padding-bottom:5em;background-color:darkslategrey;">
        <h2><?php echo $setsLeft; ?> sets left.</h2>
        There are <?php echo $setsLeft; ?> sets from modding v1 that have missing nominator data, and this project tracks progress on backfilling it. <br>
        <span class="subText">(you might get something cool in the future if you get at least 50 nominator edits!!)</span>
    </div>

    <div class="flex-container">
        <div class="flex-child" style="width:50%">
            Oldest maps without nominator data
            <div style="height:40em;overflow: scroll;">
                <?php
                $usedSets = array();

                $stmt = $conn->prepare("SELECT * FROM beatmaps WHERE SetID NOT IN (SELECT DISTINCT SetID FROM beatmapset_nominators) AND Mode = ? AND Status != 4 ORDER BY DateRanked LIMIT 250;");
                $stmt->bind_param("i", $mode);
                $stmt->execute();
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()){
                    if (in_array($row["SetID"], $usedSets))
                        continue;

                    echo "<div class='alternating-bg' style='box-sizing:content-box;min-height:1.5em;'>
							<a href='/mapset/{$row["SetID"]}'>{$row["DateRanked"]}: {$row["Artist"]} - {$row["Title"]}</a>
						  </div>";

                    $usedSets[] = $row["SetID"];
                }
                ?>
            </div>

            Newest maps without nominator data
            <div style="height:40em;overflow: scroll;">
                <?php
                $usedSets = array();

                $stmt = $conn->prepare("SELECT * FROM beatmaps WHERE SetID NOT IN (SELECT DISTINCT SetID FROM beatmapset_nominators) AND Mode = ? AND Status != 4 ORDER BY DateRanked DESC LIMIT 250;");
                $stmt->bind_param("i", $mode);
                $stmt->execute();
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()){
                    if (in_array($row["SetID"], $usedSets))
                        continue;

                    echo "<div class='alternating-bg' style='box-sizing:content-box;min-height:1.5em;'>
							<a href='/mapset/{$row["SetID"]}'>{$row["DateRanked"]}: {$row["Artist"]} - {$row["Title"]}</a>
						  </div>";

                    $usedSets[] = $row["SetID"];
                }
                ?>
            </div>
        </div>
    </div>

<?php
require("../footer.php");
?>