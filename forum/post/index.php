<?php
    $threadId = $_GET['id'] ?? -1;
    $page = $_GET['p'] ?? 1;
    require_once "../../base.php";

    $stmt = $conn->prepare("SELECT * FROM forum_threads LEFT JOIN forum_topics ft on forum_threads.TopicID = ft.TopicID WHERE ThreadID = ?;");
    $stmt->bind_param("i", $threadId);
    $stmt->execute();
    $thread = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (is_null($thread) || !is_numeric($threadId) || !is_numeric($page))
        die("ahhh");

    $PageTitle = $thread["Title"];
    require_once '../../header.php';

    $limit = 15;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM forum_posts WHERE ThreadID = ?;");
    $stmt->bind_param("i", $threadId);
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

    $stmt = $conn->prepare("SELECT * FROM forum_posts WHERE ThreadID = ? ORDER BY CreatedAt {$pageString};");
    $stmt->bind_param("i", $threadId);
    $stmt->execute();
    $posts = $stmt->get_result();
    $stmt->close();
?>

<style>
    h2 {
        margin: 0;
    }

    .forum-post-divider {
        width: 100%;
        background-color: #203838;
        height: 2px;
        box-sizing: border-box;
    }

    .forum-post-body {
        width: 100%;
        box-sizing: border-box;
        min-height: 12em;
    }

    .forum-post-info {
        background-color: DarkSlateGrey;
        box-sizing: border-box;
        min-height: inherit;
        width: 17%;
        min-width: 100px;
        align-items: center;
        justify-content: center;
        display: flex;
    }

    .forum-post-content {
        background-color: DarkSlateGrey;
        box-sizing: border-box;
        min-height: inherit;
        width: 83%;
        padding: 1em;
    }

    .forum-post-reply {
        background-color: DarkSlateGrey;
        box-sizing: border-box;
        min-height: 8em;
        width: 100%;
        padding: 1em;
    }

    textarea {
        width: 100%;
        text-align: left;
        resize: none;
        height: 6em;
        background-color: #203838;
        color: white;
        border: 1px solid white;
    }

    .small-button {
        min-width: revert;
    }

    .removePost {
        font-size: 1.5em;
    }

    .pagination span {
        padding: 0px 16px;
    }
</style>

<h2><?php echo $thread["Title"]; ?></h2>
<span class="subText"><?php echo $thread["Name"]; ?></span>

<div style="float:right;">
    <div class="pagination">
        <a href="<?php echo "?id={$threadId}&p={$prevPage}"; ?>"><span>&laquo;</span></a>
        <?php for ($i = 1; $i <= $pageCount; $i++) { ?>
            <a href="<?php echo "?id={$threadId}&p={$i}"; ?>"><span class="pageLink <?php if ($page == $i) echo 'active' ?>"><?php echo $i ?></span></a>
        <?php } ?>
        <a href="<?php echo "?id={$threadId}&p={$nextPage}"; ?>"><span>&raquo;</span></a>
    </div>
</div>
<hr>

<?php
    while($post = $posts->fetch_assoc()) {
        ?>
        <div class="flex-container forum-post-body" id="post-<?php echo $post["PostID"]; ?>">
            <div class="forum-post-info">
                <div>
                    <div class="profileTitle" style="text-align: center;">
                        <a href="/profile/<?php echo $post["UserID"]; ?>" rel="noopener noreferrer"><?php echo GetUserNameFromId($post["UserID"], $conn); ?></a>
                        <a href="/profile/<?php echo $post["UserID"]; ?>" rel="noopener noreferrer"></a>
                    </div>
                    <div class="profileImage">
                        <img src="https://s.ppy.sh/a/<?php echo $post["UserID"]; ?>" style="width:100px;height:100px;">
                    </div>
                    <div style="text-align: center;">
                    </div>
                </div>
            </div>
            <div class="forum-post-content">
                <div style="margin-bottom:1em;">
                    <span class="subText">
                        <a href="#post-<?php echo $post["PostID"]; ?>">
                            <?php RenderLocalTime($post["CreatedAt"]); ?>
                        </a>
                    </span>
                    <span style="float:right;">
                        <?php
                        if ($post["UserID"] == $userId || $userId === 9558549) { ?>
                            <i class="icon-remove removePost" style="color:#f94141;" value="<?php echo $post["PostID"]; ?>"></i>
                        <?php } ?>
                    </span>
                </div>
                <div>
                    <?php echo ParseCommentLinks($conn, $post["Content"]); ?>
                </div>
            </div>
        </div>

        <div class="forum-post-divider"></div>
        <?php
    }
?>

<?php if ($loggedIn) { ?>
    <form action="Reply.php" method="post">
        <div class="forum-post-reply">
            Write a reply:<br><br>
            <input type="hidden" name="PostThread" value="<?php echo $threadId; ?>" />

            <textarea name="PostReply" id="PostReply"></textarea> <br><br>
            <button type="button" onclick="insertTag('img')" class="small-button">img</button>
            <button type="button" onclick="insertTag('a')" class="small-button">link</button>
            <button type="button" onclick="insertTag('code')" class="small-button">code</button>
            <button type="button" onclick="insertTag('font', 'color=')" class="small-button">color</button>
            <button type="button" onclick="insertTag('b')" class="small-button">bold</button>
            <button type="button" onclick="insertTag('i')" class="small-button">italics</button>
            <button type="button" onclick="insertTag('u')" class="small-button">underline</button> <br><br>

            <input type="submit" id="submitButton" value="Submit" />
        </div>
    </form>
<?php } ?>

<div style="float:right;">
    <div class="pagination">
        <a href="<?php echo "?id={$threadId}&p={$prevPage}"; ?>"><span>&laquo;</span></a>
        <?php for ($i = 1; $i <= $pageCount; $i++) { ?>
            <a href="<?php echo "?id={$threadId}&p={$i}"; ?>"><span class="pageLink <?php if ($page == $i) echo 'active' ?>"><?php echo $i ?></span></a>
        <?php } ?>
        <a href="<?php echo "?id={$threadId}&p={$nextPage}"; ?>"><span>&raquo;</span></a>
    </div>
</div>

<script>
    function insertTag(tag, param = '') {
        var textarea = document.getElementById("PostReply");
        var cursorPos = textarea.selectionStart;
        var cursorEnd = textarea.selectionEnd;
        var tagText = '';

        switch (tag) {
            case 'font':
                tagText = '[' + tag + ' ' + param + ']' + textarea.value.substring(cursorPos, cursorEnd) + '[/' + tag + ']';
                break;
            default:
                tagText = '[' + tag + ']' + textarea.value.substring(cursorPos, cursorEnd) + '[/' + tag + ']';
        }

        var textBefore = textarea.value.substring(0, cursorPos);
        var textAfter = textarea.value.substring(cursorEnd);

        textarea.value = textBefore + tagText + textAfter;
        textarea.setSelectionRange(cursorPos, cursorPos + tagText.length);
        textarea.focus();
    }

    $(".removePost").click(function(event){
        var $this = $(this);

        if (!confirm("Are you sure you want to remove this post?")) {
            return;
        }

        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                console.log(this.responseText);
                location.reload();
            }
        };

        xhttp.open("POST", "RemovePost.php", true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send("tID=" + <?php echo $thread["ThreadID"]; ?> + "&pID=" + $this.attr('value'));
    });
</script>

<?php
require ('../../footer.php');
?>