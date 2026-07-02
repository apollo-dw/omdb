<?php
    $PageTitle = "Home";
	require "base.php";
    require 'header.php';
?>

<div style="display: flex;">
    <div>
        welcome to OMDB - a place to rate maps! discover new maps, check out people's ratings, AND STUFF. <br>
        <span style="color:grey;">
            <?php
            $motd = getMapOfTheDay($conn, $mode);
            
            $query = "SELECT 
                    COUNT(*) AS total_users,
                    SUM(CASE WHEN `LastAccessedSite` >= NOW() - INTERVAL 24 HOUR THEN 1 ELSE 0 END) AS online_users,
                    (SELECT COUNT(DISTINCT UserID) FROM ratings WHERE `date` >= NOW() - INTERVAL 24 HOUR) AS active_raters,
                    (SELECT COUNT(*) FROM `ratings`) AS total_ratings,
                    (SELECT COUNT(*) FROM ratings WHERE `date` >= NOW() - INTERVAL 24 HOUR) AS ratings_today,
                    (SELECT COUNT(*) FROM `comments`) AS total_comments,
                    (SELECT COUNT(*) FROM `comments` WHERE `date` >= NOW() - INTERVAL 24 HOUR) AS comments_today,
                    (SELECT COUNT(*) FROM `reviews`) AS total_reviews,
                    (SELECT COUNT(*) FROM `lists`) AS total_lists
                FROM `users`
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            $stmt->close();
            ?>

            <span title='<?php echo (int)$stats["online_users"]; ?> within the last day, <?php echo (int)$stats["active_raters"]; ?> rated maps within the last day' style='border-bottom:1px dotted white;'><?php echo (int)$stats["total_users"]; ?> users</span>,
            <span title='<?php echo (int)$stats["ratings_today"]; ?> within the last day' style='border-bottom:1px dotted white;'><?php echo (int)$stats["total_ratings"]; ?> ratings</span>,
            <span title='<?php echo (int)$stats["comments_today"]; ?> within the last day' style='border-bottom:1px dotted white;'><?php echo (int)$stats["total_comments"]; ?> comments</span>, <?php echo (int)$stats["total_reviews"]; ?> reviews, <?php echo (int)$stats["total_lists"]; ?> lists
        </span>
    </div>  
