<?php
    $PageTitle = "Home";
	require "base.php";
    require 'header.php';
?>

welcome to OMDB - a place to rate maps! discover new maps, check out people's ratings, AND STUFF. <br>
<span style="color:grey;">
    <?php
    $stmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM `users`), COUNT(*) FROM `users` WHERE `LastAccessedSite` >= NOW() - INTERVAL 24 HOUR;");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_row();
	$usersCount = $row[0];
	$usersOnlineCount = $row[1];
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

    <span title='<?php echo $usersOnlineCount; ?> within the last day' style='border-bottom:1px dotted white;'><?php echo $usersCount; ?> users</span>,
    <?php echo $ratingsCount; ?> ratings,
    <?php echo $commentsCount; ?> comments
</span>
<hr>
<div class="flex-container column-when-mobile-container">
	<div class="flex-child column-when-mobile" style="width:40%;height:32em;overflow-y:scroll;position:relative;">
		<?php
		  $stmt = $conn->prepare("SELECT r.*, b.DifficultyName, b.SetID FROM `ratings` r INNER JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID WHERE b.Mode = ? ORDER BY r.date DESC LIMIT 40;");
		  $stmt->bind_param("i", $mode);
          $stmt->execute();
		  $result = $stmt->get_result();

		  while($row = $result->fetch_assoc()) {
		  ?>
			<div class="flex-container ratingContainer alternating-bg">
			    <div class="flex-child" style="margin-left:0.5em;">
				    <a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
			    </div>
                <div class="flex-child">
                    <a style="display:flex;" href="/profile/<?php echo $row["UserID"]; ?>">
                        <img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/>
                    </a>
                </div>
                <div class="flex-child" style="flex:0 0 66%;">
                    <a style="display:flex;" href="/profile/<?php echo $row["UserID"]; ?>">
                        <?php echo GetUserNameFromId($row["UserID"], $conn); ?>
                    </a>
                    <?php
                        echo RenderUserRating($conn, $row) . " on " . "<a href='/mapset/" . $row["SetID"] . "'>" . mb_strimwidth(htmlspecialchars($row["DifficultyName"]), 0, 35, "...") . "</a>";
                    ?>
                </div>
			</div>
		  <?php
		  }
		  $stmt->close();
		?>
	</div>
	<div class="flex-child column-when-mobile" style="width:60%;height:32em;overflow-y:scroll;">
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
			  <div class="flex-child" style="margin-left:0.5em;">
				<a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
			  </div>
                <div class="flex-child">
                    <div>
                        <a style="align-items:center; display:flex;" href="/profile/<?php echo $row["UserID"]; ?>">
                            <img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/>
                            <span style="margin-left: 0.5em;"><?php echo GetUserNameFromId($row["UserID"], $conn); ?></span>
                        </a>
                    </div>
                    <div style="flex:0 0 auto;text-overflow:ellipsis;min-width:0%;">
                        <a style="color:white;" href="/mapset/<?php echo $row["SetID"]; ?>">
                            <?php
                            if (!$is_blocked)
                                echo htmlspecialchars(mb_strimwidth($row["Comment"], 0, 180, "..."));
                            else
                                echo "[blocked comment]";
                            ?>
                        </a>
                    </div>
                </div>
			</div>
		  <?php
		  }
		  $stmt->close();
		?>
	</div>
</div>
<br>
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
			<a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo "{$row["Artist"]} - {$row["Title"]}"; ?></a><br>
            by <a href="/profile/<?php echo $row["SetCreatorID"]; ?>"><?php echo $artist; ?></a> <br>
			<?php echo GetHumanTime($row["Timestamp"]); ?>
		</span>
	</div>
	<?php
		}

		$stmt->close();
	?>
</div>
<br>
    <div class="flex-container column-when-mobile-container">
        <div class="flex-child column-when-mobile" style="width:50%;height:40em;background-color: darkslategray;padding: 0.5em;box-sizing:border-box;">
            <h2 style="margin-top:0;">Map of the week</h2>
            <?php
                $stmt = $conn->prepare(
                    "SELECT * FROM beatmaps
                                   WHERE
                                        DateRanked between date_sub(now(),INTERVAL 1 WEEK) and now()
                                        AND Rating IS NOT NULL
                                        AND Mode = ?
                                   ORDER BY
                                        ChartRank ASC
                                   LIMIT 1;");
                $stmt->bind_param("i", $mode);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $year = date("Y", strtotime($result['DateRanked']));

                $stmt = $conn->prepare("
                                SELECT d.DescriptorID, d.Name
                                FROM descriptor_votes 
                                JOIN descriptors d on descriptor_votes.DescriptorID = d.DescriptorID
                                WHERE BeatmapID = ?
                                GROUP BY DescriptorID
                                HAVING SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) > (SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END) + 0)
                                ORDER BY (SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) - SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END)) DESC, DescriptorID
                                LIMIT 5;");
                $stmt->bind_param("i", $result["BeatmapID"]);
                $stmt->execute();
                $descriptorResult = $stmt->get_result();
            ?>
            <div style="width:100%;text-align:center;">
                <a href="/mapset/<?php echo $result["SetID"]; ?>"><img src="https://assets.ppy.sh/beatmaps/<?php echo $result["SetID"]; ?>/covers/cover.jpg" style="width:100%;" onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
                <br><br>
                <b><a href="/mapset/<?php echo $result["SetID"]; ?>"><?php echo "{$result["Title"]} [{$result["DifficultyName"]}]";?></a></b><br>
                by <?php RenderBeatmapCreators($result['BeatmapID'], $conn); ?> <br>
                <span class="subText map-descriptors">
                    <?php
                    $descriptorLinks = array();
                    while($descriptor = $descriptorResult->fetch_assoc()){
                        $descriptorLink = '<a style="color:inherit;" href="../descriptor/?id=' . $descriptor["DescriptorID"] . '">' . $descriptor["Name"] . '</a>';
                        $descriptorLinks[] = $descriptorLink;
                    }

                    echo implode(', ', $descriptorLinks);
                    ?>
                </span>
                <br><br>
                Ranked <?php echo date("M jS, Y", strtotime($result['DateRanked'])); ?> <br>
                <b><?php echo number_format($result["WeightedAvg"], 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $result["RatingCount"]; ?></span> votes</span><br>
                <b>#<?php echo $result["ChartYearRank"]; ?></b> for <a href="/charts/?y=<?php echo $year;?>&p=<?php echo ceil($result["ChartYearRank"] / 50); ?>"><?php echo $year;?></a><br>
            </div>
        </div>
        <div class="flex-child column-when-mobile" style="width:50%;height:40em;">
            <?php
            $counter = 0;

            $stmt = $conn->prepare("SELECT b.BeatmapID, b.SetID, b.Title, b.DifficultyName, num_ratings
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
                <div class="flex-container ratingContainer alternating-bg" style="height:4em;">
                    <div class="flex-child" style="min-width:2em;text-align:center;">
                        #<?php echo $counter; ?>
                    </div>
                    <div class="flex-child">
                        <a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb" onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
                    </div>
                    <div class="flex-child" style="text-overflow: ellipsis;overflow:hidden;">
                        <a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo "{$row["Title"]} [{$row["DifficultyName"]}]";?></a>
                    </div>
                    <div class="flex-child" style="margin-left: auto;text-align:right;min-width:6em;">
                        <?php echo $row["num_ratings"];?> ratings
                    </div>
                </div>
                <?php
            }

            $stmt->close();
            ?>
        </div>
    </div>
<?php
require 'footer.php';
?>