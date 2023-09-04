<?php
$PageTitle = "Project Legacy";
require("../header.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$stmt = $conn->prepare("SELECT COUNT(DISTINCT bm.SetID) as count FROM beatmaps bm LEFT JOIN beatmapset_nominators bn ON bm.SetID = bn.SetID WHERE bn.SetID IS NULL AND bm.Mode = ?;");
$stmt->bind_param("i", $mode);
$stmt->execute();
$setsLeft = $stmt->get_result()->fetch_assoc()["count"];
$stmt->close();

$edits = $conn->query("SELECT UserID, COUNT(*) as count FROM beatmap_edit_requests WHERE setid IS NOT NULL AND status = 'approved' GROUP BY UserID ORDER BY count DESC LIMIT 25;");

$stmt = $conn->prepare("SELECT * FROM beatmaps WHERE SetID NOT IN (SELECT SetID FROM beatmapset_nominators) AND ChartRank IS NOT NULL AND Mode = ? AND Status != 4 ORDER BY ChartRank LIMIT 42;");
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
            Most approved nominator edits
            <?php
            $counter = 0;
            while($row = $edits->fetch_assoc()){
                $counter += 1;
                $username = GetUserNameFromId($row["UserID"], $conn);

                echo "<div class='alternating-bg' style='padding:0.25em;box-sizing:content-box;height:2em;'>
						#{$counter}
						<a href='/profile/{$row["UserID"]}'>
							<img src='https://s.ppy.sh/a/{$row["UserID"]}' style='height:24px;width:24px;' title='{$username}'/>
						</a>
						{$username} - {$row["count"]}
					  </div>";
            }
            ?>
        </div>
        <div class="flex-child" style="width:50%">
            Highest charting maps without nominator data
            <?php
            while($row = $result->fetch_assoc()){
                echo "<div class='alternating-bg' style='box-sizing:content-box;min-height:1.5em;'>
						<a href='/mapset/{$row["SetID"]}'>#{$row["ChartRank"]}: {$row["Artist"]} - {$row["Title"]}</a>
					  </div>";
            }
            ?>
        </div>
    </div>

<?php
require("../footer.php");
?>