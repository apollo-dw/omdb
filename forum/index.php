<?php
    $PageTitle = "Forums";
    require "../base.php";
    require '../header.php';

$stmt = $conn->query("SELECT
                        ft.TopicID,
                        ft.Name AS TopicName,
                        ft.Description AS TopicDescription,
                        tt.TotalThreads,
                        tp.TotalPosts
                    FROM
                        forum_topics ft
                    LEFT JOIN (
                        SELECT
                            TopicID,
                            COUNT(DISTINCT ThreadID) AS TotalThreads
                        FROM
                            forum_threads
                        GROUP BY
                            TopicID
                    ) tt ON ft.TopicID = tt.TopicID
                    LEFT JOIN (
                        SELECT
                            ft.TopicID,
                            COUNT(fp.PostID) AS TotalPosts
                        FROM
                            forum_threads ft
                        LEFT JOIN
                            forum_posts fp ON ft.ThreadID = fp.ThreadID
                        GROUP BY
                            ft.TopicID
                    ) tp ON ft.TopicID = tp.TopicID;");
					
	if ($username !== "moonpoint") {
		die("What");
	}
?>


<style>
    h1, h2 {
        margin: 0;
        display: inline;
    }

    .forum-topic {
        width: 100%;
        padding: 0.5em;
        box-sizing: border-box;
    }
</style>

<h1>Forums</h1>
<hr>

<?php
    $thread_stmt = $conn->prepare("SELECT ft.ThreadID, ft.Title AS ThreadTitle, ft.CreatedAt AS ThreadCreatedAt, 
                                    fp.PostID, fp.UserID AS PostUserID, fp.CreatedAt AS PostCreatedAt
                            FROM forum_threads ft
                            LEFT JOIN forum_posts fp ON ft.ThreadID = fp.ThreadID
                            WHERE ft.TopicID = ?
                            ORDER BY fp.CreatedAt DESC
                            LIMIT 1");
    while ($row = $stmt->fetch_assoc()) {
        $thread_stmt->bind_param("i", $row["TopicID"]);
        $thread_stmt->execute();
        $latestThread = $thread_stmt->get_result()->fetch_assoc();
        ?>
        <div class="alternating-bg forum-topic flex-container">
            <div>
                <a href="topic/?id=<?php echo $row["TopicID"]; ?>"><h2><?php echo $row["TopicName"]; ?></h2></a> <br>
                <span class="subText"><?php echo $row["TopicDescription"]; ?></span>
            </div>
            <div style="margin-left:auto;display:flex;align-items:center;">
                <div style="margin-right: 6em;display:flex;align-items:center;">
                    <div style="margin-left: 0.5em;text-align: right;">
                        <a style="display:flex;" href="post/?id=<?php echo $latestThread["ThreadID"]; ?>">
                            <?php echo $latestThread["ThreadTitle"]; ?>
                        </a>
                        <span class="subText"><?php echo GetHumanTime($latestThread["PostCreatedAt"]); ?></span>
                    </div>

                </div>
                <div style="min-width: 6em;text-align: right;">
                    <?php echo $row["TotalPosts"]; ?> posts <br>
                    <?php echo $row["TotalThreads"]; ?> threads
                </div>
            </div>
        </div>
        <?php
    }
?>

<?php
    require '../footer.php';
?>
