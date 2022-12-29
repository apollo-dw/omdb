<?php
    $PageTitle = "Home";
    require 'header.php';
?>

welcome to OMDB - a place to rate maps! discover new maps, check out people's ratings, AND STUFF. <br>
<span style="color:grey;">
	<?php echo $conn->query("SELECT Count(*) FROM `users`;")->fetch_row()[0]; ?> users, 
	<?php echo $conn->query("SELECT Count(*) FROM `ratings`;")->fetch_row()[0]; ?> ratings,
	<?php echo $conn->query("SELECT Count(*) FROM `comments`;")->fetch_row()[0]; ?> comments
</span><hr>

<p style="width:66%;">This website is still in development pretty much. Some things might be weird. Mobile will definitely work pretty bad rn so I recommend using ur computor for this. Search absolutely sucks, I know. but u can paste in a beatmap link in there for now. Also some maps are missing randomly so I need 2 fix that</p>

<div class="flex-container">
	<div class="flex-child" style="width:40%;height:40em;overflow-y:scroll;">
		<?php
		  $counter = 0;

		  $stmt = $conn->prepare("SELECT r.*, b.DifficultyName, b.SetID FROM `ratings` r INNER JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID ORDER BY r.date DESC LIMIT 50;");
		  $stmt->execute();
		  $result = $stmt->get_result();

		  while($row = $result->fetch_assoc()) {
			$counter += 1;
		  ?>
			<div class="flex-container ratingContainer" <?php if($counter % 2 == 1){ echo "style='background-color:#203838;'"; } ?>>
			  <div class="flex-child">
				<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
			  </div>
			  <div class="flex-child" style="height:24px;width:24px;">
				<a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
			  </div>
			  <div class="flex-child" style="flex:0 0 50%;">
				<?php
				  echo $row["Score"] . " on " . "<a style='color:white;' href='/mapset/" . $row["SetID"] . "'>" . mb_strimwidth(htmlspecialchars($row["DifficultyName"]), 0, 35, "...") . "</a>";
				?>
			  </div>
			  <div class="flex-child" style="width:100%;text-align:right;min-width:0%;">
				<?php echo time_elapsed_string($row["date"]); ?>
			  </div>
			</div>
		  <?php
		  }
		  
		  $stmt->close();
		?>
	</div>
	<div class="flex-child" style="width:60%;height:40em;overflow-y:scroll;">
		<?php
		  $counter = 0;

		  $stmt = $conn->prepare("SELECT * FROM `comments` ORDER BY date DESC LIMIT 30;");
		  $stmt->execute();
		  $result = $stmt->get_result();

		  while($row = $result->fetch_assoc()) {
			$counter += 1;
		  ?>
			<div class="flex-container ratingContainer" <?php if($counter % 2 == 1){ echo "style='background-color:#203838;'"; } ?>>
			  <div class="flex-child">
				<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
			  </div>
			  <div class="flex-child" style="height:24px;width:24px;">
				<a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
			  </div>
			  <div class="flex-child" style="flex:0 0 60%;text-overflow:elipsis;min-width:0%;">
				<a href="/mapset/<?php echo $row["SetID"]; ?>"><?php
				  echo mb_strimwidth(htmlspecialchars($row["Comment"]), 0, 55, "...");
				?></a>
			  </div>
			  <div class="flex-child" style="width:100%;text-align:right;min-width:0%;">
				<?php echo time_elapsed_string($row["date"]); ?>
			  </div>
			</div>
		  <?php
		  }
		  
		  $stmt->close();
		?>
	</div>
</div>
<br>
Latest mapsets:<br>
<div class="flex-container" style="width:100%;background-color:DarkSlateGrey;justify-content: space-around;padding:0px;">
	<br>
	<?php
		$stmt = $conn->prepare("SELECT DISTINCT SetID, Artist, Title, CreatorID, Timestamp, DateRanked FROM `beatmaps` WHERE `Mode`='0' ORDER BY `DateRanked` DESC, `Timestamp` DESC LIMIT 8;");
		$stmt->execute();
		$result = $stmt->get_result();
		
		while($row = $result->fetch_assoc()) {
			$artist = GetUserNameFromId($row["CreatorID"], $conn);
	?>
	<div class="flex-child" style="text-align:center;width:11%;padding:0.5em;display: inline-block;margin-left:auto;margin-right:auto;">
		<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb" style="aspect-ratio: 1 / 1;width:90%;height:auto;" onerror="this.onerror=null; this.src='/charts/INF.png';"></a><br>
		<span class="subtext">
			<a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo "{$row["Artist"]} - {$row["Title"]}"; ?></a><br> by <a href="/profile/<?php echo $row["CreatorID"]; ?>"><?php echo $artist; ?></a> <br>
			<?php echo time_elapsed_string($row["Timestamp"]); ?>
		</span>
	</div>
	<?php
		}
		
		$stmt->close();
	?>
</div>

<?php
    require 'footer.php';
?>