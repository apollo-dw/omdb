<?php
	$profileId = $_GET['id'] ?? -1;
	require "../base.php";

	if ($profileId == -1 || !is_numeric($profileId)) {
		siteRedirect();
	}

    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `UserID` = ?");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();
	$isValidUser = true;

	if ($profile == NULL)
		$isValidUser = false;

    $PageTitle = $profile != NULL ? GetUserNameFromId($profileId, $conn) : "Profile";
    require '../header.php';

	$ratingCounts = array();

    if ($isValidUser) {
        $stmt = $conn->prepare("SELECT r.`Score`, COUNT(*) AS count
                        FROM `ratings` r
                        JOIN `beatmaps` b ON r.`BeatmapID` = b.`BeatmapID`
                        WHERE r.`UserID` = ? AND b.`Mode` = ?
                        GROUP BY r.`Score`");
        $stmt->bind_param("ii", $profileId, $mode);
        $stmt->execute();
        $result = $stmt->get_result();

        $ratingCounts = array();
        while ($row = $result->fetch_assoc()) {
            $ratingCounts[$row['Score']] = $row['count'];
        }
        $stmt->close();

        $maxRating = sizeof($ratingCounts) >= 1 ? max($ratingCounts) : 2;

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

        if ($profileId != $userId){
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
            <a href="https://osu.ppy.sh/u/<?php echo $profileId; ?>" target="_blank" rel="noopener noreferrer"><?php echo GetUserNameFromId($profileId, $conn); ?></a> <a href="https://osu.ppy.sh/u/<?php echo $profileId; ?>" target="_blank" rel="noopener noreferrer"><i class="icon-external-link" style="font-size:10px;"></i></a>
		</div>
		<div class="profileImage">
			<img src="https://s.ppy.sh/a/<?php echo $profileId; ?>" style="width:146px;height:146px;"/>
		</div>
        <div class="profileActions">
            <?php
                if ($profileId != $userId && $isValidUser){

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
                }
            ?>
        </div>
        <div class="profileStats">
            <?php
                $stmt = $conn->prepare("SELECT COUNT(*) FROM `user_relations` WHERE `UserIDTo` = ? AND `type` = '1'");
                $stmt->bind_param("i", $profileId);
                $stmt->execute();
                $stmt->bind_result($friendCount);
                $stmt->fetch();
                $stmt->close();
            ?>
            <a href="friends/?id=<?php echo $profileId; ?>"><b>Friends:</b> <?php echo $friendCount; ?></a><br>

            <?php
                $stmt = $conn->prepare("SELECT COUNT(*) FROM `ratings` WHERE `UserID` = ?");
                $stmt->bind_param("i", $profileId);
                $stmt->execute();
                $stmt->bind_result($ratingCount);
                $stmt->fetch();
                $stmt->close();
            ?>
            <a href="ratings/?id=<?php echo $profileId; ?>&p=1"><b>Ratings:</b> <?php echo $ratingCount; ?></a><br>

            <?php
                $stmt = $conn->prepare("SELECT COUNT(*) FROM `comments` WHERE `UserID` = ?");
                $stmt->bind_param("i", $profileId);
                $stmt->execute();
                $stmt->bind_result($commentCount);
                $stmt->fetch();
                $stmt->close();
            ?>
            <a href="comments/?id=<?php echo $profileId; ?>"><b>Comments:</b> <?php echo $commentCount; ?></a><br>

            <?php
                $stmt = $conn->prepare("SELECT COUNT(DISTINCT SetID) FROM `beatmaps` WHERE `SetCreatorID` = ?");
                $stmt->bind_param("i", $profileId);
                $stmt->execute();
                $stmt->bind_result($mapsetCount);
                $stmt->fetch();
                $stmt->close();
            ?>
            <b>Ranked Mapsets:</b> <?php echo $mapsetCount; ?><br>

            <?php
            $stmt = $conn->prepare("SELECT COUNT(*) FROM `beatmap_edit_requests` WHERE `UserID` = ? AND Status = 'Approved';");
            $stmt->bind_param("i", $profileId);
            $stmt->execute();
            $stmt->bind_result($approvedEditCount);
            $stmt->fetch();
            $stmt->close();
            ?>
            <b>Approved Edits:</b> <?php echo $approvedEditCount; ?><br>
        </div>
		<?php if ($isValidUser){ ?>
			<div class="profileRankingDistribution" style="margin-bottom:0.5em;">
                <div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["5.0"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=5.0&p=1">5.0 <?php if ($profile["Custom50Rating"] != "") { echo " - " . htmlspecialchars($profile["Custom50Rating"]); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["4.5"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=4.5&p=1">4.5 <?php if ($profile["Custom45Rating"] != "") { echo " - " . htmlspecialchars($profile["Custom45Rating"]); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["4.0"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=4.0&p=1">4.0 <?php if ($profile["Custom40Rating"] != "") { echo " - " . htmlspecialchars($profile["Custom40Rating"]); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["3.5"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=3.5&p=1">3.5 <?php if ($profile["Custom35Rating"] != "") { echo " - " . htmlspecialchars($profile["Custom35Rating"]); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["3.0"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=3.0&p=1">3.0 <?php if ($profile["Custom30Rating"] != "") { echo " - " . htmlspecialchars($profile["Custom30Rating"]); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["2.5"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=2.5&p=1">2.5 <?php if ($profile["Custom25Rating"] != "") { echo " - " . htmlspecialchars($profile["Custom25Rating"]); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["2.0"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=2.0&p=1">2.0 <?php if ($profile["Custom20Rating"] != "") { echo " - " . htmlspecialchars($profile["Custom20Rating"]); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["1.5"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=1.5&p=1">1.5 <?php if ($profile["Custom15Rating"] != "") { echo " - " . htmlspecialchars($profile["Custom15Rating"]); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["1.0"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=1.0&p=1">1.0 <?php if ($profile["Custom10Rating"] != "") { echo " - " . htmlspecialchars($profile["Custom10Rating"]); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["0.5"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=0.5&p=1">0.5 <?php if ($profile["Custom05Rating"] != "") { echo " - " . htmlspecialchars($profile["Custom05Rating"]); } ?></a></div>
				<div class="profileRankingDistributionBar" style="width: <?php echo ($ratingCounts["0.0"]/$maxRating)*90; ?>%;"><a href="ratings/?id=<?php echo $profileId; ?>&r=0.0&p=1">0.0 <?php if ($profile["Custom00Rating"] != "") { echo " - " . htmlspecialchars($profile["Custom00Rating"]); } ?></a></div>
			</div>
			<div style="margin-bottom:1.5em;">
				Rating Distribution<br>
			</div>
        <?php
				if ($loggedIn && $profileId != $userId) {
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
		<?php
			if($isValidUser){
		?>
		<div id="ratingDisplay">
			<?php
				include 'rating.php';
			?>
		</div>
		<?php
			} else {
		?>
			This person is not an OMDB user :(
		<?php
			}
		?>
	</div>
</div>

<?php
    if($isValidUser && $mutualCount > 0) {
?>
        <hr>
        Mutuals
        <div class="flex-container" style="background-color:DarkSlateGrey;padding:0px;">
            <br>
            <?php
                $counter = 0;
                $max = 10;

                while($row = $mutuals->fetch_assoc() and ($counter < $max)) {
                    ?>
                    <div class="flex-child" style="text-align:center;width:11%;padding:0.5em;flex-direction:column;">
                        <div class="profileImage">
                            <a href="/profile/<?php echo $row["ID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["ID"]; ?>" style="width:5em;height:5em;"/></a><br>
                            <a href="/profile/<?php echo $row["ID"]; ?>"><?php echo $row["username"]; ?></a>
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


<hr>
<span class="subText">This display is currently WIP! I am planning to add a checkbox to hide less-relevant maps (ones with low amount of ratings)</span><br><br>
<div id="beatmaps">
    <?php
        $stmt = $conn->prepare("SELECT DISTINCT b.`SetID`, b.`SetCreatorID`, b.`Artist`, b.`Title`
                           FROM beatmaps b
                           INNER JOIN beatmap_creators bc ON b.`BeatmapID` = bc.`BeatmapID`
                           WHERE bc.`CreatorID` = ? 
                           GROUP BY b.`SetID`, b.`SetCreatorID`, b.`Artist`, b.`Title` 
                           ORDER BY MIN(b.`Timestamp`) DESC;");
        $stmt->bind_param("s", $profileId);
        $stmt->execute();
        $setsResult = $stmt->get_result();
        $stmt->close();

        $sets = [];
        while($row = $setsResult->fetch_assoc())
            $sets[] = $row;

        foreach($sets as $set) {
            $stmt = $conn->prepare("SELECT b.`BeatmapID`, b.`DateRanked`, b.`DifficultyName`, b.`WeightedAvg`, b.`RatingCount`, b.`SR`, b.`ChartRank`, r.`Score`,
                       (SELECT COUNT(DISTINCT CreatorID) FROM beatmap_creators WHERE BeatmapID = b.`BeatmapID`) AS NumCreators
                       FROM beatmaps b
                       INNER JOIN beatmap_creators bc ON b.`BeatmapID` = bc.`BeatmapID`
                       LEFT JOIN ratings r ON b.`BeatmapID` = r.`BeatmapID` AND r.`UserID` = ?
                       WHERE b.`SetID` = ? AND bc.`CreatorID` = ?
                       ORDER BY b.`RatingCount` DESC");

            $stmt->bind_param("iii", $userId, $set["SetID"], $profileId);
            $stmt->execute();
            $difficultyResult = $stmt->get_result();

            $stmt = $conn->prepare("SELECT COUNT(*) FROM comments WHERE SetID = ?");
            $stmt->bind_param("i", $set["SetID"]);
            $stmt->execute();
            $commentCount = $stmt->get_result()->fetch_row()[0];

            $topMap = $difficultyResult->fetch_assoc();
            $topMapIsBolded = isset($topMap["ChartRank"]) && $topMap["ChartRank"] <= 250;
            $topMapIsGD = $set["SetCreatorID"] != $profileId;
            $topMapIsCollab = $topMap["NumCreators"] > 1;

            $stmt->close();
            ?>
            <div class="profile-top-map<?php if ($difficultyResult->num_rows > 1) echo ' clickable'; ?>">
                <a href="/mapset/<?php echo $set['SetID']; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $set['SetID']; ?>l.jpg" class="diffThumb" style="height:48px;width:48px;margin-right:0.5em;" onerror="this.onerror=null; this.src='../charts/INF.png';" /></a>
                <div>
                    <a href="/mapset/<?php echo $set['SetID']; ?>"><?php echo $set['Artist']; ?> - <?php echo htmlspecialchars($set['Title']); ?> <a href="https://osu.ppy.sh/b/<?php echo $topMap['BeatmapID']; ?>" target="_blank" rel="noopener noreferrer"><i class="icon-external-link" style="font-size:10px;"></i></a><br></a>
                    <a <?php if ($topMapIsBolded) { echo "style='font-weight:bolder;'"; } ?> href="/mapset/<?php echo $set['SetID']; ?>"><?php echo htmlspecialchars($topMap['DifficultyName']); ?></a> <span class="subText"><?php echo number_format((float)$topMap['SR'], 2, '.', ''); ?>* <?php if ($topMapIsCollab) echo "(collab)"; elseif ($topMapIsGD) echo "(GD)"; ?></span><br>
                    <?php echo date("M jS, Y", strtotime($topMap['DateRanked']));?><br>
                </div>
                <div style="margin-left:auto;">
                    <span style="display: inline-block;margin-right:1em;"">
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
                        <b><?php echo number_format($topMap["WeightedAvg"], 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $topMap["RatingCount"]; ?></span> votes</span><br>
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
                            <a <?php if ($mapIsBolded) { echo "style='font-weight:bolder;'"; } ?> href="/mapset/<?php echo $set['SetID']; ?>"><?php echo htmlspecialchars($map['DifficultyName']); ?></a> <span class="subText"><?php echo number_format((float)$map['SR'], 2, '.', ''); ?>* <?php if ($topMapIsGD) echo ("(GD)"); ?></span><br>
                        </div>

                        <div style="float:right;display: inline-block;min-width:13em;min-height:1px;text-align:right;">
                            <?php if (isset($map["ChartRank"])) { ?>
                                <b><?php echo number_format($map["WeightedAvg"], 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $map["RatingCount"]; ?></span> votes</span><br>
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
    });
</script>

<?php
    require '../footer.php';
?>
