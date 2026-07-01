<?php
    require "../base.php";

    $newsId = GetIntParam('id', -1, "Y U POST CRINGE");

    $stmt = $conn->prepare("SELECT n.NewsID, n.Title, n.Content, n.DateCreated, n.DateEdited, n.AuthorID
        FROM news_posts n
        WHERE n.NewsID = ?
    ");
    $stmt->bind_param("i", $newsId);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (is_null($post))
        die("News post not found");

    $PageTitle = $post["Title"];
    require "../header.php";

    $postDate = date("M j, Y H:i", strtotime($post["DateCreated"]));
    $wasEdited = $post["DateEdited"] !== null;
    $editDate = $wasEdited ? date("M j, Y H:i", strtotime($post["DateEdited"])) : "";

    $stmt = $conn->prepare("SELECT
            COUNT(*) AS totalHearts,
            SUM(CASE WHEN UserID = ? THEN 1 ELSE 0 END) AS userLiked,
            GROUP_CONCAT(UserID) AS heartedUsers
        FROM news_hearts
        WHERE NewsID = ?
    ");
    $stmt->bind_param("ii", $userId, $newsId);
    $stmt->execute();
    $heartData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $newsHeartCount = (int)$heartData['totalHearts'];
    $userHasLikedNews = ((int)$heartData['userLiked']) > 0;

    $heartedUserIds = [];
    if (!empty($heartData['heartedUsers'])) {
        $heartedUserIds = array_map('intval', explode(',', $heartData['heartedUsers']));
    }
    $heartedUsernames = [];
    foreach ($heartedUserIds as $uid) {
        $heartedUsernames[] =
            "<span style='white-space: nowrap;'>" .
            safe_htmlspecialchars(GetUserNameFromId($uid, $conn), ENT_QUOTES) .
            "</span>";
    }
    $heartedUsernamesString = implode(", ", $heartedUsernames);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM news_comments WHERE NewsID = ?;");
    $stmt->bind_param("i", $newsId);
    $stmt->execute();
    $commentCount = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
?>

<div style="padding: 1em; background-color:darkslategray">
    <?php if ($loggedIn) { ?>
        <div class="tooltip-wrapper" style="float:right;">
            <span class="subText">[<?php echo $newsHeartCount; ?>]</span>

            <i
                id="news-heart-<?php echo $newsId; ?>"
                class="icon-heart<?php if (!$userHasLikedNews) echo "-empty"; ?> news-heart"
                data-news-id="<?php echo $newsId; ?>"
            ></i>
            <?php if ($heartedUsernamesString) { ?>
                <div class="tooltip-box">
                    <?php echo $heartedUsernamesString; ?>
                </div>
            <?php } ?>
        </div>
    <?php } ?>

    <h1><?php echo safe_htmlspecialchars($post["Title"], ENT_QUOTES); ?></h1>
    <span class="subText">
        Posted by
        <a href="/profile/<?php echo (int)$post["AuthorID"]; ?>">
            <?php echo safe_htmlspecialchars(GetUserNameFromId($post["AuthorID"], $conn), ENT_QUOTES); ?>
        </a>
        on <?php echo $postDate; ?>
        <?php if ($wasEdited): ?>
            | <b title="Last edited <?php echo $editDate; ?>">edited <?php echo $editDate; ?></b>
        <?php endif; ?>
    </span>
    <hr>
    <div style="line-height:1.6;word-break:break-word;">
        <?php echo ParseCommentLinks($conn, $post["Content"]); ?>
    </div>
</div>

<hr>
<br>

<div class="flex-child column-when-mobile">
    Comments (<?php echo $commentCount; ?>)<br><br>

    <div class="flex-container commentContainer" style="width:100%;">

        <?php if ($loggedIn) { ?>
            <div class="flex-child commentComposer">
                <form>
                    <textarea id="commentForm" name="commentForm" placeholder="Write your comment here!" value="" autocomplete='off'></textarea>
                    <a href="/rules/" target="_blank" rel="noopener noreferrer"><i class="icon-book"></i> Rules</a>
                    <input type='button' name="commentSubmit" id="commentSubmit" value="Post" onclick="submitComment()" />
                </form>
            </div>
        <?php } ?>

        <?php
        $stmt = $conn->prepare("SELECT CommentID, UserID, Comment, Timestamp FROM news_comments
                                WHERE NewsID = ?
                                ORDER BY Timestamp DESC");
        $stmt->bind_param("i", $newsId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $is_blocked = 0;

            if ($loggedIn) {
                $stmt_relation = $conn->prepare("SELECT * FROM user_relations WHERE UserIDFrom = ? AND UserIDTo = ? AND type = 2");
                $stmt_relation->bind_param("ii", $userId, $row["UserID"]);
                $stmt_relation->execute();
                $is_blocked = $stmt_relation->get_result()->num_rows > 0;
                $stmt_relation->close();
            }
            ?>
            <div class="flex-container flex-child commentHeader">
                <div class="flex-child <?php if ($is_blocked) echo "faded"; ?>" style="height:24px;width:24px;">
                    <a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo safe_htmlspecialchars(GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>"/></a>
                </div>
                <div class="flex-child <?php if ($is_blocked) echo "faded"; ?>">
                    <a href="/profile/<?php echo $row["UserID"]; ?>"><?php echo safe_htmlspecialchars(GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?></a>
                </div>
                <div class="flex-child" style="margin-left:auto;">
                    <?php
                    if ($loggedIn && $userName == "moonpoint") { ?>
                        <i class="icon-magic scrubComment" style="color:#f94141;cursor: pointer;" value="<?php echo $row["CommentID"]; ?>"></i>
                    <?php }
                    if ($row["UserID"] == $userId || $userName == "moonpoint") { ?>
                        <i class="icon-remove removeComment" style="color:#f94141;cursor:pointer;" value="<?php echo $row["CommentID"]; ?>"></i>
                    <?php }
                    echo GetHumanTime($row["Timestamp"]); ?>
                </div>
            </div>
            <div class="flex-child comment" style="min-width:0;overflow: hidden;">
                <?php
                if (!$is_blocked)
                    echo "<p>" . ParseCommentLinks($conn, $row["Comment"]) . "</p>";
                else
                    echo "<p>[blocked comment]</p>";
                ?>
            </div>
            <?php
        }
        $stmt->close();
        ?>
    </div>
</div>

<script>
    $('.news-heart').on('click', function() {
        const $el = $(this);
        const newsId = $el.data('news-id');

        $.ajax({
            type: 'POST',
            url: 'HeartNews.php',
            data: { bID: newsId },
            dataType: 'json',
            success: function(response) {
                if (response.state === 1)
                    $el.removeClass('icon-heart-empty').addClass('icon-heart');
                else if (response.state === 0)
                    $el.removeClass('icon-heart').addClass('icon-heart-empty');
            }
        });
    });

    function submitComment(){
        var text = $('#commentForm').val();

        if (text.length > 3 && text.length < 8000){
            $('#commentSubmit').prop('disabled', true);

            $.ajax({
                type: "POST",
                url: "SubmitComment.php",
                data: {
                    nID: <?php echo $newsId; ?>,
                    comment: text
                },
                success: function() {
                    location.reload();
                },
                error: function() {
                    $('#commentSubmit').prop('disabled', false);
                }
            });
        }
    }

    $('#commentForm').keydown(function (event) {
        if ((event.keyCode == 10 || event.keyCode == 13) && event.ctrlKey)
            submitComment();
    });

    $(".removeComment").click(function(){
        var $this = $(this);

        if (!confirm("Are you sure you want to remove this comment?")) {
            return;
        }

        $.ajax({
            type: "POST",
            url: "RemoveComment.php",
            data: {
                nID: <?php echo $newsId; ?>,
                cID: $this.attr('value')
            },
            success: function() {
                location.reload();
            }
        });
    });

    $(".scrubComment").click(function(){
        var $this = $(this);

        if (!confirm("Are you sure you want to scrub this comment?")) {
            return;
        }

        $.ajax({
            type: "POST",
            url: "ScrubComment.php",
            data: {
                nID: <?php echo $newsId; ?>,
                cID: $this.attr('value')
            },
            success: function() {
                location.reload();
            }
        });
    });
</script>

<?php require "../footer.php"; ?>