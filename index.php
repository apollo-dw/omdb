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
		  $stmt = $conn->prepare("SELECT r.*, b.DifficultyName, b.SetID FROM `ratings` r INNER JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID INNER JOIN `users` u on r.UserID = u.UserID WHERE b.Mode = ? and u.HideRatings = 0 ORDER BY r.date DESC LIMIT 60;");
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
                $stmt = $conn->prepare("(SELECT c.*, r.UserIDTo AS blocked, 'beatmap' AS comment_type, NULL as Name, NULL as ProposalID
                                                FROM comments c
                                                LEFT JOIN user_relations r ON c.UserID = r.UserIDTo AND r.UserIDFrom = ? AND r.type = 2
                                                WHERE EXISTS (
                                                    SELECT 1
                                                    FROM beatmaps b
                                                    WHERE b.SetID = c.SetID AND b.Mode = ?
                                                )
                                            )
                                            UNION ALL
                                            (
                                                SELECT dpc.*, NULL AS blocked, 'descriptor_proposal' AS comment_type, p.Name, dpc.ProposalID
                                                FROM descriptor_proposal_comments dpc
                                                LEFT JOIN descriptor_proposals p ON p.ProposalID = dpc.ProposalID
                                                WHERE 1
                                            )
                                            ORDER BY date DESC
                                            LIMIT 20;");
                $stmt->bind_param("ii", $userId, $mode);
                $stmt->execute();
                $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $is_blocked = $row['blocked'] ? 1 : 0;
            $linkID = $row['comment_type'] === 'beatmap' ? "/mapset/{$row["SetID"]}" : "/descriptor/proposal/?id={$row["ProposalID"]}";
            ?>
            <div class="flex-container ratingContainer alternating-bg">
                <div class="flex-child" style="margin-left:0.5em;">
                    <?php if ($row["comment_type"] == 'beatmap') { ?>
                        <a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/charts/INF.png';"></a>
                    <?php } else { ?>
                        <div style="height: 32px;width: 32px;font-size: 16px;text-align:center;line-height: 32px;">
                            <i class="icon-pencil"></i>
                        </div>
                    <?php } ?>
                </div>
                <div class="flex-child">
                    <div>
                        <a href="/profile/<?php echo $row["UserID"]; ?>">
                            <img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/>
                            <span><?php echo GetUserNameFromId($row["UserID"], $conn); ?></span>
                        </a>
                        <span>
                            <?php if ($row["comment_type"] == 'descriptor_proposal') { ?>
                                on <a href="<?php echo $linkID; ?>"><?php echo $row["Name"]; ?> descriptor</a>
                            <?php } ?>
                        </span>
                    </div>
                    <div style="flex:0 0 auto;text-overflow:ellipsis;min-width:0%;">
                        <a style="color:white;" href="<?php echo $linkID; ?>">
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
        $usedSets = array();
		$stmt = $conn->prepare("SELECT SetID, Artist, Title, SetCreatorID, Timestamp, DateRanked FROM `beatmaps` WHERE `Mode`= ? ORDER BY `Timestamp` DESC LIMIT 200;");
        $stmt->bind_param("i", $mode);
        $stmt->execute();
		$result = $stmt->get_result();

		while($row = $result->fetch_assoc()) {
            if (in_array($row["SetID"], $usedSets))
                continue;
            if (sizeof($usedSets) >= 8)
                break;

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
            $usedSets[] = $row["SetID"];
		}

		$stmt->close();
	?>
</div>
<br>
    <div class="flex-container column-when-mobile-container">
        <div class="flex-child column-when-mobile" style="width:50%;height:40em;background-color: darkslategray;padding: 0.5em;box-sizing:border-box;">
            <h2 style="margin-top:0;">Highest charting map of the week</h2>
            <?php
                $stmt = $conn->prepare(
                    "SELECT * FROM beatmaps
                                       WHERE
                                            DateRanked >= DATE_SUB(NOW(), INTERVAL WEEKDAY(NOW()) + 7 DAY) 
                                            AND DateRanked < DATE_SUB(NOW(), INTERVAL WEEKDAY(NOW()) DAY)
                                            AND Rating IS NOT NULL
                                            AND Mode = ?
                                       ORDER BY
                                            ChartRank ASC
                                       LIMIT 1;");
                $stmt->bind_param("i", $mode);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows >= 1) {
                    $result = $result->fetch_assoc();
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
                } else {
                    $result = null;
                }

                $stmt->close();
            ?>
            <?php if ($result != null) { ?>
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
                <b>#<?php echo $result["ChartYearRank"]; ?></b> for <a href="/charts/?y=<?php echo $year;?>&p=<?php echo ceil($result["ChartYearRank"] / 50); ?>"><?php echo $year;?></a>, <b>#<?php echo $result["ChartRank"]; ?></b> <a href="/charts/?y=all-time&p=<?php echo ceil($result["ChartRank"] / 50); ?>">overall</a><br>
            </div>
            <?php } else { echo "no maps for this week :("; } ?>
        </div>
        <div class="flex-child column-when-mobile" style="width:50%;height:40em;">
            <?php
            $stmt = $conn->prepare("SELECT b.BeatmapID, b.SetID, b.Title, b.DifficultyName, COUNT(r.BeatmapID) as num_ratings
                                          FROM beatmaps b
                                          INNER JOIN ratings r ON b.BeatmapID = r.BeatmapID
                                          WHERE r.date >= NOW() - INTERVAL 1 WEEK
                                          AND b.Mode = ?
                                          GROUP BY b.BeatmapID
                                          ORDER BY num_ratings DESC
                                          LIMIT 25;");
            $stmt->bind_param("i", $mode);
            $stmt->execute();
            $result = $stmt->get_result();

            $usedSets = array();

            while($row = $result->fetch_assoc()) {
                if (in_array($row["SetID"], $usedSets))
                    continue;
                ?>
                <div class="flex-container ratingContainer alternating-bg" style="height:4em;">
                    <div class="flex-child" style="min-width:2em;text-align:center;">
                        #<?php echo sizeof($usedSets) + 1; ?>
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
                if (sizeof($usedSets) == 9)
                    break;

                $usedSets[] = $row["SetID"];
            }
            $stmt->close();
            ?>
        </div>
    </div>
<?php
require 'footer.php';
?>