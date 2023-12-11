<?php
    $topicId = $_GET['id'] ?? -1;
    $PageTitle = "Forums";
    require "../../base.php";
    require '../../header.php';

    $stmt = $conn->prepare("SELECT Name FROM forum_topics WHERE TopicID = ?;");
    $stmt->bind_param("i", $topicId);
    $stmt->execute();
    $topic = $stmt->get_result()->fetch_assoc()["Name"];

    if (is_null($topic) || !is_numeric($topicId))
        die("AHHH");

    if (!$loggedIn)
        die("Please log in");
?>

<style>
    .container {
        width: 100%;
        background-color: darkslategray;
        padding: 1.5em;
        box-sizing: border-box;
        overflow: hidden;
    }

    h2 {
        margin: 0;
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

<form action="CreatePost.php" method="post">
    <div class="container">
        <h2>New post</h2>
        <span class="subText"><?php echo $topic; ?></span>
        <hr>

        <input type="hidden" name="PostTopic" value="<?php echo $topicId; ?>" />
        <label>Subject:</label> <br>
        <input autocomplete="off" name="PostSubject" id="PostSubject" style="width:50%;" required/><br><br>

        <label>Body:</label> <br>
        <textarea name="PostBody" id="PostBody"><?php echo $list["Description"] ?? ""; ?></textarea> <br><br>
        <button type="button" onclick="insertTag('img')" class="small-button">img</button>
        <button type="button" onclick="insertTag('a')" class="small-button">link</button>
        <button type="button" onclick="insertTag('code')" class="small-button">code</button>
        <button type="button" onclick="insertTag('font', 'color=')" class="small-button">color</button>
        <button type="button" onclick="insertTag('b')" class="small-button">bold</button>
        <button type="button" onclick="insertTag('i')" class="small-button">italics</button>
        <button type="button" onclick="insertTag('u')" class="small-button">underline</button>

        <br><br>
        <hr>

        <div>
            Forums are subject to the <a href="/rules/">OMDB rules and code of conduct.</a>
        </div> <br>
        <input type="submit" id="submitButton" value="Submit" />
    </div>
</form>

<script>
    function insertTag(tag, param = '') {
        var textarea = document.getElementById("PostBody");
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