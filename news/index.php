<?php
    $PageTitle = "News";
    require "../base.php";
    require "../header.php";
?>

<h1 style="margin-bottom:0.25em;">News</h1>
<hr>

<?php
    $stmt = $conn->prepare("SELECT n.NewsID, n.Title, n.Content, n.DateCreated, n.DateEdited, n.AuthorID
        FROM news_posts n
        ORDER BY n.DateCreated DESC
    ");
    $stmt->execute();
    $posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($posts)) {
        echo "<p style='color:grey;'>No news yet</p>";
    }

    foreach ($posts as $post):
        $newsId = (int)$post["NewsID"];
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
        $newsCommentCount = $stmt->get_result()->fetch_row()[0];
        $stmt->close();
?>
    <div class="news-post alternating-bg" style="padding: 1em;">
        <h2>
            <a href="post.php?id=<?php echo $newsId; ?>">
                <?php echo safe_htmlspecialchars($post["Title"], ENT_QUOTES); ?>
            </a>
            <?php if ($loggedIn) { ?>
            <div class="tooltip-wrapper" style="float:right;">
                <span class="subText">[<?php echo $newsHeartCount; ?>]</span>

                <i
                    id="news-heart-<?php echo $newsId; ?>"
                    class="icon-heart<?php if (!$userHasLikedNews) echo "-empty"; ?> news-heart"
                    style="font-size: 13px;"
                    data-news-id="<?php echo $newsId; ?>"
                ></i>
                <?php if ($heartedUsernamesString) { ?>
                    <div class="tooltip-box">
                        <?php echo $heartedUsernamesString; ?>
                    </div>
                <?php } ?>
            </div>
            <br>
            <span class="subText" style="float:right;">    
                <?php echo $newsCommentCount; ?> comment<?php echo $newsCommentCount == 1 ? "" : "s"; ?>
            </span>
        <?php } ?>
        </h2>
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
            <?php 
                $previewText = mb_strimwidth($post["Content"], 0, 200, "…");
                echo nl2br(safe_htmlspecialchars($previewText, ENT_QUOTES));
            ?><br><a href="post.php?id=<?php echo $newsId; ?>">See full post 👀</a>
        </div>
    </div>
<?php endforeach; ?>

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
</script>

<?php require "../footer.php"; ?>