<?php
    $mapset_id = $_GET['mapset_id'] ?? -1;
    require '../base.php';

    $foundSet = false;
    $stmt = $conn->prepare("SELECT * FROM `beatmaps` WHERE `SetID`=? ORDER BY `Mode`, `SR` DESC;");
    $stmt->bind_param("s", $mapset_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sampleRow = $result->fetch_assoc();
    mysqli_data_seek($result, 0);

    $PageTitle = htmlspecialchars($sampleRow['Title']) . " by " . GetUserNameFromId($sampleRow['SetCreatorID'], $conn);
    $year = date("Y", strtotime($sampleRow['DateRanked']));
    $isLoved = $sampleRow["Status"] == 4;
    require '../header.php';

    if($mapset_id == -1){
        siteRedirect();
    }

    $stmt = $conn->prepare("SELECT Count(*) FROM `ratings` WHERE BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID=?) ORDER BY date DESC;");
    $stmt->bind_param("s", $mapset_id);
    $stmt->execute();
    $numberOfSetRatings = $stmt->get_result()->fetch_row()[0];

    $stmt = $conn->prepare("SELECT Count(*) FROM comments WHERE SetID = ?;");
    $stmt->bind_param("s", $mapset_id);
    $stmt->execute();
    $commentCount = $stmt->get_result()->fetch_row()[0];

    // This will be set to true if during the display of difficulties,
    // a blocked one appears. This is so we can display a message near
    // the comment box.
    $hasBlacklistedDifficulties = false;
?>

<style>
    table, th, td, tr {
        border-collapse: collapse;
        padding: 0.25em;
        margin: 0;
    }

    th {
        height: 1em;
        text-align:left;
    }

    .text-center {
        text-align: center;
    }

    .dark-bg {
        background-color: #203838;
    }

    .light-bg {
        background-color: DarkSlateGray;
    }
</style>

<center><h1><a target="_blank" rel="noopener noreferrer" href="https://osu.ppy.sh/s/<?php echo $sampleRow['SetID']; ?>"><?php echo $sampleRow['Artist'] . " - " . htmlspecialchars($sampleRow['Title']) . "</a> by <a href='/profile/{$sampleRow['SetCreatorID']}'>" .  GetUserNameFromId($sampleRow['SetCreatorID'], $conn); ?></a></h1></center>

<div class="flex-container">
    <div class="flex-child">
        <img src="https://assets.ppy.sh/beatmaps/<?php echo $sampleRow['SetID']; ?>/covers/cover.jpg" style="width:25rem;height:8.5em;" onerror="this.onerror=null; this.src='INF.png';" />
    </div>
    <div class="flex-container flex-child light-bg" style="margin-left:1em;flex-grow: 1;height:8.5em;">
        <div class="flex-child" style="width:50%;margin:0;box-sizing:border-box;flex-wrap:wrap;">
            <div style="background-color:#203838;flex-basis: 100%;width:100%;padding:0.25em;box-sizing: border-box;">Mapset info</div>
            <div style="padding:0.25em;">
                <?php
                if ($isLoved)
                    echo "Submitted: ";
                else
                    echo "Ranked: ";
                echo date("M jS, Y", strtotime($sampleRow['DateRanked']));
                ?>
                <br>
                <?php
                    $stmt = $conn->prepare("SELECT ROUND(AVG(Score), 2) FROM `ratings` WHERE BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID=?)");
                    $stmt->bind_param("s", $mapset_id);
                    $stmt->execute();
                    $averageRating = $stmt->get_result()->fetch_row()[0];
                    $stmt->close();
                ?>

                Average Rating: <b><?php echo $averageRating; ?></b> <span style="font-size:12px;color:grey;">/ 5.00 from <?php echo $numberOfSetRatings; ?> votes</span><br>
                <?php echo getLanguage($sampleRow["Lang"]) . " " .  getGenre($sampleRow["Genre"]); ?> <br>

                <?php
                    if ($isLoved)
                        echo "Loved Mapset";
                ?>
            </div>
        </div>
        <div class="flex-child" style="width:50%;margin:0;border-left:2px solid #203838;box-sizing:border-box;flex-wrap:wrap;">
            <div style="background-color:#203838;flex-basis: 100%;width:100%;padding:0.25em;box-sizing: border-box;">Nominators</div>
            <?php
            $stmt = $conn->prepare("SELECT * FROM beatmapset_nominators WHERE SetID = ?");
            $stmt->bind_param("i", $mapset_id);
            $stmt->execute();
            $nominatorResult = $stmt->get_result();

            $nominators = array();
            while ($row = $nominatorResult->fetch_assoc()) {
                $nominatorID = $row["NominatorID"];
                $nominatorName = GetUserNameFromId($nominatorID, $conn);
                $mode = $row["Mode"];

                if (!isset($nominators[$mode])) {
                    $nominators[$mode] = array();
                }
                $nominators[$mode][$nominatorID] = $nominatorName;
            }

            if (!empty($nominators)) {
                echo "<table>";
                foreach ($nominators as $mode => $modeNominators) {
                    $modeString = getModeIcon($mode);

                    echo "<tr><td class='text-center' style='vertical-align: middle;'>$modeString</td><td style='width:100%;vertical-align: middle;'>";
                    $nominatorLinks = array();
                    foreach ($modeNominators as $nominatorID => $nominatorName) {
                        $nominatorLinks[] = "<a href='/profile/$nominatorID'><img src='https://s.ppy.sh/a/$nominatorID' style='height:24px;width:24px;' title='$nominatorName'></a>
                                     <a href='/profile/$nominatorID'>$nominatorName</a>";
                    }
                    echo implode(" ", $nominatorLinks);
                    echo "</td></tr>";
                }
                echo "</table>";
            } else if (!$isLoved) {
                echo "No nominators found! This is likely because this is a old set, ranked during moddingv1.<br><a href='edit/?id={$mapset_id}'><span class='subText'><i class='icon-edit'></i> Feel free to help by deducing nominators.</span></a> ";
            }
            ?>
        </div>
    </div>
</div>
<br>
<hr style="margin-bottom:1em;">

<?php
while($row = $result->fetch_assoc()) {
    $stmt = $conn->prepare("SELECT * FROM `ratings` WHERE `BeatmapID` = ? AND `UserID` = ?");
    $stmt->bind_param("ii", $row["BeatmapID"], $userId);
    $stmt->execute();
    $ratedQueryResult = $stmt->get_result();

    $userHasRatedThis = $ratedQueryResult->num_rows == 1;
    $userMapRating = $ratedQueryResult->fetch_row()[3] ?? -1;

    $stmt = $conn->prepare("SELECT `Score`, COUNT(*) as count, SUM(u.Weight) as WeightedCount FROM `ratings` JOIN users u on ratings.UserID = u.UserID WHERE `BeatmapID` = ? GROUP BY `Score`");
    $stmt->bind_param("i", $row["BeatmapID"]);
    $stmt->execute();
    $ratingResult = $stmt->get_result();

    $blackListed = $row["Blacklisted"] == 1;
    $hasCharted = $ratingResult->num_rows > 0 && $row["ChartYearRank"] != null;

    // Why do I need to do this here and not on the profile rating distribution chart. I don't get it
    $ratingCounts = array();
    $ratingCounts['0.0'] = 0;
    $ratingCounts['0.5'] = 0;
    $ratingCounts['1.0'] = 0;
    $ratingCounts['1.5'] = 0;
    $ratingCounts['2.0'] = 0;
    $ratingCounts['2.5'] = 0;
    $ratingCounts['3.0'] = 0;
    $ratingCounts['3.5'] = 0;
    $ratingCounts['4.0'] = 0;
    $ratingCounts['4.5'] = 0;
    $ratingCounts['5.0'] = 0;

    $totalRatings = 0;
    $averageRating = 0;

    while ($ratingRow = $ratingResult->fetch_assoc()) {
        $ratingCounts[$ratingRow['Score']] = $ratingRow['WeightedCount'];
        $totalRatings += $ratingRow['count'];
    }

    $maxRating = max(max($ratingCounts), 5);

    if ($totalRatings > 0) {
        $stmt = $conn->prepare("SELECT SUM(r.Score * u.Weight) / SUM(u.Weight) AS avg_score
                                        FROM ratings r
                                        JOIN users u ON r.UserID = u.UserID
                                        WHERE r.BeatmapID = ?;");
        $stmt->bind_param("i", $row["BeatmapID"]);
        $stmt->execute();
        $averageRating = number_format($stmt->get_result()->fetch_assoc()["avg_score"], 2);
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count, AVG(Score) as avg FROM `ratings` WHERE `BeatmapID`=? AND `UserID` IN (SELECT `UserIDTo` FROM `user_relations` WHERE `UserIDFrom` = ? AND `type`=1)");
    $stmt->bind_param("ii", $beatmapID, $userID);
    $beatmapID = $row["BeatmapID"];
    $userID = $userId;
    $stmt->execute();
    $friendRatingResult = $stmt->get_result()->fetch_assoc();
    $friendRatingCount = $friendRatingResult["count"];
    $friendRatingAvg = $friendRatingResult["avg"];

    $hasFriendsRatings = $loggedIn && $friendRatingCount > 0;

    $stmt = $conn->prepare("SELECT d.DescriptorID, d.Name
                                          FROM descriptor_votes 
                                          JOIN descriptors d on descriptor_votes.DescriptorID = d.DescriptorID
                                          WHERE BeatmapID = ?
                                          GROUP BY DescriptorID
                                          HAVING SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) > SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END)
                                          ORDER BY (SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) - SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END)) DESC, DescriptorID
                                          LIMIT 10;");
    $stmt->bind_param("i", $beatmapID);
    $stmt->execute();
    $descriptorResult = $stmt->get_result();
?>

    <div class="flex-container difficulty-container alternating-bg <?php if($blackListed) echo "faded"; ?>" >
        <div class="flex-child diffBox" style="text-align:center;width:20%;">
            <span style="position:relative;top:2px;">
                <?php echo getModeIcon($row['Mode']); ?>
            </span>
            <a href="https://osu.ppy.sh/b/<?php echo $row['BeatmapID']; ?>" target="_blank" rel="noopener noreferrer" <?php if ($row["ChartRank"] <= 250 && !is_null($row["ChartRank"])){ echo "class='bolded'"; }?>>
                <?php echo mb_strimwidth(htmlspecialchars($row['DifficultyName']), 0, 35, "..."); ?>
            </a>
            <a href="osu://b/<?php echo $row['BeatmapID']; ?>"><i class="icon-download-alt">&ZeroWidthSpace;</i></a>
            <span class="subText"><?php echo number_format((float)$row['SR'], 2, '.', ''); ?>*</span>
            <br>
            <?php
                $creatorStmt = $conn->prepare("SELECT CreatorID FROM beatmap_creators WHERE BeatmapID = ?");
                $creatorStmt->bind_param('i', $row['BeatmapID']);
                $creatorStmt->execute();
                $creatorsResult = $creatorStmt->get_result();
                $creators = [];

                while ($creator = $creatorsResult->fetch_assoc())
                    $creators[] = $creator['CreatorID'];

                if (!(in_array($row['SetCreatorID'], $creators) && count($creators) == 1)) {
                    ?>
                    <span class="subText">mapped by <?php RenderBeatmapCreators($row['BeatmapID'], $conn); ?></span>
                    <?php
                }
            ?>
        </div>
        <?php if (!$blackListed) { ?>
            <div class="flex-child diffBox" style="width:0;text-align:center;">
                <?php
                if($totalRatings > 0){
                    ?>
                    <div class="mapsetRankingDistribution">
                        <div class="mapsetRankingDistributionBar" style="height: <?php echo ($ratingCounts["5.0"]/$maxRating)*90; ?>%;"></div>
                        <div class="mapsetRankingDistributionBar" style="height: <?php echo ($ratingCounts["4.5"]/$maxRating)*90; ?>%;"></div>
                        <div class="mapsetRankingDistributionBar" style="height: <?php echo ($ratingCounts["4.0"]/$maxRating)*90; ?>%;"></div>
                        <div class="mapsetRankingDistributionBar" style="height: <?php echo ($ratingCounts["3.5"]/$maxRating)*90; ?>%;"></div>
                        <div class="mapsetRankingDistributionBar" style="height: <?php echo ($ratingCounts["3.0"]/$maxRating)*90; ?>%;"></div>
                        <div class="mapsetRankingDistributionBar" style="height: <?php echo ($ratingCounts["2.5"]/$maxRating)*90; ?>%;"></div>
                        <div class="mapsetRankingDistributionBar" style="height: <?php echo ($ratingCounts["2.0"]/$maxRating)*90; ?>%;"></div>
                        <div class="mapsetRankingDistributionBar" style="height: <?php echo ($ratingCounts["1.5"]/$maxRating)*90; ?>%;"></div>
                        <div class="mapsetRankingDistributionBar" style="height: <?php echo ($ratingCounts["1.0"]/$maxRating)*90; ?>%;"></div>
                        <div class="mapsetRankingDistributionBar" style="height: <?php echo ($ratingCounts["0.5"]/$maxRating)*90; ?>%;"></div>
                        <div class="mapsetRankingDistributionBar" style="height: <?php echo ($ratingCounts["0.0"]/$maxRating)*90; ?>%;"></div>
                    </div>
                    <span class="subText" style="width:100%;">Rating Distribution</span>
                    <?php
                }
                ?>
            </div>
            <div class="flex-child diffBox" style="text-align:right;width:25%;">
                <?php
                $averageRating = number_format($averageRating, 2);
                if ($totalRatings > 0) {
                    ?>
                    Rating: <b><?php echo $averageRating; ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $totalRatings; ?></span> votes</span><br>
                    <?php
                }
                if ($hasFriendsRatings) {
                    ?>
                    Friend Rating: <b style="color:#e79ac1;"><?php echo number_format($friendRatingAvg, 2); ?></b> <span class="subText">/ 5.00 from <span style="color:white"><?php echo $friendRatingCount; ?></span> votes</span><br>
                    <?php
                }
                if($hasCharted) {
                    ?>
                    Ranking: <b>#<?php echo $row["ChartYearRank"]; ?></b> for <a href="/charts/?y=<?php echo $year;?>&p=<?php echo ceil($row["ChartYearRank"] / 50); ?>"><?php echo $year;?></a>, <b>#<?php echo $row["ChartRank"]; ?></b> <a href="/charts/?y=all-time&p=<?php echo ceil($row["ChartRank"] / 50); ?>">overall</a><br>
                    <?php
                }
                ?>
                <span class="map-descriptors">
					<table style="margin-left: auto;">
						<tr>
							<th style="padding:0;">
								<span class="subText" style="font-weight:normal;">
									<?php
                                    $descriptorLinks = array();
                                    while($descriptor = $descriptorResult->fetch_assoc()){
                                        $descriptorLink = '<a style="color:inherit;" href="../descriptor/?id=' . $descriptor["DescriptorID"] . '">' . $descriptor["Name"] . '</a>';
                                        $descriptorLinks[] = $descriptorLink;
                                    }
                                    echo implode(', ', $descriptorLinks);
                                    ?>
								</span>
							</th>
                            <?php if ($loggedIn) { ?>
							<th style="padding:0;padding-left:0.5em;">
								<a href="descriptor-vote/?id=<?php echo $row["BeatmapID"]; ?>"><i class="icon-plus"></i></a>
							</th>
                            <?php } ?>
						</tr>
					</table>
                </span>
            </div>
            <div class="flex-child diffBox" style="width:5%;text-align:left;">
                <?php
                if($loggedIn){
                    $selectStmt = $conn->prepare("SELECT GROUP_CONCAT(Tag SEPARATOR ', ') AS AllTags FROM rating_tags WHERE UserID = ? AND BeatmapID = ?");
                    $selectStmt->bind_param("ii", $userId, $beatmapID);
                    $selectStmt->execute();
                    $tags_result = $selectStmt->get_result();
                    $tags_row = $tags_result->fetch_assoc();
                    $allTags = htmlspecialchars($tags_row['AllTags'], ENT_COMPAT, "ISO-8859-1");
                    $selectStmt->close();
                    ?>
                    <span class="identifier" style="display: inline-block;">
                        <ol class="star-rating-list <?php if(!$userHasRatedThis) { echo 'unrated'; } ?>" beatmapid="<?php echo $row["BeatmapID"]; ?>" rating="<?php echo $userMapRating; ?>">
                            <li class="icon-remove" style="opacity:0;"></li>
                            <?php for ($i = 1; $i <= 5; $i++){ ?>
                                <li class="star icon-star<?php
                                if ($userMapRating == ($i - 0.5)) {
                                    echo '-half-empty';
                                } else if ($userMapRating < $i) {
                                    echo '-empty';
                                }
                                ?>" value="<?php echo $i; ?>"></li>
                            <?php } ?>
                        </ol>
                    </span>
                    <span class="starRemoveButton <?php if(!$userHasRatedThis) { echo 'disabled'; } ?>" beatmapid="<?php echo $row["BeatmapID"]; ?>"><i class="icon-remove"></i></span>
                    <span class="star-value<?php if(!$userHasRatedThis) echo ' unrated';  ?>"><?php if($userHasRatedThis) echo $userMapRating; else echo '&ZeroWidthSpace;';  ?></span>
                    <select class="star-rating-list-mobile" beatmapid="<?php echo $row["BeatmapID"]; ?>">
                        <option value="-2" <?php if ($userMapRating == -1) echo "selected"; ?>>...</option>
                        <?php for ($i = 0; $i <= 5; $i += 0.5) {
                            $selected = $userMapRating == $i ? "selected" : "";
                            echo "<option value='{$i}' {$selected}>{$i}</option>";
                        } ?>
                    </select>
                    <div style="overflow:hidden;text-overflow:ellipsis;">
                        <span class="subText tags" beatmapid="<?php echo $row["BeatmapID"]; ?>"><?php echo $allTags; ?></span>
                    </div>
                    <?php
                } else {
                    echo 'Log in to rate maps!';
                }
                ?>
            </div>

            <div class="flex-child diffBox" style="text-align: right;width:0%;display: contents;">
                <?php
                    if($loggedIn) { ?>
                <span class="tag-button" style="min-width: 1em;padding-right:1em;cursor:pointer;" beatmapid="<?php echo $row["BeatmapID"]; ?>"><i class="icon-ellipsis-vertical"></i></span>
                <?php } ?>
            </div>

            <div style="position:absolute;right:20%;padding:0;width:0;height: 0;display:none;" beatmapid="<?php echo $row["BeatmapID"]; ?>">
                <div class="tag-input" style="left:0.5em;bottom:-2.6em;padding:0.5em;position:absolute;background-color:DarkSlateGrey;min-width:16em;min-height:4em;text-align:center;display:flex;flex-direction:column;align-items: center;">
                    <div>
                        <input class="tag-input-field" style="padding:0;margin: 0 0.5em 0 0;width:10em;" value="<?php echo $allTags;?>">
                        <button class="tag-input-submit" style="min-width:0;">Save</button><br>
                    </div>
                    <span class="subText">separate your tags with commas</span>
                </div>
            </div>

        <?php
        } else { ?>
            <div class="flex-child diffBox" style="width:50%;">
                <b>This difficulty has been blacklisted from OMDB.</b><br>
                Reason: <?php echo $row["BlacklistReason"]; ?>
                <?php $hasBlacklistedDifficulties = true; ?>
            </div>
        <?php } ?>
    </div>
    <?php
}
?>

