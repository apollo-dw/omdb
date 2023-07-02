<?php
    $PageTitle = "Home";
	require "base.php";
    require 'header.php';
?>

welcome to OMDB - a place to rate maps! discover new maps, check out people's ratings, AND STUFF. <br>
<span style="color:grey;">
    <?php
    $stmt = $conn->prepare("SELECT COUNT(*) FROM `users`");
    $stmt->execute();
    $result = $stmt->get_result();
    $usersCount = $result->fetch_row()[0];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM `ratings`");
    $stmt->execute();
    $result = $stmt->get_result();
    $ratingsCount = $result->fetch_row()[0];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM `comments`");
    $stmt->execute();
    $result = $stmt->get_result();
    $commentsCount = $result->fetch_row()[0];
    $stmt->close();
    ?>

    <?php echo $usersCount; ?> users,
    <?php echo $ratingsCount; ?> ratings,
    <?php echo $commentsCount; ?> comments
</span>
    <hr>

<p style="width:66%;">This website is still in development pretty much. Some things might be weird. Mobile will definitely work pretty bad rn so I recommend using ur computor for this.</p>

<div class="flex-container">
	<div class="flex-child" style="width:40%;height:32em;overflow-y:scroll;position:relative;">
		<?php
		  $stmt = $conn->prepare("SELECT r.*, b.DifficultyName, b.SetID FROM `ratings` r INNER JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID WHERE b.Mode = ? ORDER BY r.date DESC LIMIT 40;");
		  $stmt->bind_param("i", $mode);
          $stmt->execute();
		  $result = $stmt->get_result();

		  while($row = $result->fetch_assoc()) {
		  ?>
			<div class="flex-container ratingContainer alternating-bg">
			  <div class="flex-child">
				<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
			  </div>
			  <div class="flex-child" style="height:24px;width:24px;">
				<a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
			  </div>
			  <div class="flex-child" style="flex:0 0 50%;">
				<?php
				  echo renderRating($conn, $row) . " on " . "<a href='/mapset/" . $row["SetID"] . "'>" . mb_strimwidth(htmlspecialchars($row["DifficultyName"]), 0, 35, "...") . "</a>";
				?>
			  </div>
			  <div class="flex-child" style="width:100%;text-align:right;min-width:0%;">
				<?php echo GetHumanTime($row["date"]); ?>
			  </div>
			</div>
		  <?php
		  }
		  
		  $stmt->close();
		?>
	</div>
	<div class="flex-child" style="width:60%;height:32em;overflow-y:scroll;">
		<?php
          $stmt = $conn->prepare("SELECT c.*
                            FROM comments c
                            WHERE EXISTS (
                                SELECT 1
                                FROM beatmaps b
                                WHERE b.SetID = c.SetID AND b.Mode = ?
                            )
                            ORDER BY c.date DESC
                            LIMIT 20;");
          $stmt->bind_param("i", $mode);
          $stmt->execute();
          $result = $stmt->get_result();

          while($row = $result->fetch_assoc()) {
              $is_blocked = 0;

              if ($loggedIn) {
                  $stmt_relation_to_profile_user = $conn->prepare("SELECT * FROM user_relations WHERE UserIDFrom = ? AND UserIDTo = ? AND type = 2");
                  $stmt_relation_to_profile_user->bind_param("ii", $userId, $row["UserID"]);
                  $stmt_relation_to_profile_user->execute();
                  $is_blocked = $stmt_relation_to_profile_user->get_result()->num_rows > 0;
              }
		  ?>
			<div class="flex-container ratingContainer alternating-bg">
			  <div class="flex-child">
				<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
			  </div>
			  <div class="flex-child" style="height:24px;width:24px;">
				<a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
			  </div>
			  <div class="flex-child" style="flex:0 0 60%;text-overflow:elipsis;min-width:0%;">
                    <a style="color:white;" href="/mapset/<?php echo $row["SetID"]; ?>">
                        <?php
                            if (!$is_blocked)
                                echo htmlspecialchars(mb_strimwidth($row["Comment"], 0, 55, "..."));
                            else
                                echo "[blocked comment]";
                        ?>
                    </a>
			  </div>
			  <div class="flex-child" style="width:100%;text-align:right;min-width:0%;">
				<?php echo GetHumanTime($row["date"]); ?>
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
		$stmt = $conn->prepare("SELECT DISTINCT SetID, Artist, Title, SetCreatorID, Timestamp, DateRanked FROM `beatmaps` WHERE `Mode`= ? ORDER BY `Timestamp` DESC LIMIT 8;");
        $stmt->bind_param("i", $mode);
        $stmt->execute();
		$result = $stmt->get_result();
		
		while($row = $result->fetch_assoc()) {
			$artist = GetUserNameFromId($row["SetCreatorID"], $conn);
	?>
	<div class="flex-child" style="text-align:center;width:11%;padding:0.5em;display: inline-block;margin-left:auto;margin-right:auto;">
		<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb" style="aspect-ratio: 1 / 1;width:90%;height:auto;" onerror="this.onerror=null; this.src='/charts/INF.png';"></a><br>
		<span class="subtext">
			<a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo "{$row["Artist"]} - {$row["Title"]}"; ?></a><br> by <a href="/profile/<?php echo $row["SetCreatorID"]; ?>"><?php echo $artist; ?></a> <br>
			<?php echo GetHumanTime($row["Timestamp"]); ?>
		</span>
	</div>
	<?php
		}

		$stmt->close();
	?>
</div>
<br>
Most rated beatmaps in the last 7 days:<br>
<div style="width:100%;height:40em;">
    <?php
    $counter = 0;

    $stmt = $conn->prepare("SELECT b.BeatmapID, b.SetID, b.Title, b.Artist, b.DifficultyName, num_ratings
                                  FROM beatmaps b
                                  INNER JOIN (
                                        SELECT BeatmapID, COUNT(*) as num_ratings
                                        FROM ratings
                                        WHERE date >= now() - interval 1 week
                                        GROUP BY BeatmapID
                                  ) r ON b.BeatmapID = r.BeatmapID
                                  INNER JOIN (
                                        SELECT SetID, MAX(num_ratings) as max_ratings
                                        FROM (
                                            SELECT b.SetID, b.BeatmapID, COUNT(*) as num_ratings
                                            FROM beatmaps b
                                            INNER JOIN ratings r ON b.BeatmapID = r.BeatmapID
                                            WHERE r.date >= now() - interval 1 week
                                            GROUP BY b.SetID, b.BeatmapID
                                        ) t
                                        GROUP BY SetID
                                  ) m ON b.SetID = m.SetID AND r.num_ratings = m.max_ratings
								  WHERE b.mode = ?
                                  ORDER BY num_ratings DESC, b.BeatmapID DESC
                                  LIMIT 10;
                                  ");
    $stmt->bind_param("i", $mode);
    $stmt->execute();

    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()) {
        $counter += 1;
        ?>
        <div class="flex-container ratingContainer alternating-bg">
            <div class="flex-child" style="min-width:2em;">
                #<?php echo $counter; ?>
            </div>
            <div class="flex-child">
                <a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
            </div>
            <div class="flex-child" style="flex:0 0 80%;">
                <a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo "{$row["Artist"]} - {$row["Title"]} [{$row["DifficultyName"]}]";?></a>
            </div>
            <div class="flex-child" style="width:100%;text-align:right;min-width:0%;">
                <?php echo $row["num_ratings"];?> ratings
            </div>
        </div>
        <?php
    }

    $stmt->close();
    ?>
</div>
<?php
require 'footer.php';
?>