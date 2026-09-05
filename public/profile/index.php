<?php
    require_once __DIR__ . '/../../app/base.php';

    $profileId = $_GET['id'];

    $idStmt = $conn->prepare("SELECT * FROM `users` WHERE `UserID` = ?");
    $usernameStmt = $conn->prepare("SELECT UserID FROM `users` WHERE `Username` = ?");

    if (str_contains($profileId, "@") || preg_match("/[a-z]/i", $profileId)) {
        if ($profileId[0] === "@") {
            $profileId = substr($profileId, 1);
        }

        $usernameStmt->bind_param("s", $profileId);
        $usernameStmt->execute();
        $result = $usernameStmt->get_result();
        $profile = $result->fetch_assoc();
        $usernameStmt->close();

        if ($profile === null) {
            http_response_code(404);
            exit();
        }

        header('Location: ./' . $profile["UserID"]);
        exit();
    }

    if (ctype_digit($profileId)) {
        $idStmt->bind_param("i", $profileId);
        $idStmt->execute();
        $result = $idStmt->get_result();
        $profile = $result->fetch_assoc();
    } else {
        http_response_code(404);
        exit();
    }

    $idStmt->close();
    $isValidUser = $profile !== null;

    $PageTitle = $isValidUser ? GetUserNameFromId($profileId, $conn) : "Profile";
    require '../header.php';

    if ($profileId == 0) {
        require "./OMDBBotUser.php";
        exit();
    }

    $ratingCounts = [];
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
    }

    $stmt = $conn->prepare("SELECT 1 FROM blacklist WHERE UserID = ?");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $isBlacklisted = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    $is_friend = $is_blocked = $is_friended = 0;
    $correlation = null;
    $sharedMapCount = 0;
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
            $lowId = min($userId, $profileId);
            $highId = max($userId, $profileId);

            $stmt = $conn->prepare("SELECT `correlation`, `count`
                FROM `user_correlations`
                WHERE user1_id = ? AND user2_id = ?");
            $stmt->bind_param("ii", $lowId, $highId);
            $stmt->execute();
            $cached = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($cached !== null) {
                $correlation = (float)$cached["correlation"];
                $sharedMapCount = (int)$cached["count"];
            } else {
                $stmt = $conn->prepare("SELECT r1.`Score`, r2.`Score`
                            FROM `ratings` r1
                            JOIN `ratings` r2 ON r1.`BeatmapID` = r2.`BeatmapID`
                            JOIN `beatmaps` b ON r1.`BeatmapID` = b.`BeatmapID`
                            WHERE r1.`UserID` = ? AND r2.`UserID` = ? AND b.`Mode` = ?");
                $stmt->bind_param("iii", $userId, $profileId, $mode);
                $stmt->execute();
                $rows = $stmt->get_result()->fetch_all(MYSQLI_NUM);
                $stmt->close();

                $sharedMapCount = count($rows);
                $correlation = CalculatePearsonCorrelation(
                    array_column($rows, 0),
                    array_column($rows, 1)
                );
            }
        }
    }

    $shouldHideProfile = isset($profile) && $profile["IsPrivate"] && !($is_friend && $is_friended) && $userId != $profileId;

    $hasRatedMaps = false;
    $hasMaps = false;

    if (!$isBlacklisted) {
        $stmt = $conn->prepare("SELECT
                AVG(b.SR) AS AvgSR,
                COUNT(CASE WHEN b.Rating IS NOT NULL THEN b.BeatmapID END) AS RatedMapCount,
                COUNT(b.BeatmapID) AS MapCount,
                COALESCE(
                    SUM(b.RatingCount),
                    0
                ) AS TotalRatings
            FROM beatmap_creators bc
            JOIN beatmaps b ON bc.BeatmapID = b.BeatmapID
            WHERE bc.CreatorID = ? AND b.Mode = ?");
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

        $hasMaps = $mapStats['MapCount'] > 0;
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

    RenderCustomThemeCss($profile);
?>

<?php if ($shouldHideProfile) { ?>

    <center>
        <div class="profileCard">
            <div class="profileTitle">
                <a href="https://osu.ppy.sh/u/<?php echo $profileId; ?>" target="_blank" rel="noopener noreferrer"><?php echo safe_htmlspecialchars(GetUserNameFromId($profileId, $conn), ENT_QUOTES); ?></a> <a href="https://osu.ppy.sh/u/<?php echo $profileId; ?>" target="_blank" rel="noopener noreferrer"></a>
            </div>
            <div class="profileImage">
                <img class="square-thumb" src="https://s.ppy.sh/a/<?php echo $profileId; ?>" style="width:146px;height:146px;"/>
            </div>

            <?php if ($isValidUser && !IS_NULL($profile['UserTitle'])) { ?>
            <div class="profileUserTitle">
                <span class="subText" style="font-weight:bolder;"><?php echo $profile['UserTitle']; ?></span>
            </div>
            <?php } ?>

            <?php if ($isValidUser && $profile['IsPatron'] === 1) { ?>
            <div class="profilePatronBadge">
                <a href="/patron/" style="text-decoration:none;">
                    <div style="background-color: var(--main-theme-patron-pink); border-radius: 2px; color: contrast-color(var(--main-theme-patron-pink)); font-weight:bolder; padding:0.5em; margin-top: 0.5em;"><i class="icon-heart"></i> Patron</div>
                </a>
            </div>
            <?php } ?>

            <?php if ($profileId != $userId && $isValidUser && $loggedIn) { ?>
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

            <div class="profileStats">
                This user has a hidden OMDB presence.
            </div>
        </div>
    </center>

<?php } else { ?>

<div class="profileContainer column-when-mobile-container">
	<div class="profileCard">
		<div class="profileTitle">
            <a href="https://osu.ppy.sh/u/<?php echo $profileId; ?>" target="_blank" rel="noopener noreferrer"><?php echo safe_htmlspecialchars(GetUserNameFromId($profileId, $conn), ENT_QUOTES); ?></a> <a href="https://osu.ppy.sh/u/<?php echo $profileId; ?>" target="_blank" rel="noopener noreferrer"></i></a>
		</div>
		<div class="profileImage">
			<img class="square-thumb" src="https://s.ppy.sh/a/<?php echo $profileId; ?>" style="width:146px;height:146px;"/>
		</div>

		<?php if ($isValidUser && !IS_NULL($profile['UserTitle'])) { ?>
		<div class="profileUserTitle">
			<span class="subText" style="font-weight:bolder;"><?php echo $profile['UserTitle']; ?></span>
		</div>
		<?php } ?>

        <?php if ($isValidUser && $profile['IsPatron'] === 1) { ?>
		<div class="profilePatronBadge">
            <a href="/patron/" style="text-decoration:none;">
			    <div style="background-color: var(--main-theme-patron-pink); border-radius: 2px; color: contrast-color(var(--main-theme-patron-pink)); font-weight:bolder; padding:0.5em; margin-top: 0.5em;"><i class="icon-heart"></i> Patron</div>
            </a>
        </div>
		<?php } ?>

		<?php if ($profileId != $userId && $isValidUser && $loggedIn) { ?>
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
        ?>

        <div class="profileStats">
            <?php if ($isValidUser) { ?>
            <a href="friends/?id=<?php echo $profileId; ?>">
                <b>Friends:</b> <?php echo $friendCount; ?>
            </a><br>
            <?php } ?>

            <?php if ($isValidUser && $ratingCount > 0) { ?>
            <a href="ratings/?id=<?php echo $profileId; ?>&p=1">
                <b>Ratings:</b> <?php echo $ratingCount; ?>
            </a><br>
            <?php } ?>

            <?php if ($isValidUser && $commentCount > 0) { ?>
            <a href="comments/?id=<?php echo $profileId; ?>">
                <b>Comments:</b> <?php echo $commentCount; ?>
            </a><br>
            <?php } ?>

            <?php if ($isValidUser && $reviewCount > 0) { ?>
            <a href="reviews/?id=<?php echo $profileId; ?>">
                <b>Reviews:</b> <?php echo $reviewCount; ?>
            </a><br>
            <?php } ?>

            <?php if ($isValidUser && $approvedEditCount > 0) { ?>
            <b>Approved Edits:</b> <?php echo $approvedEditCount; ?><br>
            <?php } ?>

            <?php if ($isValidUser && $approvedEditCount > 0) { ?>
            <b>Descriptor votes:</b> <?php echo $descriptorVoteCount; ?><br>
            <?php } ?>
        </div>

		<?php if ($isValidUser) { ?>
			<div class="profileRankingDistribution" style="margin-bottom:0.5em;">
                <div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["5.0"] ?? 0) / $maxRating) * 90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=5&p=1">5.0 <?php if ($profile["Custom50Rating"] != "") {
                    echo " - " . safe_htmlspecialchars($profile["Custom50Rating"], ENT_QUOTES);
                } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["4.5"] ?? 0) / $maxRating) * 90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=4.5&p=1">4.5 <?php if ($profile["Custom45Rating"] != "") {
                    echo " - " . safe_htmlspecialchars($profile["Custom45Rating"], ENT_QUOTES);
                } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["4.0"] ?? 0) / $maxRating) * 90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=4&p=1">4.0 <?php if ($profile["Custom40Rating"] != "") {
                    echo " - " . safe_htmlspecialchars($profile["Custom40Rating"], ENT_QUOTES);
                } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["3.5"] ?? 0) / $maxRating) * 90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=3.5&p=1">3.5 <?php if ($profile["Custom35Rating"] != "") {
                    echo " - " . safe_htmlspecialchars($profile["Custom35Rating"], ENT_QUOTES);
                } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["3.0"] ?? 0) / $maxRating) * 90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=3&p=1">3.0 <?php if ($profile["Custom30Rating"] != "") {
                    echo " - " . safe_htmlspecialchars($profile["Custom30Rating"], ENT_QUOTES);
                } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["2.5"] ?? 0) / $maxRating) * 90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=2.5&p=1">2.5 <?php if ($profile["Custom25Rating"] != "") {
                    echo " - " . safe_htmlspecialchars($profile["Custom25Rating"], ENT_QUOTES);
                } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["2.0"] ?? 0) / $maxRating) * 90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=2&p=1">2.0 <?php if ($profile["Custom20Rating"] != "") {
                    echo " - " . safe_htmlspecialchars($profile["Custom20Rating"], ENT_QUOTES);
                } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["1.5"] ?? 0) / $maxRating) * 90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=1.5&p=1">1.5 <?php if ($profile["Custom15Rating"] != "") {
                    echo " - " . safe_htmlspecialchars($profile["Custom15Rating"], ENT_QUOTES);
                } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["1.0"] ?? 0) / $maxRating) * 90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=1&p=1">1.0 <?php if ($profile["Custom10Rating"] != "") {
                    echo " - " . safe_htmlspecialchars($profile["Custom10Rating"], ENT_QUOTES);
                } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["0.5"] ?? 0) / $maxRating) * 90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=0.5&p=1">0.5 <?php if ($profile["Custom05Rating"] != "") {
                    echo " - " . safe_htmlspecialchars($profile["Custom05Rating"], ENT_QUOTES);
                } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo (($ratingCounts["0.0"] ?? 0) / $maxRating) * 90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=0&p=1">0.0 <?php if ($profile["Custom00Rating"] != "") {
                    echo " - " . safe_htmlspecialchars($profile["Custom00Rating"], ENT_QUOTES);
                } ?></a></div>
			</div>
			<div style="margin-bottom:2em;">
				Rating Distribution<br>
			</div>
        <?php
                if ($loggedIn && $profileId != $userId && isset($correlation)) {
                    $widthPercentage = abs(($correlation / 2) * 100);
                    $leftMargin = 0;

                    if ($correlation < 0) {
                        $leftMargin = 50 - $widthPercentage;
                    }
                    if ($correlation > 0) {
                        $leftMargin = 50;
                    }
            ?>
				<div class="profileRankingDistribution" style="margin-bottom:0.5em;height:1.5em;">
					<div class="profileRankingDistributionBar" style="width: <?php echo $widthPercentage;?>%;height:1.5em;position:relative;margin-left:<?php echo $leftMargin;?>%;padding:0px;box-sizing: border-box;"></div>
				    <span class="verticalLine"></span>
                </div>
				<div style="margin-bottom:1em;">
                    <div><span class="subText"><?php echo round($correlation, 3); ?></span></div>
					Rating Similarity To You<br><span class="subText">based on <?php echo $sharedMapCount; ?> map<?php echo $sharedMapCount === 1 ? '' : 's'; ?></span>
				</div>
			<?php } elseif ($profileId == $userId) {
                    ?>
                    <a href="compatible/?id=<?php echo $profileId; ?>">View users similar to you!</a>
                    <?php
                }
            } ?>

            <?php
                if ($isValidUser && $profile["IsPrivate"]) {
                    echo "<span style='margin-top:0.5em;' class='subText'>User has hidden OMDB presence</span>";
                }
            ?>
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
    if ($isValidUser) {
        $desc = trim($profile["CustomDescription"] ?? "");

        if (!empty($desc)) {
?>
			<hr>
			<h2>About me</h2>
			<div style="background-color:var(--main-theme-color-darker);padding:2em;box-sizing:border-box;max-height:30em;overflow-y:scroll;">
				<?php
                    echo ParseCommentLinks($conn, $desc);
                ?>
			</div>

			<?php
             if ($profileId == $userId) {
                 echo "<br><a href='../settings'><div style='float:right;'>edit your description</div></a>";
             }
             echo "<br />";
        }
    }