<script>
    const tagButtons = document.querySelectorAll('.tag-button');
    tagButtons.forEach(button => {
        button.addEventListener('click', () => {
            const beatmapID = button.getAttribute('beatmapid');
            const tagInputDiv = document.querySelector(`div[beatmapid="${beatmapID}"]`);

            tagInputDiv.style.display = (tagInputDiv.style.display === 'none') ? 'block' : 'none';
        });
    });

    const tagInputSubmitButtons = document.querySelectorAll('.tag-input-submit');
    tagInputSubmitButtons.forEach(button => {
        button.addEventListener('click', () => {
            const beatmapID = button.parentNode.parentNode.parentNode.getAttribute('beatmapid');
            const tagInputField = document.querySelector(`div[beatmapid="${beatmapID}"] input.tag-input-field`);
            const tagsSpan = document.querySelector(`span.tags[beatmapid="${beatmapID}"]`);
            const tags = tagInputField.value;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'SubmitTags.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE) {
                    if (xhr.status === 200) {
                        console.log(xhr.responseText);
                        tagsSpan.textContent = tags;
                    } else {
                        console.error('Error: ' + xhr.status);
                    }
                }
            };

            xhr.send(`tags=${encodeURIComponent(tags)}&beatmapID=${beatmapID}`);
        });
    });
