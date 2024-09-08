<?php
	$PageTitle = "Project Legacy";
	require("../header.php");
	$stmt = $conn->prepare("SELECT COUNT(DISTINCT bm.SetID) as count FROM beatmaps bm LEFT JOIN beatmapset_nominators bn ON bm.SetID = bn.SetID WHERE bn.SetID IS NULL AND Status IN (1, 2) AND bm.Mode = ?;");
	$stmt->bind_param("i", $mode);
	$stmt->execute();
	$setsLeft = $stmt->get_result()->fetch_assoc()["count"];
	$stmt->close();

	$edits = $conn->query("SELECT UserID, COUNT(*) as count FROM beatmap_edit_requests WHERE setid IS NOT NULL AND status = 'approved' GROUP BY UserID ORDER BY count DESC LIMIT 60;");
?>

<div style="width:100%;text-align:center;padding-top:2em;padding-bottom:2em;background-color:darkslategrey;">
	<h2><?php echo $setsLeft; ?> sets left.</h2>
	There are <?php echo $setsLeft; ?> sets from modding v1 that have missing nominator data, and this project tracks progress on backfilling it. <br>
    <span class="subText">
		#1 gets 6 months supporter <br>
		#2 gets 4 months supporter <br>
		#3 gets 2 months supporter <br>
		<br>
		everyone who gets more than 300 edits will get 1 month supporter <br>
		anyone above 50 edits will get a user title on your omdb profile <br>
		<br>
		thank you hivie for offering #1 - #3 rewards!
	</span>
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
		Oldest maps without nominator data
		<div style="height:40em;overflow: scroll;">
			<?php
				$usedSets = array();
				
				$stmt = $conn->prepare("SELECT * FROM beatmaps b JOIN beatmapsets s on b.SetID = s.SetID WHERE s.SetID NOT IN (SELECT DISTINCT SetID FROM beatmapset_nominators) AND s.SetID NOT IN (SELECT DISTINCT SetID FROM beatmap_edit_requests WHERE Status = 'Pending' AND SetID IS NOT NULL) AND Mode = ? AND s.Status IN (1, 2) ORDER BY DateRanked LIMIT 250;");
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
				
				$stmt = $conn->prepare("SELECT * FROM beatmapsets s LEFT JOIN beatmaps b ON s.SetID = b.SetID WHERE s.SetID NOT IN (SELECT DISTINCT SetID FROM beatmapset_nominators) AND s.SetID NOT IN (SELECT DISTINCT SetID FROM beatmap_edit_requests WHERE Status = 'Pending' AND SetID IS NOT NULL) AND Mode = ? AND s.Status IN (1, 2) ORDER BY DateRanked DESC LIMIT 250;");
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