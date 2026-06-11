<?php
    require_once "../../base.php";

    $topicId = GetIntParam('id', -1, "AHHH");
    $page = GetIntParam('p', 1);

    $stmt = $conn->prepare("SELECT * FROM forum_topics WHERE TopicID = ?;");
    $stmt->bind_param("i", $topicId);
    $stmt->execute();
    $topic = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (is_null($topic))
        die("AHHH");

    $PageTitle = $topic["Name"];
    require_once '../../header.php';

    $limit = 50;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM forum_threads WHERE TopicID = ?;");
    $stmt->bind_param("i", $topicId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $pageCount = floor($count / $limit) + 1;
    $prevPage = max($page - 1, 1);
    $nextPage = min($page + 1, $pageCount);
    $pageString = "LIMIT {$limit}";

    if ($page > 1) {
        $lower = ($page - 1) * $limit;
        $pageString = "LIMIT {$lower}, {$limit}";
    }

    $stmt = $conn->prepare("SELECT
    ft.ThreadID,
    ft.Title,
    ft.TopicID,
    ft.UserID AS ThreadUserID,
    ft.CreatedAt AS ThreadCreatedAt,
    COUNT(fp.PostID) AS TotalPosts,
    MAX(fp.CreatedAt) AS LatestPostCreatedAt,
    (SELECT fp.UserID FROM forum_posts fp WHERE ft.ThreadID = fp.ThreadID ORDER BY fp.CreatedAt DESC LIMIT 1) AS LatestPostUserID,
    (SELECT MAX(fp.PostID) FROM forum_posts fp WHERE ft.ThreadID = fp.ThreadID) AS LatestPostID,
    (SELECT MAX(fp.CreatedAt) FROM forum_posts fp WHERE ft.ThreadID = fp.ThreadID) AS LatestPostCreatedAt
    FROM forum_threads ft
    LEFT JOIN forum_posts fp ON ft.ThreadID = fp.ThreadID
    WHERE ft.TopicID = ?
    GROUP BY ft.ThreadID
    ORDER BY LatestPostCreatedAt DESC {$pageString};");

    $stmt->bind_param("i", $topicId);
    $stmt->execute();
    $threads = $stmt->get_result();
    $stmt->close();
?>

<style>
    h2 {
        margin: 0;
        display: inline;
    }

    .forum-thread {
        width: 100%;
        height: 6em;
        box-sizing: border-box;
        align-items: center;
        padding: 0.5em;
    }
</style>

<h2><?php echo htmlspecialchars($topic["Name"], ENT_QUOTES); ?></h2>
<div>
    <span class="subText"><?php echo htmlspecialchars($topic["Description"], ENT_QUOTES); ?></span>
    <?php if ($loggedIn) { ?>
        <div style="float:right;"><a href="../new/?id=<?php echo $topicId; ?>">Create new post</a></div>
    <?php } ?>
</div>
<hr>

<?php
    while($thread = $threads->fetch_assoc()) {
        ?>
        <div class="flex-container forum-thread alternating-bg">
            <div>
                <a href="../post/?id=<?php echo $thread["ThreadID"]; ?>"><b><?php echo htmlspecialchars($thread["Title"], ENT_QUOTES); ?></b></a> <br>
                <span class="subText">
                    by <a href="../../profile/<?php echo $thread["ThreadUserID"]; ?>">
                        <?php echo htmlspecialchars(GetUserNameFromId($thread["ThreadUserID"], $conn), ENT_QUOTES); ?></a>
                     | <?php RenderLocalTime($thread["ThreadCreatedAt"]); ?>
                </span>
            </div>
            <div style="margin-left:auto;display:flex;align-items:center;">
                <div style="margin-right: 6em;display:flex;align-items:center;">
                    <a href="/profile/<?php echo $thread["LatestPostUserID"]; ?>">
                        <img src="https://s.ppy.sh/a/<?php echo $thread["LatestPostUserID"]; ?>" style="height:32px;width:32px;" title="<?php echo htmlspecialchars(GetUserNameFromId($thread["LatestPostUserID"], $conn), ENT_QUOTES); ?>"/>
                    </a>
                    <div style="margin-left: 0.5em;">
                        <a style="display:flex;" href="/profile/<?php echo $thread["LatestPostUserID"]; ?>">
                            <?php echo htmlspecialchars(GetUserNameFromId($thread["LatestPostUserID"], $conn), ENT_QUOTES); ?>
                        </a>
                        <span class="subText"><?php echo GetHumanTime($thread["LatestPostCreatedAt"]); ?></span>
                    </div>

                </div>
                <div style="min-width: 6em;text-align: right;">
                    <?php echo $thread["TotalPosts"]; ?> posts
                </div>
            </div>
        </div>
        <?php
    }
?>

<div style="float:right;">
    <div class="pagination">
        <a href="<?php echo "?id={$topicId}&p={$prevPage}"; ?>"><span>&laquo;</span></a>
        <?php for ($i = 1; $i <= $pageCount; $i++) { ?>
            <a href="<?php echo "?id={$topicId}&p={$i}"; ?>"><span class="pageLink <?php if ($page == $i) echo 'active' ?>"><?php echo $i ?></span></a>
        <?php } ?>
        <a href="<?php echo "?id={$topicId}&p={$nextPage}"; ?>"><span>&raquo;</span></a>
    </div>
</div>

<?php
    require ('../../footer.php');
?>