</div>
<hr>
<div class="flex-container column-when-mobile-container">
	<div class="flex-child column-when-mobile" style="width:40%;height:36em;overflow-y:scroll;position:relative;">
		<?php
		  if ($userId !== -1) {
				$stmt = $conn->prepare("
					SELECT r.*, b.DifficultyName, b.SetID, m.Username
					FROM `ratings` r 
					INNER JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID 
					INNER JOIN `users` u ON r.UserID = u.UserID 
                    LEFT JOIN mappernames m ON m.UserID = r.UserID
					WHERE b.Mode = ? 
					  AND b.blacklisted = 0 
					  AND u.HideRatings = 0
                      AND NOT EXISTS (
                        SELECT 1 
                        FROM user_relations ur 
                        WHERE r.UserID = ur.UserIDTo AND ur.UserIDFrom = ? AND ur.type = 2
                    )
					  AND (
						  (SELECT OnlyFriendsOnFrontPage FROM users WHERE UserID = ?) = 0
						  OR r.UserID IN (
							  SELECT UserIDTo 
							  FROM user_relations 
							  WHERE UserIDFrom = ? AND type = 1
						  )
						  OR r.UserID = ?
					  )
					ORDER BY r.date DESC 
					LIMIT 100
				");
				$stmt->bind_param("iiiii", $mode, $userId, $userId, $userId, $userId);
			} else {
				$stmt = $conn->prepare("
					SELECT r.*, b.DifficultyName, b.SetID, m.Username
					FROM `ratings` r 
					INNER JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID 
					INNER JOIN `users` u ON r.UserID = u.UserID 
                    LEFT JOIN mappernames m ON m.UserID = r.UserID
					WHERE b.Mode = ? 
					  AND b.blacklisted = 0 
					  AND u.HideRatings = 0
					ORDER BY r.date DESC 
					LIMIT 60
				");
				$stmt->bind_param("i", $mode);
			}
          $stmt->execute();
		  $result = $stmt->get_result();

		  while($row = $result->fetch_assoc()) {
		  ?>
			<div class="flex-container ratingContainer alternating-bg">
			    <div class="flex-child" style="margin-left:0.5em;">
				    <a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';"></a>
			    </div>
                <div class="flex-child">
                    <a style="display:flex;" href="/profile/<?php echo $row["UserID"]; ?>">
                        <img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo safe_htmlspecialchars($row["Username"] ?? GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>"/>
                    </a>
                </div>
                <div class="flex-child" style="flex:0 0 66%;">
                    <a style="display:flex;" href="/profile/<?php echo $row["UserID"]; ?>">
                        <?php echo safe_htmlspecialchars($row["Username"] ?? GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>
                    </a>
                    <?php
                        echo RenderUserRating($conn, $row) . " on " . "<a href='/mapset/" . $row["SetID"] . "'>" . safe_htmlspecialchars(mb_strimwidth($row["DifficultyName"], 0, 35, "..."), ENT_QUOTES) . "</a>";
                    ?>
					<span>
						<?php if ($motd['BeatmapID'] == $row["BeatmapID"]) { ?>
						<span class="tooltip-wrapper">
							<span class="badge" style="background-color: #c6c69f;" title="Map of the Day">MOTD</span>
							<span class="tooltip-box">
								Random map of the day
							</span>
						</span>
						<?php } ?>
					</span>
                </div>
			</div>
		  <?php
		  }
		  $stmt->close();
		?>
	</div>
    <div class="flex-child column-when-mobile" style="width:60%;height:36em;overflow-y:scroll;">
    <?php
        $onlyFriends = 0;
        if ($userId !== -1) {
            $stmt = $conn->prepare("SELECT OnlyFriendsOnFrontPage FROM users WHERE UserID=?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $onlyFriends = (int)$stmt->get_result()->fetch_row()[0];
            $stmt->close();
        }

        $stmt = $conn->prepare("
            (
                SELECT c.*, 'beatmap' AS comment_type, NULL as Name, NULL as ProposalID, bs.Artist, bs.Title, m.Username
                FROM comments c
                JOIN beatmapsets bs ON bs.SetID = c.SetID
                LEFT JOIN mappernames m ON m.UserID = c.UserID
                WHERE NOT EXISTS (
                    SELECT 1 
                    FROM user_relations r 
                    WHERE c.UserID = r.UserIDTo AND r.UserIDFrom = ? AND r.type = 2
                )
                AND EXISTS (
                    SELECT 1
                    FROM beatmaps b
                    WHERE b.SetID = c.SetID AND b.Mode = ?
                )
                AND (
                    ? = 0
                    OR c.UserID IN (
                        SELECT UserIDTo
                        FROM user_relations
                        WHERE UserIDFrom = ? AND type = 1
                    )
                    OR c.UserID = ?
                )
            )
            UNION ALL
            (
                SELECT dpc.*, 'descriptor_proposal' AS comment_type, p.Name, dpc.ProposalID, NULL as Artist, NULL as Title, m.Username
                FROM descriptor_proposal_comments dpc
                LEFT JOIN descriptor_proposals p ON p.ProposalID = dpc.ProposalID
                LEFT JOIN mappernames m ON m.UserID = dpc.UserID
                WHERE NOT EXISTS (
                    SELECT 1 
                    FROM user_relations r 
                    WHERE dpc.UserID = r.UserIDTo AND r.UserIDFrom = ? AND r.type = 2
                )
            )
            UNION ALL
            (
                SELECT nc.*, 'news' AS comment_type, np.Title as Name, nc.NewsID as ProposalID, NULL as Artist, NULL as Title, m.Username
                FROM news_comments nc
                JOIN news_posts np ON np.NewsID = nc.NewsID
                LEFT JOIN mappernames m ON m.UserID = nc.UserID
                WHERE NOT EXISTS (
                    SELECT 1 
                    FROM user_relations r 
                    WHERE nc.UserID = r.UserIDTo AND r.UserIDFrom = ? AND r.type = 2
                )
            )
            UNION ALL
            (
                SELECT r.*, 'review' AS comment_type, NULL as Name, NULL as ProposalID, bs.Artist, bs.Title, m.Username
                FROM reviews r
                JOIN beatmapsets bs ON bs.SetID = r.SetID
                LEFT JOIN mappernames m ON m.UserID = r.UserID
                WHERE NOT EXISTS (
                    SELECT 1 
                    FROM user_relations ur 
                    WHERE r.UserID = ur.UserIDTo AND ur.UserIDFrom = ? AND ur.type = 2
                )
                AND EXISTS (
                    SELECT 1
                    FROM beatmaps b
                    WHERE b.SetID = r.SetID AND b.Mode = ?
                )
                AND (
                    ? = 0
                    OR r.UserID IN (
                        SELECT UserIDTo
                        FROM user_relations
                        WHERE UserIDFrom = ? AND type = 1
                    )
                    OR r.UserID = ?
                )
            )
            ORDER BY date DESC
            LIMIT 40; ");

        $stmt->bind_param("iiiiiiiiiiii", $userId, $mode, $onlyFriends, $userId, $userId, $userId, $userId, $userId, $mode, $onlyFriends, $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if ($row['comment_type'] === 'beatmap' || $row['comment_type'] === 'review') {
                $linkID = "/mapset/{$row["SetID"]}";
            } elseif ($row['comment_type'] === 'news') {
                $linkID = "/news/post.php?id={$row["ProposalID"]}";
            } else {
                $linkID = "/descriptor/proposal/?id={$row["ProposalID"]}";
            }
        ?>
            <div class="flex-container ratingContainer alternating-bg">
                <div class="flex-child" style="margin-left:0.5em;">
                    <?php if ($row["comment_type"] == 'beatmap' || $row["comment_type"] == 'review') { ?>
                        <a href="/mapset/<?php echo $row["SetID"]; ?>">
                            <img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg"
                                class="diffThumb"
                                onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';"
                                loading="lazy"/>
                        </a>
                    <?php } elseif ($row["comment_type"] == 'news') { ?>
                        <div style="height: 32px;width: 32px;font-size: 16px;text-align:center;line-height: 32px;">
                            <i class="icon-list-alt"></i>
                        </div>
                    <?php } else { ?>
                        <div style="height: 32px;width: 32px;font-size: 16px;text-align:center;line-height: 32px;">
                            <i class="icon-pencil"></i>
                        </div>
                    <?php } ?>
                </div>

                <div class="flex-child">
                    <div>
                        <a href="/profile/<?php echo $row["UserID"]; ?>">
                            <img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>"
                                style="height:24px;width:24px;"
                                title="<?php echo safe_htmlspecialchars($row["Username"] ?? GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>"
                                loading="lazy"/>
                            <span><?php echo safe_htmlspecialchars($row["Username"] ?? GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?></span>
                        </a>

                        <span>
                            <?php if ($row["comment_type"] == 'descriptor_proposal') { ?>
                                on <a href="<?php echo $linkID; ?>"><?php echo safe_htmlspecialchars($row["Name"], ENT_QUOTES); ?> descriptor</a>
                            <?php } elseif ($row["comment_type"] == 'news') { ?>
                                on <a href="<?php echo $linkID; ?>"><?php echo safe_htmlspecialchars($row["Name"], ENT_QUOTES); ?></a>
                            <?php } elseif ($row["comment_type"] == 'review') { ?>
                                reviewed <a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo safe_htmlspecialchars($row["Artist"] . " - " . $row["Title"], ENT_QUOTES); ?></a>
                            <?php } ?>
                        </span>
                    </div>

                    <div style="flex:0 0 auto;text-overflow:ellipsis;min-width:0%;">
                        <a style="color:white;" href="<?php echo $linkID; ?>">
                            <?php
                                if ($row["comment_type"] == 'review') {
                                    echo ParseShortLinks($conn, nl2br(safe_htmlspecialchars(mb_strimwidth(implode("\n\n", array_slice(preg_split('/\R\s*\R/', $row["Comment"]), 0, 2)), 0, 460, '...'), ENT_QUOTES)), false);
                                } else {
                                    echo ParseShortLinks($conn, safe_htmlspecialchars(mb_strimwidth($row["Comment"], 0, 180, "..."), ENT_QUOTES), false);
                                }

                            ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
</div>
<br>
<div class="flex-container column-when-mobile-container" style="width:100%;background-color:DarkSlateGrey;justify-content:space-between;padding:0;align-items:stretch;">
    <div class="flex-child column-when-mobile" style="flex:1;min-width:14em;background-color:#0c1515;padding:0.75em;box-sizing:border-box;display:flex;flex-direction:column;justify-content:space-between;margin:0;">
        <div>
            <b>Latest News</b>
            <hr style="border-color:#2a4a4a;">
            <?php
                $newsStmt = $conn->prepare("SELECT
                        n.NewsID,
                        n.Title,
                        n.Content,
                        n.DateCreated,
                        u.Username,
                        (
                            SELECT COUNT(*)
                            FROM news_comments c
                            WHERE c.NewsID = n.NewsID
                        ) AS CommentsCount
                    FROM news_posts n
                    LEFT JOIN users u ON u.UserID = n.AuthorID
                    ORDER BY n.DateCreated DESC
                    LIMIT 1
                ");
                $newsStmt->execute();
                $newsPost = $newsStmt->get_result()->fetch_assoc();
                $newsStmt->close();
 
                if ($newsPost):
                    $newsDate = date("M j, Y H:i", strtotime($newsPost["DateCreated"]));
                    $previewText = mb_strimwidth($newsPost["Content"], 0, 200, "…");
            ?>
                <b><a href="news/post.php?id=<?php echo $newsPost["NewsID"]; ?>">
                    <?php echo safe_htmlspecialchars($newsPost["Title"], ENT_QUOTES); ?>
                </a></b>
                <br>
                <span class="subText">
                    <?php echo $newsDate; ?> - <?php echo safe_htmlspecialchars($newsPost["Username"], ENT_QUOTES); ?>
                </span>
                <br>
                <span class="subText">
                    <?php echo $newsPost["CommentsCount"]; ?> comment<?php echo $newsPost["CommentsCount"] == 1 ? "" : "s"; ?>
                </span>
                <br><br>
                <div style="font-size:0.85em;word-break:break-word;">
                    <?php echo nl2br(safe_htmlspecialchars($previewText, ENT_QUOTES)); ?>
                </div>
            <?php else: ?>
                <span class="subText">No news yet</span>
            <?php endif; ?>
        </div>
        <div style="text-align:right;">
            <a href="/news/" style="font-size:0.85em;">View all news →</a>
        </div>
    </div>
    <div class="flex-container" style="flex:4;background-color:DarkSlateGrey;justify-content: space-around;padding:0px;">
        <br>
        <?php
            $usedSets = array();
            $stmt = $conn->prepare("SELECT *, m.Username FROM cache_home_recent_maps c LEFT JOIN mappernames m ON m.UserID = c.CreatorID WHERE Mode = ? ORDER BY Timestamp DESC;");
            $stmt->bind_param("i", $mode);
            $stmt->execute();
            $result = $stmt->get_result();

            while($row = $result->fetch_assoc()) {
                if (in_array($row["SetID"], $usedSets))
                    continue;
                if (sizeof($usedSets) >= 8)
                    break;

                $artist = $row["Username"] ?? GetUserNameFromId($row["CreatorID"], $conn);
        ?>
        <div class="flex-child" style="text-align:center;flex:1;overflow:hidden;padding:0.5em;display: inline-block;margin-left:auto;margin-right:auto;">
            <a href="/mapset/<?php echo $row["SetID"]; ?>">
                <img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" 
                class="diffThumb" 
                style="aspect-ratio: 1 / 1;width:90%;height:auto;" 
                onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';"
                loading="lazy" />
            </a><br>
            <span class="subText">
                <a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo safe_htmlspecialchars($row["Metadata"], ENT_QUOTES); ?></a><br>
                by <a href="/profile/<?php echo $row["CreatorID"]; ?>"><?php echo safe_htmlspecialchars($artist, ENT_QUOTES); ?></a> <br>
                <?php echo GetHumanTime($row["Timestamp"]); ?>
            </span>
        </div>
        <?php
                $usedSets[] = $row["SetID"];
            }

            $stmt->close();
        ?>
    </div>
</div>
<br>
<div class="flex-container column-when-mobile-container">
    <div class="flex-child column-when-mobile" style="width:33%;height:40em;background-color: darkslategray;padding: 0.5em;box-sizing:border-box;">
        <h2 style="margin-top:0;">Random Map of the Day</h2>
        <?php
            if ($motd != null) {
                $stmt = $conn->prepare("
                    SELECT 
                        bd.DescriptorID,
                        d.Name,
                        d.ShortDescription
                    FROM beatmap_descriptors bd
                    JOIN descriptors d ON bd.DescriptorID = d.DescriptorID
                    WHERE bd.BeatmapID = ?
                    ORDER BY bd.Weight DESC, bd.DescriptorID
                    LIMIT 5
                ");
                $stmt->bind_param("i", $motd["BeatmapID"]);
                $stmt->execute();
                $motdDescriptorResult = $stmt->get_result();
                $stmt->close();
            }
        ?>
        <?php if ($motd != null) { 
            $motdYear = date("Y", strtotime($motd['DateRanked']));
        ?>
        <div style="width:100%;text-align:center;">
            <a href="/mapset/<?php echo $motd["SetID"]; ?>">
                <img src="https://assets.ppy.sh/beatmaps/<?php echo $motd["SetID"]; ?>/covers/cover.jpg" 
                style="width:100%;" 
                onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';"
                loading="lazy" />
            </a>
            <br><br>
            <b><a href="/mapset/<?php echo $motd["SetID"]; ?>"><?php echo safe_htmlspecialchars("{$motd["Title"]} [{$motd["DifficultyName"]}]", ENT_QUOTES);?></a></b><br>
            by <?php RenderBeatmapCreators($motd['BeatmapID'], $conn); ?> <br>
            <span class="subText map-descriptors">
                <?php
                  $motdDescriptorLinks = array();

                  while ($descriptor = $motdDescriptorResult->fetch_assoc()) {
                    $name = safe_htmlspecialchars($descriptor["Name"]);
                    $id = (int)$descriptor["DescriptorID"];
                    $shortDescription = ParseShortLinks($conn, safe_htmlspecialchars($descriptor["ShortDescription"]), false);

                    $descriptorLink = '
                      <span class="tooltip-wrapper">
                        <a style="color:inherit;" href="../descriptor/?id=' . $id . '">' . $name . '</a>
                        <span class="tooltip-box">
                          ' . $shortDescription . '
                        </span>
                      </span>';

                    $motdDescriptorLinks[] = $descriptorLink;
                  }

                  echo implode(', ', $motdDescriptorLinks);
                ?>
            </span>
            <br><br>
            Ranked <?php echo date("M jS, Y", strtotime($motd['DateRanked'])); ?> <br>
            <?php if ($motd["RatingCount"] > 0) { ?>
                <b><?php echo number_format((float)$motd["WeightedAvg"], 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $motd["RatingCount"]; ?></span> votes</span><br>
                <?php if ($motd["ChartRank"] != null) { ?>
                    <b>#<?php echo $motd["ChartYearRank"]; ?></b> for <a href="/charts/?y=<?php echo $motdYear;?>&p=<?php echo ceil($motd["ChartYearRank"] / 50); ?>"><?php echo $motdYear;?></a>, <b>#<?php echo $motd["ChartRank"]; ?></b> <a href="/charts/?y=all-time&p=<?php echo ceil($motd["ChartRank"] / 50); ?>">overall</a><br>
                <?php } ?>
            <?php } else { ?>
                <span class="subText" style="color:white;">No ratings yet! Be the first!</span><br>
            <?php } ?>
            <br>
            <span class="subText">Resets in <span id="updateText"></span></span>
        </div>
        <?php } else { echo "No maps found?! :("; } ?>
    </div>

    <div class="flex-child column-when-mobile" style="width:33%;height:40em;background-color: darkslategray;padding: 0.5em;box-sizing:border-box;">
        <h2 style="margin-top:0;">Highest charting map of the week</h2>
        <?php
            $stmt = $conn->prepare(
                "SELECT b.BeatmapID, b.SetID, s.DateRanked, b.DifficultyName, b.WeightedAvg, b.RatingCount, b.ChartRank, b.ChartYearRank, s.Title
                        FROM beatmaps b
                        JOIN cache_home_best_map c ON b.BeatmapID = c.BeatmapID
                        JOIN beatmapsets s on b.SetID = s.SetID
                        WHERE
                            b.Rating IS NOT NULL
                            AND b.Mode = ?
                        LIMIT 1;");
            $stmt->bind_param("i", $mode);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows >= 1) {
                $result = $result->fetch_assoc();
                $year = date("Y", strtotime($result['DateRanked']));
                
                $stmt = $conn->prepare("
                    SELECT 
                        bd.DescriptorID,
                        d.Name,
						            d.ShortDescription
                    FROM beatmap_descriptors bd
                    JOIN descriptors d ON bd.DescriptorID = d.DescriptorID
                    WHERE bd.BeatmapID = ?
                    ORDER BY bd.Weight DESC, bd.DescriptorID
                    LIMIT 5
                ");
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
            <a href="/mapset/<?php echo $result["SetID"]; ?>">
                <img src="https://assets.ppy.sh/beatmaps/<?php echo $result["SetID"]; ?>/covers/cover.jpg" 
                style="width:100%;" 
                onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';"
                loading="lazy" />
            </a>
            <br><br>
            <b><a href="/mapset/<?php echo $result["SetID"]; ?>"><?php echo safe_htmlspecialchars("{$result["Title"]} [{$result["DifficultyName"]}]", ENT_QUOTES);?></a></b><br>
            by <?php RenderBeatmapCreators($result['BeatmapID'], $conn); ?> <br>
            <span class="subText map-descriptors">
                <?php
                  $descriptorLinks = array();

                  while ($descriptor = $descriptorResult->fetch_assoc()) {
                    $name = safe_htmlspecialchars($descriptor["Name"]);
                    $id = (int)$descriptor["DescriptorID"];
                    $shortDescription = ParseShortLinks($conn, safe_htmlspecialchars($descriptor["ShortDescription"]), false);

                    $descriptorLink = '
                      <span class="tooltip-wrapper">
                        <a style="color:inherit;" href="../descriptor/?id=' . $id . '">' . $name . '</a>
                        <span class="tooltip-box">
                          ' . $shortDescription . '
                        </span>
                      </span>';

                    $descriptorLinks[] = $descriptorLink;
                  }

                  echo implode(', ', $descriptorLinks);
                ?>
            </span>
            <br><br>
            Ranked <?php echo date("M jS, Y", strtotime($result['DateRanked'])); ?> <br>
            <b><?php echo number_format((float)$result["WeightedAvg"], 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $result["RatingCount"]; ?></span> votes</span><br>
            <b>#<?php echo $result["ChartYearRank"]; ?></b> for <a href="/charts/?y=<?php echo $year;?>&p=<?php echo ceil($result["ChartYearRank"] / 50); ?>"><?php echo $year;?></a>, <b>#<?php echo $result["ChartRank"]; ?></b> <a href="/charts/?y=all-time&p=<?php echo ceil($result["ChartRank"] / 50); ?>">overall</a><br>
        </div>
        <?php } else { echo "no maps for this week :("; } ?>
    </div>
    <div class="flex-child column-when-mobile" style="width:34%;height:40em;">
        <?php
        $stmt = $conn->prepare("SELECT b.BeatmapID, b.SetID, s.Title, b.DifficultyName, COUNT(r.BeatmapID) as num_ratings
                                        FROM beatmaps b
                                        JOIN beatmapsets s on b.SetID = s.SetID
                                        INNER JOIN ratings r ON b.BeatmapID = r.BeatmapID
                                        WHERE r.date >= NOW() - INTERVAL 1 WEEK
                                        AND b.Mode = ? AND b.blacklisted = 0
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
                    <a href="/mapset/<?php echo $row["SetID"]; ?>">
                        <img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" 
                        class="diffThumb" 
                        onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';"
                        loading="lazy" />
                    </a>
                </div>
                <div class="flex-child" style="text-overflow: ellipsis;overflow:hidden;">
                    <a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo safe_htmlspecialchars("{$row["Title"]} [{$row["DifficultyName"]}]", ENT_QUOTES);?></a>
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

<script>
    function displayTimeRemaining() {
        const now = new Date();
        let nextReset = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate(), 6, 0, 0, 0));

        if (now.getTime() >= nextReset.getTime()) {
            nextReset.setUTCDate(nextReset.getUTCDate() + 1);
        }

        const timeRemaining = nextReset.getTime() - now.getTime();

        const hoursRemaining = Math.floor((timeRemaining / (1000 * 60 * 60)) % 24);
        const minutesRemaining = Math.floor((timeRemaining / (1000 * 60)) % 60);
        const secondsRemaining = Math.floor((timeRemaining / 1000) % 60);

        const hoursText = hoursRemaining === 1 ? 'hour' : 'hours';
        const minutesText = minutesRemaining === 1 ? 'minute' : 'minutes';
        const secondsText = secondsRemaining === 1 ? 'second' : 'seconds';

        const textElement = document.getElementById('updateText');
        if (textElement) {
            textElement.textContent = `${hoursRemaining} ${hoursText}, ${minutesRemaining} ${minutesText}, ${secondsRemaining} ${secondsText}`;
        }
    }

    displayTimeRemaining();
    setInterval(displayTimeRemaining, 1000);
</script>
<?php
require 'footer.php';
?>