?>

<?php
    if ($isValidUser && isset($mutualCount) && $mutualCount > 0) {
?>
        <hr>
        <h2>Mutuals</h2>
        <div class="flex-container" style="background-color:var(--main-theme-color-darker);overflow:hidden;flex-wrap:wrap;justify-content:space-evenly;">
            <?php
                $counter = 0;
                $max = 8;

                while ($row = $mutuals->fetch_assoc() and ($counter < $max)) {
                    ?>
                    <div class="flex-child" style="text-align:center;flex:0 0 auto;flex-wrap:wrap;padding:0.5em;flex-direction:column;min-width:0;">
                        <div class="profileImage">
                            <a href="/profile/<?php echo $row["ID"]; ?>"><img class="square-thumb" src="https://s.ppy.sh/a/<?php echo $row["ID"]; ?>" style="max-width:5em;aspect-ratio:1/1;object-fit:cover;max-width:5em;width:100%;" loading="lazy" /></a><br>
                            <a href="/profile/<?php echo $row["ID"]; ?>" style="width:100%;display:block;overflow:hidden;text-overflow:ellipsis;"><?php echo safe_htmlspecialchars($row["username"], ENT_QUOTES); ?></a>
                        </div>
                    </div>
            <?php
                    $counter++;
                }
            ?>
        </div>

        <?php
         if ($mutualCount > 10) {
             echo "<br><a href='friends/?id={$profileId}'><div style='float:right;'>...view more!</div></a>";
         }
         echo "<br />";
    }
