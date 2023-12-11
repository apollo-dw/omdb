<?php
    $threadId = $_GET['id'] ?? -1;
    require_once "../../base.php";

    $stmt = $conn->prepare("SELECT * FROM forum_threads LEFT JOIN forum_topics ft on forum_threads.TopicID = ft.TopicID WHERE ThreadID = ?;");
    $stmt->bind_param("i", $threadId);
    $stmt->execute();
    $thread = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (is_null($thread) || !is_numeric($threadId))
        die("ahhh");

    $PageTitle = $thread["Title"];
    require_once '../../header.php';

    $stmt = $conn->prepare("SELECT * FROM forum_posts WHERE ThreadID = ? ORDER BY CreatedAt;");
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
        min-height: 15em;
    }

    .forum-post-info {
        background-color: DarkSlateGrey;
        box-sizing: border-box;
        min-height: inherit;
        width: 20%;
        align-items: center;
        justify-content: center;
        display: flex;
    }

    .forum-post-content {
        background-color: DarkSlateGrey;
        box-sizing: border-box;
        min-height: inherit;
        width: 80%;
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
</style>

<h2><?php echo $thread["Title"]; ?></h2>
<span class="subText"><?php echo $thread["Name"]; ?></span>

<hr>

<?php
    while($post = $posts->fetch_assoc()) {
        ?>
        <div class="flex-container forum-post-body" id="post-<?php echo $post["PostID"]; ?>">
            <div class="forum-post-info">
                <div>
                    <div class="profileTitle" style="text-align: center;">
                        <a href="https://osu.ppy.sh/u/<?php echo $post["UserID"]; ?>" target="_blank" rel="noopener noreferrer"><?php echo GetUserNameFromId($post["UserID"], $conn); ?></a>
                        <a href="https://osu.ppy.sh/u/<?php echo $post["UserID"]; ?>" target="_blank" rel="noopener noreferrer"></a>
                    </div>
                    <div class="profileImage">
                        <img src="https://s.ppy.sh/a/<?php echo $post["UserID"]; ?>" style="width:100px;height:100px;">
                    </div>
                    <div style="text-align: center;">
                        <span class="subText">0 posts</span>
                    </div>
                </div>
            </div>
            <div class="forum-post-content">
                <div style="margin-bottom:1em;">
                    <span class="subText"><?php RenderLocalTime($post["CreatedAt"]); ?></span>
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
</script>

<?php
require ('../../footer.php');
?>