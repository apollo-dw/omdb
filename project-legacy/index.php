<?php
    $PageTitle = "Project Legacy";
    require("../header.php");

    $stmt = $conn->query("SELECT COUNT(DISTINCT bm.SetID) as count FROM beatmaps bm LEFT JOIN beatmapset_nominators bn ON bm.SetID = bn.SetID WHERE bn.SetID IS NULL;");
    $setsLeft = $stmt->fetch_assoc()["count"];

    $edits = $conn->query("SELECT UserID, COUNT(*) as count FROM beatmap_edit_requests WHERE setid IS NOT NULL AND status = 'approved' GROUP BY UserID ORDER BY count DESC LIMIT 10;");

    $stmt = $conn->query("SELECT * FROM beatmaps WHERE SetID NOT IN (SELECT SetID FROM beatmapset_nominators) AND ChartRank IS NOT NULL AND Mode = 0 AND Status != 4 ORDER BY ChartRank LIMIT 18;");
?>

    <div style="width:100%;text-align:center;padding-top:2em;padding-bottom:5em;background-color:darkslategrey;">
        <h2><?php echo $setsLeft; ?> sets left.</h2>
        There are <?php echo $setsLeft; ?> sets from modding v1 that have missing nominator data, and this project tracks progress on backfilling it. <br>
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
            while($row = $stmt->fetch_assoc()){
                echo "<div class='alternating-bg' style='box-sizing:content-box;height:1.5em;'>
						#{$row["ChartRank"]}: {$row["Artist"]} - {$row["Title"]}
					  </div>";
            }
            ?>
        </div>
    </div>

<?php
require("../footer.php");
?>