</script>

<div style="margin-top: 2em;">
    <?php if ($loggedIn) { ?>
        <a href="edit/?id=<?php echo $mapset_id; ?>"><span class="subText"><i class="icon-edit"></i> Propose edit</span></a>
    <?php } ?>
</div>
<hr style="margin-bottom:1em;margin-top: 0">

<div class="flex-container column-when-mobile-container">
    <div class="flex-child column-when-mobile" style="width:40%;">
        Latest Ratings<br><br>
        <div id="setRatingsDisplay">
            <?php
            require 'ratings.php';
            ?>
        </div>
    </div>
    <div class="flex-child column-when-mobile" style="width:60%;">
        Comments (<?php echo $commentCount; ?>)<br><br>
        <div class="flex-container commentContainer" style="width:100%;">

            <?php if($loggedIn) { ?>
                <div class="flex-child commentComposer">
                    <form>
                        <textarea id="commentForm" name="commentForm" placeholder="Write your comment here!" value="" autocomplete='off'></textarea>
                        <a href="/rules/" target="_blank" rel="noopener noreferrer"><i class="icon-book"></i> Rules</a>
                        <input type='button' name="commentSubmit" id="commentSubmit" value="Post" onclick="submitComment()" />
                    </form>
                    <?php if ($hasBlacklistedDifficulties) { ?>
                        <p style="font-weight: bolder;">
                            This mapset contains blacklisted difficulties. Do not comment what you'd rate it, please respect the mapper's wishes!
                        </p>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php
            $stmt = $conn->prepare("SELECT * FROM `comments` WHERE SetID=? ORDER BY date DESC");
            $stmt->bind_param("s", $sampleRow["SetID"]);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows != 0) {
                while ($row = $result->fetch_assoc()) {
                    $is_blocked = 0;

                    if ($loggedIn) {
                        $stmt_relation_to_profile_user = $conn->prepare("SELECT * FROM user_relations WHERE UserIDFrom = ? AND UserIDTo = ? AND type = 2");
                        $stmt_relation_to_profile_user->bind_param("ii", $userId, $row["UserID"]);
                        $stmt_relation_to_profile_user->execute();
                        $is_blocked = $stmt_relation_to_profile_user->get_result()->num_rows > 0;
                    }

                    ?>
                    <div class="flex-container flex-child commentHeader">
                        <div class="flex-child <?php if ($is_blocked) echo "faded"; ?>" style="height:24px;width:24px;">
                            <a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
                        </div>
                        <div class="flex-child <?php if ($is_blocked) echo "faded"; ?>">
                            <a href="/profile/<?php echo $row["UserID"]; ?>"><?php echo GetUserNameFromId($row["UserID"], $conn); ?></a>
                        </div>
                        <div class="flex-child" style="margin-left:auto;">
                            <?php
                            if ($loggedIn && $userName == "moonpoint") { ?>
                                <i class="icon-magic scrubComment" style="color:#f94141;cursor: pointer;" value="<?php echo $row["CommentID"]; ?>"></i>
                            <?php }
                            if ($row["UserID"] == $userId) { ?>
                                <i class="icon-remove removeComment" style="color:#f94141;" value="<?php echo $row["CommentID"]; ?>"></i>
                            <?php }
                            echo GetHumanTime($row["date"]); ?>
                        </div>
                    </div>
                    <div class="flex-child comment" style="min-width:0;overflow: hidden;">
                        <?php
                            if (!$is_blocked)
                                echo "<p>" . ParseCommentLinks($conn, nl2br(htmlspecialchars($row["Comment"], ENT_COMPAT, "ISO-8859-1"))) . "</p>";
                            else
                                echo "<p>[blocked comment]</p>";
                        ?>
                    </div>
                    <?php
                }
            }
            ?>

        </div>
    </div>
</div>

<script>
    function submitComment(){
        console.log("yeah");
        var text = $('#commentForm').val();
        console.log(text);

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                location.reload();
            }
        };

        if (text.length > 3 && text.length < 8000){
            $('#commentSubmit').prop('disabled', true);
            xhttp.open("POST", "SubmitComment.php", true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send("sID=" + <?php echo $sampleRow["SetID"]; ?> + "&comment=" + encodeURIComponent(text));
        }
    }

    $('#commentForm').keydown(function (event) {
        if ((event.keyCode == 10 || event.keyCode == 13) && event.ctrlKey)
            submitComment();
    });

    $('.tag-input-field').keydown(function (event) {
        if ((event.keyCode == 10 || event.keyCode == 13) && event.ctrlKey)
            $(this).parent().find('.tag-input-submit').click();
    });

    $(".removeComment").click(function(event){
        var $this = $(this);

        if (!confirm("Are you sure you want to remove this comment?")) {
            return;
        }

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                console.log(this.responseText);
                location.reload();
            }
        };

        xhttp.open("POST", "RemoveComment.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("sID=" + <?php echo $sampleRow["SetID"]; ?> + "&cID=" + $this.attr('value'));
    });

    $(".scrubComment").click(function(event){
        var $this = $(this);

        if (!confirm("Are you sure you want to scrub this comment?")) {
            return;
        }

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                console.log(this.responseText);
                location.reload();
            }
        };

        xhttp.open("POST", "ScrubComment.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("sID=" + <?php echo $sampleRow["SetID"]; ?> + "&cID=" + $this.attr('value'));
    });

    function setStarRatingDisplay(element, value) {
        var $this = $(element);
        var $options = $this.find(".star");

        for (var i = 0; i < 5; i++) {
            if (i < value) {
                if (value-0.5 === i) {
                    $options.eq(i).attr('class', 'star icon-star-half-empty');
                } else {
                    $options.eq(i).attr('class', 'star icon-star');
                }
            } else {
                $options.eq(i).attr('class', 'star icon-star-empty');
            }
        }
    }

    $(".star-rating-list").mousemove(function(event){
        var $this = $(this);
        var sel = event.target.value;
        var rating = 0;

        for (var i = 0; i < 5; i++) {
            if (i < sel) {
                if (event.pageX - event.target.getBoundingClientRect().left<= 6 && sel-1 === i) {
                    rating += 0.5;
                } else {
                    rating += 1;
                }
            }
        }

        setStarRatingDisplay(this, rating);
        $this.parent().parent().find('.star-value').html(rating.toFixed(1));
    });

    $(".star-rating-list").mouseleave(function(event){
        var $this = $(this);
        var sel = $this.attr("rating");

        setStarRatingDisplay(this, sel);

        if (sel == -1)
            $this.parent().parent().find('.star-value').html("&ZeroWidthSpace;");
        else
            $this.parent().parent().find('.star-value').html(sel);

    });

    function submitRating(bID, rating, callback) {
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4) {
                if (this.status == 200) {
                    callback(null, this.responseText);
                } else {
                    callback(new Error('Request failed'));
                }
            }
        };

        xhttp.open("POST", "SubmitRating.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("bID=" + bID + "&rating=" + rating);
    }

    $(".starRemoveButton").click(function(event){
        var $this = $(this);
        var bID = $(this).attr("beatmapid");


        $this.parent().addClass("faded");
        submitRating(bID, -2, function(error, response) {
            if (!error) {
                $this.addClass("disabled");
                $this.parent().removeClass("faded");
                $this.parent().find('.star-value').html("&ZeroWidthSpace;");
                $this.parent().find('.star-value').addClass("unrated");
                $this.parent().find('.star-rating-list').attr("rating", "");
                $this.parent().find('.identifier').find('.star-rating-list').addClass("unrated");
                setStarRatingDisplay($this.parent().find('.star-rating-list'), -2);
            } else {
                console.error(error);
            }
        });

    });

    $(".star-rating-list").click(function(event){
        var $this = $(this);
        var bID = $(this).attr("beatmapid");
        var sel = event.target.value;
        var rating = 0;

        for (var i = 0; i < 5; i++) {
            if (i < sel) {
                if (event.pageX - event.target.getBoundingClientRect().left <= 6 && sel-1 === i) {
                    rating += 0.5;
                } else {
                    rating += 1;
                }
            }
        }

        $this.attr("rating", rating.toFixed(1));
        $this.parent().addClass("faded");
        submitRating(bID, rating, function(error, response) {
            if (!error) {
                $this.removeClass("unrated");
                $this.parent().removeClass("faded");
                $this.parent().parent().find('.star-value').removeClass("unrated");
                $this.parent().parent().find('.star-value').html(rating.toFixed(1));
                $this.parent().parent().find('.starRemoveButton').removeClass("disabled");
            } else {
                console.error(error);
            }
        });
    });

    $(".star-rating-list-mobile").change(function(event) {
        var $this = $(this);
        var bID = $this.attr("beatmapid");
        var rating = parseFloat($this.val());

        submitRating(bID, rating, function(error, response) {
            if (!error) {
                console.log(response);
            } else {
                console.error(error);
            }
        });
    });
</script>

<?php
require '../footer.php';
?>
