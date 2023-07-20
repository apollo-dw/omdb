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

<center><h1><a target="_blank" rel="noopener noreferrer" href="https://osu.ppy.sh/s/<?php echo $sampleRow['SetID']; ?>"><?php echo $sampleRow['Artist'] . " - " . htmlspecialchars($sampleRow['Title']) . "</a> by <a href='/profile/{$sampleRow['SetCreatorID']}'>" .  GetUserNameFromId($sampleRow['SetCreatorID'], $conn); ?></a></h1></center>

<div class="flex-container" style="justify-content: center;">
    <div class="flex-child">
        <img src="https://assets.ppy.sh/beatmaps/<?php echo $sampleRow['SetID']; ?>/covers/cover.jpg" style="height:6rem;width:21.6rem;border-radius:16px;" onerror="this.onerror=null; this.src='INF.png';" />
    </div>
    <div class="flex-child">
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
        <?php
            if ($isLoved)
                echo "Loved Mapset";
        ?>
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

    $stmt = $conn->prepare("SELECT `Score`, COUNT(*) as count FROM `ratings` WHERE `BeatmapID` = ? GROUP BY `Score`");
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

    while ($ratingRow = $ratingResult->fetch_assoc()) {
        $ratingCounts[$ratingRow['Score']] = $ratingRow['count'];
    }

    $maxRating = max(max($ratingCounts), 5);

    $totalRatings = 0;
    $averageRating = 0;

    foreach ($ratingCounts as $rating => $count)
        $totalRatings += $count;

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
    ?>

    <div class="flex-container difficulty-container alternating-bg <?php if($blackListed){ echo "faded"; }?>" >
        <div class="flex-child diffBox" style="text-align:center;width:40%;">
            <a href="https://osu.ppy.sh/b/<?php echo $row['BeatmapID']; ?>" target="_blank" rel="noopener noreferrer" <?php if ($row["ChartRank"] <= 250 && !is_null($row["ChartRank"])){ echo "class='bolded'"; }?>>
                <?php echo mb_strimwidth(htmlspecialchars($row['DifficultyName']), 0, 35, "..."); ?>
            </a>
            <a href="osu://b/<?php echo $row['BeatmapID']; ?>"><i class="icon-download-alt">&ZeroWidthSpace;</i></a>
            <span class="subText"><?php echo number_format((float)$row['SR'], 2, '.', ''); ?>*</span>
            <br>
            <?php
                $creatorStmt = $conn->prepare("SELECT IF(COUNT(*) = 1 AND CreatorID = ?, 1, 0) AS IsOnlyOne FROM beatmap_creators WHERE BeatmapID = ? GROUP BY CreatorID;");
                $creatorStmt->bind_param('ii', $row['SetCreatorID'], $row['BeatmapID']);
                $creatorStmt->execute();
                $didCreatorIDMapThis = $creatorStmt->get_result()->fetch_assoc()["IsOnlyOne"];

                if (!$didCreatorIDMapThis) {
                    ?> <span class="subText">mapped by <?php RenderBeatmapCreators($row['BeatmapID'], $conn); ?></span> <?php
                }
            ?>
        </div>
        <?php if (!$blackListed) { ?>
            <div class="flex-child diffBox" style="width:15%;text-align:center;">
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
            <div class="flex-child diffBox" style="text-align:right;width:40%;">
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
                    Ranking: <b>#<?php echo $row["ChartYearRank"]; ?></b> for <a href="/charts/?y=<?php echo $year;?>&p=<?php echo ceil($row["ChartYearRank"] / 50); ?>"><?php echo $year;?></a>, <b>#<?php echo $row["ChartRank"]; ?></b> <a href="/charts/?y=all-time&p=<?php echo ceil($row["ChartRank"] / 50); ?>">overall</a>
                    <?php
                }
                ?>
            </div>
            <div class="flex-child diffBox" style="width:20%;">
                <?php
                if($loggedIn){
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
                    <span class="starRemoveButton <?php if(!$userHasRatedThis) { echo 'disabled'; } ?>" beatmapid="<?php echo $row["BeatmapID"]; ?>"><i class="icon-remove"></i></span><span style="display: inline-block; padding-left:0.25em;" class="star-value <?php if(!$userHasRatedThis) { echo 'unrated'; } ?>"><?php if($userHasRatedThis){ echo $userMapRating; } else { echo '&ZeroWidthSpace;'; } ?></span>
                    <?php
                } else {
                    echo 'Log in to rate maps!';
                }
                ?>
            </div>

            <div class="flex-child diffBox" style="overflow:hidden;text-align: center;width:10%;display: flex; align-items: center;">
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
                <div style="overflow:hidden;text-overflow:ellipsis;">
                    <span class="subText tags" beatmapid="<?php echo $row["BeatmapID"]; ?>"><?php echo $allTags; ?></span>
                </div>
                <div style="margin-left:auto;padding-left:0.5em;">
                    <span class="tag-button" style="min-width: 3em;cursor:pointer;" beatmapid="<?php echo $row["BeatmapID"]; ?>"><i class="icon-tags"></i></span>
                </div>
                <?php
                }
                ?>
            </div>

            <div style="position:relative;padding:0;width:0;height: 0;display:none;" beatmapid="<?php echo $row["BeatmapID"]; ?>">
                <div class="tag-input" style="left:0.5em;bottom:-2.6em;padding:0.5em;position:absolute;background-color:DarkSlateGrey;min-width:12em;min-height:4em;text-align:center;display:flex;flex-direction:column;align-items: center;">
                    <div>
                        <input class="tag-input-field" style="padding:0;margin: 0 0.5em 0 0;width:6em;" value="<?php echo $allTags;?>">
                        <button class="tag-input-submit" style="min-width:0;">Save</button><br>
                    </div>
                    <span class="subText">separate your tags with commas</span>
                </div>
            </div>

        <?php
        } else { ?>
            <div class="flex-child diffBox" style="width:91%;">
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

<hr style="margin-bottom:1em;margin-top:1em;">

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
                            <?php if ($row["UserID"] == $userId) { ?> <i class="icon-remove removeComment" style="color:#f94141;" value="<?php echo $row["CommentID"]; ?>"></i> <?php } echo GetHumanTime($row["date"]); ?>
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

    $(".removeComment").click(function(event){
        var $this = $(this);

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

    $(".star-rating-list").mousemove(function(event){
        var $this = $(this);
        var sel = event.target.value;
        var $options = $this.find(".star");
        var rating = 0;

        for (var i = 0; i < 5; i++) {
            if (i < sel) {
                if (event.pageX - event.target.getBoundingClientRect().left<= 6 && sel-1 == i) {
                    $options.eq(i).attr('class', 'star icon-star-half-empty');
                    rating += 0.5;
                } else {
                    $options.eq(i).attr('class', 'star icon-star');
                    rating += 1;
                }
            } else {
                $options.eq(i).attr('class', 'star icon-star-empty');
            }
        }
        $this.parent().parent().find('.star-value').html(rating.toFixed(1));
    });

    $(".star-rating-list").mouseleave(function(event){
        var $this = $(this);
        var sel = $this.attr("rating");
        var $options = $this.find(".star");

        for (var i = 0; i < 5; i++) {
            if (i < sel) {
                if (sel-0.5 == i) {
                    $options.eq(i).attr('class', 'star icon-star-half-empty');
                } else {
                    $options.eq(i).attr('class', 'star icon-star');
                }
            } else {
                $options.eq(i).attr('class', 'star icon-star-empty');
            }
        }

        if (sel == -1){
            $this.parent().parent().find('.star-value').html("&ZeroWidthSpace;");
        }else{
            $this.parent().parent().find('.star-value').html(sel);
        }
    });

    $(".starRemoveButton").click(function(event){
        var $this = $(this);
        var bID = $(this).attr("beatmapid");

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                console.log(this.responseText);

                $this.addClass("disabled");
                $this.parent().find('.star-value').html("&ZeroWidthSpace;");
                $this.parent().find('.star-value').addClass("unrated");
                $this.parent().find('.identifier').find('.star-rating-list').addClass("unrated");
            }
        };

        $this.attr("rating", "");
        xhttp.open("POST", "SubmitRating.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("bID=" + bID + "&rating=" + -2);
        $this.parent().find('.star-value').html("removing...");

    });

    $(".star-rating-list").click(function(event){
        var $this = $(this);
        var bID = $(this).attr("beatmapid");
        var sel = event.target.value;
        var rating = 0;

        for (var i = 0; i < 5; i++) {
            if (i < sel) {
                if (event.pageX - event.target.getBoundingClientRect().left <= 6 && sel-1 == i) {
                    rating += 0.5;
                } else {
                    rating += 1;
                }
            }
        }

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                console.log(this.responseText);

                $this.removeClass("unrated");
                $this.parent().parent().find('.star-value').removeClass("unrated");
                $this.parent().parent().find('.star-value').html(rating.toFixed(1));
                $this.parent().parent().find('.starRemoveButton').removeClass("disabled");
            }
        };

        $this.attr("rating", rating.toFixed(1));
        xhttp.open("POST", "SubmitRating.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("bID=" + bID + "&rating=" + rating);
        $this.parent().parent().find('.star-value').html("rating...");

    });
</script>

<?php
require '../footer.php';
?>