?>

<?php } ?>

<?php
    if ($hasRatedMaps) {
?>
    <hr>
    <h2>Mapping Overview</h2>
    <div class="flex-container column-when-mobile-container" style="justify-content:space-around; align-items:stretch; gap:67px;">
        <div class="flex-container" style="background-color:var(--main-theme-color-darker); flex:1; text-align:center; box-sizing:border-box; flex-direction:column; justify-content:center; padding:0.25em;">
            <h3 style="margin:0;">Highest Rated</h3>
            <span class="subText">Excl. collabs with 4+ mappers</span>
            <?php if (isset($highestMap)) {
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
                        $shortDescription = ParseShortLinks($conn, safe_htmlspecialchars($descriptor["ShortDescription"]), false);

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
                    <b><?php echo number_format((float)$highestMap['WeightedAvg'], 2); ?></b> <span class="subText">/ 5.00 from <span style="color: var(--main-theme-text-color);"><?php echo $highestMap["RatingCount"]; ?></span> votes</span>
                    <br>
                    <?php if ($highestMap["ChartRank"] != null) { ?>
                        <b>#<?php echo $highestMap["ChartYearRank"]; ?></b> for <a href="/charts/?y=<?php echo $highestMapYear;?>&p=<?php echo ceil($highestMap["ChartYearRank"] / 50); ?>"><?php echo $highestMapYear;?></a>, <b>#<?php echo $highestMap["ChartRank"]; ?></b> <a href="/charts/?y=all-time&p=<?php echo ceil($highestMap["ChartRank"] / 50); ?>">overall</a>
                    <?php } ?>
                </div>
            <?php } else {
                echo "<span class='subText'>N/A</span>";
            } ?>
        </div>

        <div style="background-color:var(--main-theme-color-darker); flex:1; text-align:center; display:flex; flex-direction:column; justify-content:center; box-sizing:border-box; padding:0.25em;">
            <div style="margin-bottom: 1em;">
                <?php if ($mapsetCount > 0) { ?>
                    <b>Ranked Mapsets:</b> <?php echo $mapsetCount; ?><br>
                <?php } ?>
                <b>Total Ratings Received:</b> <?php echo $mapStats['TotalRatings']; ?><br>
                <b>Average Star Rating:</b> <?php echo number_format((float)$mapStats['AvgSR'], 2); ?>*<br>
                <?php if ($activeYear) { ?>
                    <b>Most Active Year:</b> <?php echo $activeYear; ?>
                <?php } ?>
            </div>

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
                            $shortDescription = ParseShortLinks($conn, safe_htmlspecialchars($descriptor["ShortDescription"]), false);
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

        <div style="background-color:var(--main-theme-color-darker); flex:1; overflow-y:auto; max-height:23em;">
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
                            AND (
                                u.IsPrivate = 0
                                OR (
                                    EXISTS (
                                        SELECT 1
                                        FROM user_relations ur1
                                        WHERE ur1.UserIDFrom = ?
                                        AND ur1.UserIDTo = r.UserID
                                        AND ur1.type = 1
                                    )
                                    AND EXISTS (
                                        SELECT 1
                                        FROM user_relations ur2
                                        WHERE ur2.UserIDFrom = r.UserID
                                        AND ur2.UserIDTo = ?
                                        AND ur2.type = 1
                                    )
                                )
                            )
                        ORDER BY r.date DESC
                        LIMIT 60
                    ");
                    $stmt->bind_param("iiiiiiii", $profileId, $mode, $userId, $userId, $userId, $userId, $userId, $userId);
                } else {
                    $stmt = $conn->prepare("
                        SELECT r.*, b.DifficultyName, b.SetID
                        FROM `ratings` r
                        INNER JOIN `beatmaps` b ON r.BeatmapID = b.BeatmapID
                        INNER JOIN `beatmap_creators` bc ON b.BeatmapID = bc.BeatmapID
                        INNER JOIN `users` u ON r.UserID = u.UserID
                        WHERE bc.CreatorID = ? AND b.Mode = ? AND u.HideRatings = 0 AND u.IsPrivate = 0
                        ORDER BY r.date DESC
                        LIMIT 60
                    ");
                    $stmt->bind_param("ii", $profileId, $mode);
                }
                $stmt->execute();
                $recentRatingsResult = $stmt->get_result();

                if ($recentRatingsResult->num_rows > 0) {
                    while ($row = $recentRatingsResult->fetch_assoc()) {
            ?>
                <div class="flex-container ratingContainer alternating-bg">
                    <div class="flex-child" style="margin-left:0.5em;">
                        <a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';"></a>
                    </div>
                    <div class="flex-child">
                        <a style="display:flex;" href="/profile/<?php echo $row["UserID"]; ?>">
                            <img class="square-thumb" src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo safe_htmlspecialchars(GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>"/>
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


<?php
if ($hasMaps) {
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
                    '5' => 'Most rated',
                    '6' => 'Least rated',
                ],
                'categories' => ['genre', 'language', 'country', 'descriptor', 'status', 'meta', 'user', 'tag', 'slot'],
            ];
            require __DIR__ . '/../../app/functions/filter/index.php';
        ?>
        <label>
            <input type="checkbox" id="hideLessRelevantCheckbox" checked> <span>Hide less-relevant maps (Most rated and/or highest charted, min. 10 shown)</span>
        </label>
    </div>

    <?php
    include 'MapsListing.php';
}
?>

<script>
    function attachCollapseHandlers() {
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
    }

    $(document).ready(function() {
        attachCollapseHandlers();

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
                    $(this).css('background-color', 'var(--main-theme-color-darker)').find('.starBackground').css('color', 'var(--main-theme-color)');
                } else {
                    $(this).css('background-color', 'var(--main-theme-color)').find('.starBackground').css('color', 'var(--main-theme-color-darker)');
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
                params.set('tokens', encodeTokens(payload.tokens));

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
