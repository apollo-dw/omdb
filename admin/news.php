<?php
include 'header.php';
?>

<style>
    table, tr, td {
        text-align: left;
        vertical-align: top;
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        border-spacing: 0;
    }

    tr, td {
        padding: 0.5em;
        font-size: 14px;
    }

    textarea#content {
        width: 30em;
        height: 10em;
    }

    input#title {
        width: 30em;
    }

    .news-preview {
        max-width: 30em;
        max-height: 4em;
        overflow: hidden;
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>

<h1>news</h1>

<?php
    $editing  = null;
    $editID   = (int)($_GET["edit"] ?? 0);
    if ($editID > 0) {
        $stmt = $conn->prepare("SELECT * FROM news_posts WHERE NewsID = ?");
        $stmt->bind_param("i", $editID);
        $stmt->execute();
        $editing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
?>
<?php CSRFField(); ?>
<label for="title">Title:</label><br>
<input name="title" id="title" type="text" maxlength="255" value="<?php echo $editing ? safe_htmlspecialchars($editing["Title"], ENT_QUOTES) : ''; ?>" /><br><br>

<label for="content">Content:</label><br>
<textarea name="content" id="content"><?php echo $editing ? safe_htmlspecialchars($editing["Content"], ENT_QUOTES) : ''; ?></textarea><br><br>

<input type="hidden" id="newsID" value="<?php echo $editing ? (int)$editing["NewsID"] : 0; ?>" />
<input type="button" value="<?php echo $editing ? 'Save Changes' : 'Publish Post'; ?>" onclick="SubmitNews()"/>
<?php if ($editing): ?>
    <input type="button" value="Cancel Edit" onclick="window.location.href='news.php'"/>
<?php endif; ?>
<div id="newsStatus"></div>

<br><br><br>

<div>
    <table>
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Date</th>
            <th>Preview</th>
            <th>Actions</th>
        </tr>
        <?php
        $stmt = $conn->prepare("SELECT n.NewsID, n.Title, n.Content, n.DateCreated, n.DateEdited, m.Username
            FROM news_posts n
            LEFT JOIN mappernames m ON m.UserID = n.AuthorID
            ORDER BY n.DateCreated DESC
        ");
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $dateStr = date("M j, Y H:i:s", strtotime($row["DateCreated"]));
            if ($row["DateEdited"] !== null) {
                $dateStr .= "<br>(edited " . date("M j, Y H:i:s", strtotime($row["DateEdited"])) . ")";
            }
            echo "<tr>";
            echo "<td>" . safe_htmlspecialchars($row["Title"], ENT_QUOTES) . "</td>";
            echo "<td>" . safe_htmlspecialchars($row["Username"], ENT_QUOTES) . "</td>";
            echo "<td>" . $dateStr . "</td>";
            echo "<td class='news-preview'>" . ParseCommentLinks($conn, safe_htmlspecialchars($row["Content"], ENT_QUOTES)) . "</td>";
            echo "<td>";
            echo "<a href='news.php?edit=" . (int)$row["NewsID"] . "'><input type='button' value='Edit'/></a> ";
            echo "<input type='button' value='Delete' onclick='DeleteNews(" . (int)$row["NewsID"] . ")'/>";
            echo "</td>";
            echo "</tr>";
        }
        $stmt->close();
        ?>
    </table>
</div>

<script>
    function SubmitNews() {
        const newsID = document.getElementById('newsID').value;
        const title = document.getElementById('title').value;
        const content = document.getElementById('content').value;
        const csrf = document.getElementById('csrf_token').value;

        const xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    document.getElementById('newsStatus').textContent = 'Saved.';
                    window.location.href = 'news.php';
                } else {
                    document.getElementById('newsStatus').textContent = 'Error: ' + xhr.responseText;
                    console.error('Error: ' + xhr.status);
                }
            }
        };

        xhr.open('POST', 'actions/SubmitNews.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(
            'csrf_token=' + encodeURIComponent(csrf) +
            '&newsID=' + encodeURIComponent(newsID) +
            '&title=' + encodeURIComponent(title) +
            '&content=' + encodeURIComponent(content)
        );
    }

    function DeleteNews(newsID) {
        if (!confirm('Delete this post?'))
            return;

        const csrf = document.getElementById('csrf_token').value;
        const xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200)
                    window.location.reload();
                else
                    console.error('Error: ' + xhr.status);
            }
        };

        xhr.open('POST', 'actions/DeleteNews.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(
            'csrf_token=' + encodeURIComponent(csrf) +
            '&newsID=' + encodeURIComponent(newsID)
        );
    }
</script>