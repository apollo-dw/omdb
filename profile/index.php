<?php
    require "../base.php";

    $profileId = GetIntParam('id', -1, "Invalid page bro");

    $order = $_GET['o'] ?? '1';
    $year = ($_GET['y'] ?? 'all-time') === 'all-time' ? 'all-time' : (int)$_GET['y'];
    $rating = $_GET['r'] ?? '';

    $tokensRaw = json_decode(urldecode($_GET['tokens'] ?? '[]'), true);
    if (!is_array($tokensRaw)) $tokensRaw = [];

    $parsedTokens = parseFilterTokens($tokensRaw);
    $filter = buildBeatmapFilterSQL($parsedTokens);

    $filterConditions = "";
    $filterTypes = "";
    $filterValues = [];

    if ($year !== 'all-time') {
        $filterConditions .= " AND YEAR(s.DateRanked) = ?";
        $filterTypes .= "i";
        $filterValues[] = (int)$year;
    }

    if ($rating !== '' && $loggedIn) {
        $filterConditions .= " AND EXISTS (
            SELECT 1 FROM ratings r_filter
            WHERE r_filter.BeatmapID = b.BeatmapID
            AND r_filter.UserID = ?
            AND r_filter.Score = ?
        )";
        $filterTypes .= "id";
        $filterValues[] = $userId;
        $filterValues[] = (float)$rating;
    }

    $filterConditions .= $filter['sql'];
    $filterTypes .= $filter['types'];
    $filterValues = array_merge($filterValues, $filter['values']);

    switch ($order) {
        case '2':
            $orderSQL = "s.Timestamp ASC";
            break;
        case '3':
            $orderSQL = "MAX(b.Rating) DESC, MAX(b.WeightedAvg) DESC";
            break;
        case '4':
            $orderSQL = "MAX(b.Rating) ASC, MAX(b.WeightedAvg) ASC";
            break;
        default:
            $orderSQL = "s.Timestamp DESC";
    }

    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `UserID` = ?");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();
    $isValidUser = $profile !== NULL;

    $PageTitle = $isValidUser ? GetUserNameFromId($profileId, $conn) : "Profile";
    require '../header.php';

    $ratingCounts = [];
    $isBlacklisted = false;
    if ($isValidUser) {
        $stmt = $conn->prepare("SELECT r.`Score`, COUNT(*) AS count
                        FROM `ratings` r
                        JOIN `beatmaps` b ON r.`BeatmapID` = b.`BeatmapID`
                        WHERE r.`UserID` = ? AND b.`Mode` = ?
                        GROUP BY r.`Score`");
        $stmt->bind_param("ii", $profileId, $mode);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $ratingCounts[$row['Score']] = $row['count'];
        }
        $stmt->close();

        $maxRating = count($ratingCounts) >= 1 ? max($ratingCounts) : 2;

        $stmt = $conn->prepare("SELECT u.UserID as ID, u.Username as username FROM users u
            JOIN user_relations ur1 ON u.UserID = ur1.UserIDTo
            JOIN user_relations ur2 ON u.UserID = ur2.UserIDFrom
            WHERE ur1.UserIDFrom = ? AND ur2.UserIDTo = ?
            AND ur1.type = 1 AND ur2.type = 1
            ORDER BY LastAccessedSite DESC, ID");
        $stmt->bind_param("ii", $profileId, $profileId);
        $stmt->execute();
        $mutuals = $stmt->get_result();
        $mutualCount = $mutuals->num_rows;
        $stmt->close();

        $stmt = $conn->prepare("SELECT 1 FROM blacklist WHERE UserID = ?");
        $stmt->bind_param("i", $profileId);
        $stmt->execute();
        $isBlacklisted = $stmt->get_result()->num_rows > 0;
        $stmt->close();
    }

    $is_friend = $is_blocked = $is_friended = 0;
    if ($loggedIn) {
        $stmt_relation_to_profile_user = $conn->prepare("SELECT * FROM user_relations WHERE UserIDFrom = ? AND UserIDTo = ?");
        $stmt_relation_to_profile_user->bind_param("ii", $userId, $profileId);
        $stmt_relation_to_profile_user->execute();
        $result = $stmt_relation_to_profile_user->get_result();
        $resultRow = $result->fetch_assoc();

        $is_friend = $result->num_rows > 0 && $resultRow["type"] == 1;
        $is_blocked = $result->num_rows > 0 && $resultRow["type"] == 2;

        $stmt_relation_from_profile_user = $conn->prepare("SELECT * FROM user_relations WHERE UserIDFrom = ? AND UserIDTo = ?");
        $stmt_relation_from_profile_user->bind_param("ii", $profileId, $userId);
        $stmt_relation_from_profile_user->execute();
        $result2 = $stmt_relation_from_profile_user->get_result();
        $result2Row = $result2->fetch_assoc();

        $is_friended = $result2->num_rows > 0 && $result2Row["type"] == 1;

        $stmt_relation_to_profile_user->close();
        $stmt_relation_from_profile_user->close();

        if ($profileId != $userId) {
            $stmt = $conn->prepare("SELECT r1.`Score`, r2.`Score`
                        FROM `ratings` r1
                        JOIN `ratings` r2 ON r1.`BeatmapID` = r2.`BeatmapID`
                        JOIN `beatmaps` b ON r1.`BeatmapID` = b.`BeatmapID`
                        WHERE r1.`UserID` = ? AND r2.`UserID` = ? AND b.`Mode` = ?");
            $stmt->bind_param("iii", $userId, $profileId, $mode);
            $stmt->execute();
            $resultSet = $stmt->get_result();

            // Fetch and store the scores in arrays
            $rows = $resultSet->fetch_all(MYSQLI_NUM);

            $userScores = array_column($rows, 0);
            $profileScores = array_column($rows, 1);

            $stmt->close();

            $correlation = CalculatePearsonCorrelation($userScores, $profileScores);
        }
    }
?>

<div class="profileContainer column-when-mobile-container">
	<div class="profileCard">
		<div class="profileTitle">
            <a href="https://osu.ppy.sh/u/<?php echo $profileId; ?>" target="_blank" rel="noopener noreferrer"><?php echo safe_htmlspecialchars(GetUserNameFromId($profileId, $conn), ENT_QUOTES); ?></a> <a href="https://osu.ppy.sh/u/<?php echo $profileId; ?>" target="_blank" rel="noopener noreferrer"><i class="icon-external-link" style="font-size:10px;"></i></a>
		</div>
		<div class="profileImage">
			<img src="https://s.ppy.sh/a/<?php echo $profileId; ?>" style="width:146px;height:146px;"/>
		</div>
		
		<?php if ($isValidUser && !IS_NULL($profile['UserTitle'])) { ?>
		<div class="profileUserTitle">
			<span class="subText" style="font-weight:bolder;"><?php echo $profile['UserTitle']; ?></span>
		</div>
		<?php } ?>
		
		<?php if ($profileId != $userId && $isValidUser && $loggedIn){ ?>
        <div class="profileActions">
            <?php
				if (!$is_blocked) {
					if ($is_friend && $is_friended) {
						echo '<button id="friendButton" class="mutual">Mutual</button> ';
					} elseif ($is_friend && !$is_friended) {
						echo '<button id="friendButton">Friend</button> ';
					} else {
						echo '<button id="friendButton">Add Friend</button> ';
					}
				}

				if ($is_blocked) {
					echo '<button id="blockButton" class="blocked">Unblock</button>';
				} else {
					echo '<button id="blockButton">Block</button>';
				}
            ?>
        </div>
		<?php } ?>
		
        <?php
            $stmt = $conn->prepare("
                SELECT
                    (SELECT COUNT(*)
                    FROM user_relations
                    WHERE UserIDTo = ?
                    AND type = '1') AS friendCount,

                    (SELECT COUNT(*)
                    FROM ratings
                    WHERE UserID = ?) AS ratingCount,

                    (SELECT COUNT(*)
                    FROM comments
                    WHERE UserID = ?) AS commentCount,

                    (SELECT COUNT(*)
                    FROM reviews
                    WHERE UserID = ?) AS reviewCount,

                    (SELECT COUNT(*)
                    FROM beatmapsets s
                    WHERE CreatorID = ?
                    AND EXISTS (
                        SELECT 1
                        FROM beatmaps bm
                        WHERE bm.SetID = s.SetID
                        AND bm.Status IN (1, 2)
                    )) AS mapsetCount,

                    (SELECT COUNT(*)
                    FROM beatmap_edit_requests
                    WHERE UserID = ?
                    AND Status = 'Approved') AS approvedEditCount,

                    (SELECT COUNT(*)
                    FROM descriptor_votes
                    WHERE UserID = ?) AS descriptorVoteCount
            ");

            $stmt->bind_param(
                "iiiiiii",
                $profileId,
                $profileId,
                $profileId,
                $profileId,
                $profileId,
                $profileId,
                $profileId
            );

            $stmt->execute();

            $stats = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            $friendCount = $stats["friendCount"];
            $ratingCount = $stats["ratingCount"];
            $commentCount = $stats["commentCount"];
            $reviewCount = $stats["reviewCount"];
            $mapsetCount = $stats["mapsetCount"];
            $approvedEditCount = $stats["approvedEditCount"];
            $descriptorVoteCount = $stats["descriptorVoteCount"];

            $hasRatedMaps = false;
            if (!$isBlacklisted) {
                $stmt = $conn->prepare("SELECT
                        AVG(b.SR) AS AvgSR,
                        COUNT(b.BeatmapID) AS RatedMapCount,
                        COALESCE(SUM(b.RatingCount), 0) AS TotalRatings
                    FROM beatmap_creators bc
                    JOIN beatmaps b ON bc.BeatmapID = b.BeatmapID
                    WHERE bc.CreatorID = ? AND b.Mode = ? AND b.Rating IS NOT NULL
                ");
                $stmt->bind_param("ii", $profileId, $mode);
                $stmt->execute();
                $mapStats = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                $stmt = $conn->prepare("SELECT
                        YEAR(COALESCE(s.DateRanked, s.Timestamp)) as ActiveYear,
                        COUNT(*) as YearCount
                    FROM beatmap_creators bc
                    JOIN beatmaps b ON bc.BeatmapID = b.BeatmapID
                    JOIN beatmapsets s ON b.SetID = s.SetID
                    WHERE bc.CreatorID = ? AND b.Mode = ?
                    GROUP BY ActiveYear
                    ORDER BY YearCount DESC
                    LIMIT 1
                ");
                $stmt->bind_param("ii", $profileId, $mode);
                $stmt->execute();
                $activeYearResult = $stmt->get_result()->fetch_assoc();
                $activeYear = $activeYearResult ? $activeYearResult['ActiveYear'] : null;
                $stmt->close();

                $hasRatedMaps = $mapStats['RatedMapCount'] > 0;
                if ($hasRatedMaps) {
                    $stmt = $conn->prepare("SELECT b.BeatmapID, s.SetID, s.Artist, s.Title, b.DifficultyName, b.WeightedAvg, b.`RatingCount`, s.DateRanked, b.ChartRank, b.ChartYearRank
                        FROM beatmap_creators bc
                        JOIN beatmaps b ON bc.BeatmapID = b.BeatmapID
                        JOIN beatmapsets s ON b.SetID = s.SetID
                        WHERE bc.CreatorID = ? AND b.Mode = ? AND b.Rating IS NOT NULL
                        AND (SELECT COUNT(*) FROM beatmap_creators WHERE BeatmapID = b.BeatmapID) <= 3
                        ORDER BY b.Rating DESC, b.BeatmapID DESC
                        LIMIT 1
                    ");

                    $stmt->bind_param("ii", $profileId, $mode);
                    $stmt->execute();
                    $highestMap = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    $highestMapDescriptors = [];
                    if ($highestMap) {
                        $stmt = $conn->prepare("SELECT bd.DescriptorID, d.Name, d.ShortDescription
                            FROM beatmap_descriptors bd
                            JOIN descriptors d ON bd.DescriptorID = d.DescriptorID
                            WHERE bd.BeatmapID = ?
                            ORDER BY bd.Weight DESC, bd.DescriptorID
                            LIMIT 5
                        ");
                        $stmt->bind_param("i", $highestMap["BeatmapID"]);
                        $stmt->execute();
                        $highestMapDescResult = $stmt->get_result();
                        while ($descriptor = $highestMapDescResult->fetch_assoc()) {
                            $highestMapDescriptors[] = $descriptor;
                        }
                        $stmt->close();
                    }
                }
            }
        ?>

        <div class="profileStats">
            <a href="friends/?id=<?php echo $profileId; ?>">
                <b>Friends:</b> <?php echo $friendCount; ?>
            </a><br>

            <a href="ratings/?id=<?php echo $profileId; ?>&p=1">
                <b>Ratings:</b> <?php echo $ratingCount; ?>
            </a><br>

            <a href="comments/?id=<?php echo $profileId; ?>">
                <b>Comments:</b> <?php echo $commentCount; ?>
            </a><br>

            <a href="reviews/?id=<?php echo $profileId; ?>">
                <b>Reviews:</b> <?php echo $reviewCount; ?>
            </a><br>

            <b>Ranked Mapsets:</b> <?php echo $mapsetCount; ?><br>

            <b>Approved Edits:</b> <?php echo $approvedEditCount; ?><br>

            <b>Descriptor votes:</b> <?php echo $descriptorVoteCount; ?><br>
            <hr style="margin: 0.5em 0; border: none; border-top: 1px solid rgba(255,255,255,0.1);">
        </div>

		<?php if ($isValidUser){ ?>
			<div class="profileRankingDistribution" style="margin-bottom:0.5em;">
                <div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["5.0"] ?? 0)/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=5&p=1">5.0 <?php if ($profile["Custom50Rating"] != "") { echo " - " . safe_htmlspecialchars($profile["Custom50Rating"], ENT_QUOTES); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["4.5"] ?? 0)/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=4.5&p=1">4.5 <?php if ($profile["Custom45Rating"] != "") { echo " - " . safe_htmlspecialchars($profile["Custom45Rating"], ENT_QUOTES); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["4.0"] ?? 0)/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=4&p=1">4.0 <?php if ($profile["Custom40Rating"] != "") { echo " - " . safe_htmlspecialchars($profile["Custom40Rating"], ENT_QUOTES); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["3.5"] ?? 0)/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=3.5&p=1">3.5 <?php if ($profile["Custom35Rating"] != "") { echo " - " . safe_htmlspecialchars($profile["Custom35Rating"], ENT_QUOTES); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["3.0"] ?? 0)/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=3&p=1">3.0 <?php if ($profile["Custom30Rating"] != "") { echo " - " . safe_htmlspecialchars($profile["Custom30Rating"], ENT_QUOTES); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["2.5"] ?? 0)/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=2.5&p=1">2.5 <?php if ($profile["Custom25Rating"] != "") { echo " - " . safe_htmlspecialchars($profile["Custom25Rating"], ENT_QUOTES); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["2.0"] ?? 0)/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=2&p=1">2.0 <?php if ($profile["Custom20Rating"] != "") { echo " - " . safe_htmlspecialchars($profile["Custom20Rating"], ENT_QUOTES); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["1.5"] ?? 0)/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=1.5&p=1">1.5 <?php if ($profile["Custom15Rating"] != "") { echo " - " . safe_htmlspecialchars($profile["Custom15Rating"], ENT_QUOTES); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["1.0"] ?? 0)/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=1&p=1">1.0 <?php if ($profile["Custom10Rating"] != "") { echo " - " . safe_htmlspecialchars($profile["Custom10Rating"], ENT_QUOTES); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["0.5"] ?? 0)/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=0.5&p=1">0.5 <?php if ($profile["Custom05Rating"] != "") { echo " - " . safe_htmlspecialchars($profile["Custom05Rating"], ENT_QUOTES); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["0.0"] ?? 0)/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=0&p=1">0.0 <?php if ($profile["Custom00Rating"] != "") { echo " - " . safe_htmlspecialchars($profile["Custom00Rating"], ENT_QUOTES); } ?></a></div>
			</div>
			<div style="margin-bottom:1.5em;">
				Rating Distribution<br>
			</div>
        <?php
				if ($loggedIn && $profileId != $userId && $correlation !== null) {
                    $widthPercentage = abs(($correlation / 2) * 100);
                    $leftMargin = 0;

                    if($correlation < 0)
                        $leftMargin = 50 - $widthPercentage;
                    if($correlation > 0)
                        $leftMargin = 50;
			?>
				<div class="profileRankingDistribution" style="margin-bottom:0.5em;height:1.5em;">
					<div class="profileRankingDistributionBar" style="width: <?php echo $widthPercentage;?>%;height:1.5em;position:relative;margin-left:<?php echo $leftMargin;?>%;padding:0px;box-sizing: border-box;"></div>
				    <span class="verticalLine"></span>
                </div>
				<div style="margin-bottom:1em;">
                    <div style="margin-bottom:0.5em;"><span class="subText"><?php echo round($correlation, 3); ?></span></div>
					Rating Similarity To You<br>
				</div>
			<?php } elseif ($profileId == $userId) {
                    ?>
                    <a href="compatible/?id=<?php echo $profileId; ?>">View users similar to you!</a>
                    <?php
                }
		    } ?>
	</div>
	<div class="ratingsCard">
		<div id="ratingDisplay">
			<?php
				include 'rating.php';
			?>
		</div>
	</div>
</div>

<?php
    if($isValidUser) {
		$desc = trim($profile["CustomDescription"] ?? "");
		
		if (!empty($desc)) {
?>
			<hr>
			<h2>About me</h2>
			<div style="background-color:#203838;padding:2em;box-sizing:border-box;max-height:30em;overflow-y:scroll;">
				<?php
					echo ParseCommentLinks($conn, $desc);
				?>
			</div>

			<?php
			 if ($profileId == $userId){
				 echo "<br><a href='../settings'><div style='float:right;'>edit your description</div></a>";
			 }
			 echo "<br />";
		}
    }
?>

<?php
    if($isValidUser && $mutualCount > 0) {
?>
        <hr>
        <h2>Mutuals</h2>
        <div class="flex-container" style="background-color:#203838;padding:0px;">
            <br>
            <?php
                $counter = 0;
                $max = 10;

                while($row = $mutuals->fetch_assoc() and ($counter < $max)) {
                    ?>
                    <div class="flex-child" style="text-align:center;width:11%;padding:0.5em;flex-direction:column;">
                        <div class="profileImage">
                            <a href="/profile/<?php echo $row["ID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["ID"]; ?>" style="width:5em;height:5em;" loading="lazy" /></a><br>
                            <a href="/profile/<?php echo $row["ID"]; ?>"><?php echo safe_htmlspecialchars($row["username"], ENT_QUOTES); ?></a>
                        </div>
                    </div>
            <?php
                    $counter++;
                }
            ?>
        </div>

        <?php
         if ($mutualCount > 10){
             echo "<br><a href='friends/?id={$profileId}'><div style='float:right;'>...view more!</div></a>";
         }
         echo "<br />";
    }
?>

<?php
    if($hasRatedMaps) {
?>
    <hr>
    <h2>Mapping Overview</h2>
    <div class="flex-container column-when-mobile-container" style="justify-content:space-around; align-items:stretch; gap:67px;">
        <div class="flex-container" style="background-color:#203838; flex:1; text-align:center; box-sizing:border-box; flex-direction:column; justify-content:center; padding:0.25em;">
            <h3 style="margin:0;">Highest Rated</h3>
            <span class="subText">Excl. collabs with 4+ mappers</span>
            <?php if ($highestMap) { 
                $highestMapYear = date("Y", strtotime($highestMap['DateRanked']));
            ?>
                <a href="/mapset/<?php echo $highestMap["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $highestMap["SetID"]; ?>l.jpg" class="diffThumb" style="aspect-ratio: 1 / 1; width:90%; max-width:140px; height:auto; margin:0.5em;" onerror="this.onerror=null; this.src='../assets/img/missing-map-thumbnail.png';"></a>
                <b><a href="/mapset/<?php echo $highestMap["SetID"]; ?>"><?php echo safe_htmlspecialchars(mb_strimwidth("{$highestMap["Artist"]} - {$highestMap["Title"]} [{$highestMap["DifficultyName"]}]", 0, 75, "..."), ENT_QUOTES); ?></a></b>
                
                <span class="subText map-descriptors">
                    <?php
                    $highestMapDescLinks = array();
                    foreach ($highestMapDescriptors as $descriptor) {
                        $name = safe_htmlspecialchars($descriptor["Name"]);
                        $id = (int)$descriptor["DescriptorID"];
                        $shortDescription = safe_htmlspecialchars($descriptor["ShortDescription"]);

                        $highestMapDescLinks[] = '
                          <span class="tooltip-wrapper">
                            <a style="color:inherit;" href="../descriptor/?id=' . $id . '">' . $name . '</a>
                            <span class="tooltip-box">
                              ' . $shortDescription . '
                            </span>
                          </span>';
                    }
                    echo implode(', ', $highestMapDescLinks);
                    ?>
                </span>
                <br>
                <div>
                    Ranked <?php echo date("M jS, Y", strtotime($highestMap['DateRanked'])); ?>
                    <br>
                    <b><?php echo number_format((float)$highestMap['WeightedAvg'], 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $highestMap["RatingCount"]; ?></span> votes</span>
                    <br>
                    <?php if ($highestMap["ChartRank"] != null) { ?>
                        <b>#<?php echo $highestMap["ChartYearRank"]; ?></b> for <a href="/charts/?y=<?php echo $highestMapYear;?>&p=<?php echo ceil($highestMap["ChartYearRank"] / 50); ?>"><?php echo $highestMapYear;?></a>, <b>#<?php echo $highestMap["ChartRank"]; ?></b> <a href="/charts/?y=all-time&p=<?php echo ceil($highestMap["ChartRank"] / 50); ?>">overall</a>
                    <?php } ?>
                </div>
            <?php } else { echo "<span class='subText'>N/A</span>"; } ?>
        </div>

        <div style="background-color:#203838; flex:1; text-align:center; display:flex; flex-direction:column; justify-content:center; box-sizing:border-box; padding:0.25em;">
            <div>
                <b>Total Ratings Received:</b> <?php echo $mapStats['TotalRatings']; ?><br>
                <b>Average Star Rating:</b> <?php echo number_format((float)$mapStats['AvgSR'], 2); ?>*<br>
                <?php if ($activeYear) { ?>
                    <b>Most Active Year:</b> <?php echo $activeYear; ?>
                <?php } ?>
            </div>

            <br>

            <b>Top Descriptors</b>
            <span class="subText">
                <?php
                    $descStmt = $conn->prepare("SELECT
                            bd.DescriptorID,
                            d.Name,
                            d.ShortDescription,
                            SUM(bd.Weight) as TotalWeight
                        FROM beatmap_creators bc
                        JOIN beatmap_descriptors bd ON bc.BeatmapID = bd.BeatmapID
                        JOIN descriptors d ON bd.DescriptorID = d.DescriptorID
                        WHERE bc.CreatorID = ?
                        GROUP BY d.DescriptorID
                        ORDER BY TotalWeight DESC
                        LIMIT 10
                    ");
                    
                    $descStmt->bind_param("i", $profileId);
                    $descStmt->execute();
                    $descResult = $descStmt->get_result();

                    if ($descResult->num_rows > 0) {
                        $descriptors = [];
                        while ($descriptor = $descResult->fetch_assoc()) {
                            $name = safe_htmlspecialchars($descriptor["Name"]);
                            $id = (int)$descriptor["DescriptorID"];
                            $shortDescription = safe_htmlspecialchars($descriptor["ShortDescription"]);
                            $descriptors[] = '
                                            <span class="tooltip-wrapper">
                                                <a style="color:inherit;" href="../descriptor/?id=' . $id . '">' . $name . '</a>
                                                <span class="tooltip-box">
                                                    ' . $shortDescription . '
                                                </span>
                                            </span>';
                        }
                        echo implode(", ", $descriptors);
                    } else {
                        echo "<i>None yet</i>";
                    }
                    $descStmt->close();
                ?>
            </span>
        </div>

        <div style="background-color:#203838; flex:1; overflow-y:auto; max-height:23em;">
            <?php
                if ($loggedIn) {
                    $stmt = $conn->prepare("
                        SELECT r.*, b.DifficultyName, b.SetID 
                        FROM `ratings` r 
                        INNER JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID 
                        INNER JOIN `beatmap_creators` bc ON b.BeatmapID = bc.BeatmapID
                        INNER JOIN `users` u ON r.UserID = u.UserID 
                        WHERE bc.CreatorID = ? AND b.Mode = ? AND u.HideRatings = 0
                        AND r.UserID NOT IN (
                            SELECT UserIDTo 
                            FROM user_relations 
                            WHERE UserIDFrom = ? AND type = 2
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
                        LIMIT 60
                    ");
                    $stmt->bind_param("iiiiii", $profileId, $mode, $userId, $userId, $userId, $userId);
                } else {
                    $stmt = $conn->prepare("
                        SELECT r.*, b.DifficultyName, b.SetID 
                        FROM `ratings` r 
                        INNER JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID 
                        INNER JOIN `beatmap_creators` bc ON b.BeatmapID = bc.BeatmapID
                        INNER JOIN `users` u ON r.UserID = u.UserID 
                        WHERE bc.CreatorID = ? AND b.Mode = ? AND u.HideRatings = 0
                        ORDER BY r.date DESC 
                        LIMIT 60
                    ");
                    $stmt->bind_param("ii", $profileId, $mode);
                }
                $stmt->execute();
                $recentRatingsResult = $stmt->get_result();

                if ($recentRatingsResult->num_rows > 0) {
                    while($row = $recentRatingsResult->fetch_assoc()) {
            ?>
                <div class="flex-container ratingContainer alternating-bg">
                    <div class="flex-child" style="margin-left:0.5em;">
                        <a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';"></a>
                    </div>
                    <div class="flex-child">
                        <a style="display:flex;" href="/profile/<?php echo $row["UserID"]; ?>">
                            <img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo safe_htmlspecialchars(GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>"/>
                        </a>
                    </div>
                    <div class="flex-child" style="flex:0 0 66%;">
                        <a style="display:flex;" href="/profile/<?php echo $row["UserID"]; ?>">
                            <?php echo safe_htmlspecialchars(GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>
                        </a>
                        <?php
                            echo RenderUserRating($conn, $row) . " on " . "<a href='/mapset/" . $row["SetID"] . "'>" . safe_htmlspecialchars(mb_strimwidth($row["DifficultyName"], 0, 35, "..."), ENT_QUOTES) . "</a>";
                        ?>
                    </div>
                </div>
            <?php
                    }
                } else {
                    echo "<div style='height:100%; display:flex; align-items:center; justify-content:center;'><span class='subText'>No ratings yet</span></div>";
                }
                $stmt->close();
            ?>
        </div>
    </div>
    <br />
<?php
    }
?>

<hr>
<div style="margin-bottom: 1em;">
    <?php
        $filterConfig = [
            'showYear' => true,
            'showSR' => true,
            'showRating' => $loggedIn,
            'showTag' => false,
            'sortOptions' => [
                '1' => 'Latest',
                '2' => 'Oldest',
                '3' => 'Highest rated',
                '4' => 'Lowest rated',
            ],
            'categories' => ['genre', 'language', 'country', 'descriptor', 'status'],
        ];
        require "../functions/filter/index.php";
    ?>
    <label>
        <input type="checkbox" id="hideLessRelevantCheckbox" checked> <span>Hide less-relevant maps (Most rated and/or highest charted, min. 10 shown)</span>
    </label>
</div>
<div id="beatmaps">
    <?php
        $setsQuery = "SELECT s.SetID, s.CreatorID, s.Artist, s.Title
            FROM beatmap_creators c
            LEFT JOIN beatmaps b ON b.BeatmapID = c.BeatmapID
            LEFT JOIN beatmapsets s ON b.SetID = s.SetID
            WHERE c.CreatorID = ?
            {$filterConditions}
            GROUP BY s.SetID
            ORDER BY {$orderSQL}";

        $stmt = $conn->prepare($setsQuery);
        if (!empty($filterTypes)) {
            $allTypes = "i" . $filterTypes;
            $allValues = array_merge([$profileId], $filterValues);
            $stmt->bind_param($allTypes, ...$allValues);
        } else {
            $stmt->bind_param("i", $profileId);
        }

        $stmt->execute();
        $setsResult = $stmt->get_result();
        $stmt->close();

        while ($set = $setsResult->fetch_assoc()) {
			if ($set['SetID'] == null)
				continue;

            $stmt = $conn->prepare("SELECT
                b.`BeatmapID`,
                s.`DateRanked`,
                b.`DifficultyName`,
                b.`WeightedAvg`,
                b.`RatingCount`,
                b.`SR`,
                b.`ChartRank`,
                r.`Score`,
                (SELECT COUNT(DISTINCT CreatorID) FROM beatmap_creators WHERE BeatmapID = b.`BeatmapID`) AS NumCreators
                FROM beatmaps b
                LEFT JOIN beatmapsets s ON b.SetID = s.SetID
                INNER JOIN beatmap_creators bc ON b.`BeatmapID` = bc.`BeatmapID`
                LEFT JOIN ratings r ON b.`BeatmapID` = r.`BeatmapID` AND r.`UserID` = ?
                WHERE b.`SetID` = ? AND bc.`CreatorID` = ?
                ORDER BY b.`ChartRank` IS NULL, b.`ChartRank` ASC, b.`RatingCount` DESC
            ");
            $stmt->bind_param("iii", $userId, $set["SetID"], $profileId);
            $stmt->execute();
            $difficultyResult = $stmt->get_result();

            $stmt2 = $conn->prepare("SELECT COUNT(*) FROM comments WHERE SetID = ?");
            $stmt2->bind_param("i", $set["SetID"]);
            $stmt2->execute();
            $commentCount = $stmt2->get_result()->fetch_row()[0];
            $stmt2->close();

            $topMap = $difficultyResult->fetch_assoc();
            $topMapIsBolded = isset($topMap["ChartRank"]) && $topMap["ChartRank"] <= 250;
            $topMapIsGD = $set["CreatorID"] != $profileId;
            $topMapIsCollab = $topMap["NumCreators"] > 1;
            $topMapRatingCount = $topMap["RatingCount"] ?? 0;
            $topMapChartRank = $topMap["ChartRank"] ?? "";

            $stmt->close();
    ?>
            <div data-rating-count="<?php echo $topMapRatingCount; ?>" data-chart-rank="<?php echo $topMapChartRank; ?>" class="profile-top-map<?php if ($difficultyResult->num_rows > 1) echo ' clickable'; ?>">
                <a href="/mapset/<?php echo $set['SetID']; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $set['SetID']; ?>l.jpg" class="diffThumb" style="height:48px;width:48px;margin-right:0.5em;" onerror="this.onerror=null; this.src='../assets/img/missing-map-thumbnail.png';" loading="lazy" /></a>
                <div>
                    <a href="/mapset/<?php echo $set['SetID']; ?>">
					<?php echo $set['Artist']; ?> - <?php echo safe_htmlspecialchars($set['Title'], ENT_QUOTES); ?> 
					<a href="https://osu.ppy.sh/b/<?php echo $topMap['BeatmapID']; ?>" target="_blank" rel="noopener noreferrer"><i class="icon-external-link" style="font-size:10px;">
					</i></a><br></a>
                    <a <?php if ($topMapIsBolded) { echo "style='font-weight:bolder;'"; } ?> href="/mapset/<?php echo $set['SetID']; ?>">
					<?php echo safe_htmlspecialchars($topMap['DifficultyName'], ENT_QUOTES); ?></a> <span class="subText">
					<?php echo number_format((float)$topMap['SR'], 2, '.', ''); ?>* <?php if ($topMapIsCollab) echo "(collab)"; elseif ($topMapIsGD) echo "(GD)"; ?></span><br>
                    <?php echo date("M jS, Y", strtotime($topMap['DateRanked']));?><br>
                </div>
                <div style="margin-left:auto;">
                    <span style="display: inline-block;margin-right:1em;">
                        <?php
                            if (isset($topMap["Score"]))
                                echo RenderRating($topMap["Score"]);
                        ?>
                    </span>
                    <span style="display: inline-block;margin-right:1em;min-width:8em;">
                        <?php echo $commentCount; ?> <span class="subText">comment<?php if ($commentCount != 1) echo 's'; ?></span>
                    </span>
                    <span style="display: inline-block;min-width:13em;">
                        <?php if (isset($topMap["WeightedAvg"])) { ?>
                        <b><?php echo number_format((float)$topMap["WeightedAvg"], 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $topMap["RatingCount"]; ?></span> votes</span><br>
                        <?php } ?>
                    </span>
                    <span class="collapse-arrow" style="display: inline-block;<?php if ($difficultyResult->num_rows == 1) echo 'visibility:hidden;'; ?>user-select:none;margin-left:0.5em;margin-right:0.5em;width:1em;">
                        ◀
                    </span>
                </div>
            </div>

            <div class="lesser-maps" style="display: none;">
                <?php
                    while($map = $difficultyResult->fetch_assoc()){
                        $mapIsBolded = $map["ChartRank"] <= 250 && isset($map["ChartRank"]);
                ?>
                    <div class="profile-lesser-map">
                        <div style="display:inline-block;">
                            <a <?php if ($mapIsBolded) { echo "style='font-weight:bolder;'"; } ?> href="/mapset/<?php echo $set['SetID']; ?>"><?php echo safe_htmlspecialchars($map['DifficultyName'], ENT_QUOTES); ?></a> <span class="subText"><?php echo number_format((float)$map['SR'], 2, '.', ''); ?>* <?php if ($topMapIsGD) echo ("(GD)"); ?></span><br>
                        </div>

                        <div style="float:right;display: inline-block;min-width:13em;min-height:1px;text-align:right;">
                            <?php if (isset($map["ChartRank"])) { ?>
                                <b><?php echo number_format((float)$map["WeightedAvg"], 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $map["RatingCount"]; ?></span> votes</span><br>
                            <?php } ?>
                        </div>

                        <?php if (isset($map["Score"])) { ?>
                            <div style="float:right;display:inline-block;">
                                <?php echo RenderRating($map["Score"]); ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        <?php
        }
        ?>
</div>

<script>
    var coll = document.getElementsByClassName("profile-top-map");

    for (let i = 0; i < coll.length; i++) {
        coll[i].addEventListener("click", function() {
            var arrow = this.querySelector(".collapse-arrow");
            var content = this.nextElementSibling;
            if (content.style.display === "block") {
                content.style.display = "none";
                arrow.textContent = "◀";
            } else {
                content.style.display = "block";
                arrow.textContent = "▼";
            }
        });
    }

    $(document).ready(function() {

        var savedHide = localStorage.getItem('hideLessRelevantMaps');
        if (savedHide !== null) {
            $('#hideLessRelevantCheckbox').prop('checked', savedHide === 'true');
        }

        function relevanceCheck() {
            if ($('#hideLessRelevantCheckbox').is(':checked')) {
                var maps = $('.profile-top-map').map(function() {
                    return {
                        el: this,
                        count: parseInt($(this).attr('data-rating-count')) || 0,
                        rank: parseInt($(this).attr('data-chart-rank')) || Infinity,
                    };
                }).get();

                var maxCount = maps.length ? Math.max(...maps.map(m => m.count)) : 0;
                var threshold = maxCount * 0.5;
                $('.profile-top-map').hide();

                maps.sort((a, b) => {
                    if (a.rank !== b.rank) {
                        return a.rank - b.rank; 
                    }
                    return b.count - a.count;
                });

                maps.forEach(function(map, index) {
                    if (index < 10 || map.count >= threshold) {
                        $(map.el).show();
                    }
                });
            } else {
                $('.profile-top-map').show();
            }

            // Cba changing the alternating BG css just for this so this is to override that
            $('.profile-top-map').css('background-color', '').find('.starBackground').css('color', '');
            $('.profile-top-map:visible').each(function(index) {
                if (index % 2 === 0) {
                    $(this).css('background-color', '#203838').find('.starBackground').css('color', 'darkslategray');
                } else {
                    $(this).css('background-color', 'darkslategray').find('.starBackground').css('color', '#203838');
                }
            });
        }

        relevanceCheck();
        $('#hideLessRelevantCheckbox').change(function() {
            localStorage.setItem('hideLessRelevantMaps', $(this).is(':checked'));
            relevanceCheck();
        });

        $('#friendButton').click(function() {
            $.ajax({
                type: 'POST',
                url: 'DoFriendButton.php',
                data: {
                    'user_id_from': <?php echo $userId; ?>,
                    'user_id_to': <?php echo $profileId; ?>
                },
                success: function(response) {
                    console.log(response)
                    if (response == 'added') {
                        $('#friendButton').text('Friend');
                    } else if (response == 'mutual') {
                        $('#friendButton').text('Mutual').addClass("mutual");
                    } else {
                        $('#friendButton').text('Add Friend').removeClass("mutual");
                    }
                }
            });
        });

        $('#blockButton').click(function() {
            if(confirm('Are you sure you want to block this user?')){
                $.ajax({
                    type: 'POST',
                    url: 'DoBlockButton.php',
                    data: {
                        'user_id_from': <?php echo $userId; ?>,
                        'user_id_to': <?php echo $profileId; ?>
                    },
                    success: function() {
                        location.reload();
                    }
                });
            }
        });

        $(document).on('omdbFiltersSubmitted', function(event, payload) {
            var params = new URLSearchParams();
            if (payload.year)
                params.set('y', payload.year);
            if (payload.order)
                params.set('o', payload.order);
            if (payload.rating)
                params.set('r', payload.rating);
            if (payload.tokens && payload.tokens.length > 0)
                params.set('tokens', JSON.stringify(payload.tokens));

            var url = '?' + params.toString();
            history.replaceState(null, '', url);
        
            var $beatmaps = $('#beatmaps');
            $beatmaps.css('opacity', 0.5);
        
            params.set('id', <?php echo $profileId; ?>);
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (this.readyState === 4 && this.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(this.responseText, "text/html");
                    var newContent = doc.getElementById('beatmaps');
                    if (newContent) {
                        $beatmaps.replaceWith(newContent);
                        attachCollapseHandlers();
                        relevanceCheck();
                    } else {
                        location.reload();
                    }
                    $('#beatmaps').css('opacity', 1);
                }
            };
            xhr.open('POST', 'MapsListing.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send(params.toString());
        });
    });
</script>

<?php require '../footer.php'; ?>