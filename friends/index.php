<?php
    $PageTitle = "Friends";
    include '../header.php';

    if (!$loggedIn) {
        die("You have to be logged in to view this page!");
    }

    $stmt_check = $conn->prepare("SELECT u.Username, u.UserID FROM user_relations r LEFT JOIN users u ON r.UserIDTo = u.UserID WHERE UserIDFrom = ? AND type = 1 ORDER BY u.LastAccessedSite DESC;");
    $stmt_check->bind_param("i", $userId);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    $friends = [];
    while ($row = $result->fetch_assoc()) {
        if (!is_null($row["UserID"])) {
            $friends[] = $row["UserID"];
        }
    }

    $stmt_check->close();
?>

<h1 style="margin: 0;">Friends</h1>
<span class="subText">Latest activity from your friends in the past month</span> <br>
<hr>

<?php
    if (sizeof($friends) === 0) {
        die("You got no friends. Make some friends first punk");
    }
?>

<div class="flex-container">
    <div style="flex: 0 0 60%; width: 60%;">

    <?php
        $feed_query = $conn->prepare("
        (
            /* ==========================
            NEW RATINGS
            ========================== */
            SELECT
                'rating' AS ActivityType,
                r.date AS ActivityDate,
                u.UserID AS FriendUserID,
                u.Username AS FriendUsername,
                r.RatingID AS ObjectID,
                r.BeatmapID AS ObjectType,
                CONCAT(
                    bs.Artist,
                    ' - ',
                    bs.Title,
                    ' [',
                    b.DifficultyName,
                    ']'
                ) AS Title,
                JSON_OBJECT(
                    'Score', r.Score,
                    'BeatmapID', b.BeatmapID,
                    'SetID', bs.SetID
                ) AS ExtraData

            FROM user_relations fr
            JOIN users u
                ON u.UserID = fr.UserIDTo
            JOIN ratings r
                ON r.UserID = u.UserID
            LEFT JOIN beatmaps b
                ON b.BeatmapID = r.BeatmapID
            LEFT JOIN beatmapsets bs
                ON bs.SetID = b.SetID

            WHERE fr.UserIDFrom = ?
            AND fr.type = 1
            AND r.date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )
        UNION ALL
        (
            /* ==========================
            NEW REVIEWS
            ========================== */
            SELECT
                'review',
                rv.date,
                u.UserID,
                u.Username,
                rv.ReviewID,
                rv.SetID,
                CONCAT(bs.Artist, ' - ', bs.Title),
                JSON_OBJECT(
                    'Review', LEFT(rv.Comment, 250),
                    'SetID', bs.SetID
                )

            FROM user_relations fr
            JOIN users u
                ON u.UserID = fr.UserIDTo
            JOIN reviews rv
                ON rv.UserID = u.UserID
            LEFT JOIN beatmapsets bs
                ON bs.SetID = rv.SetID

            WHERE fr.UserIDFrom = ?
            AND fr.type = 1
            AND rv.date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )
        UNION ALL
        (
            /* ==========================
            NEW COMMENTS
            ========================== */
            SELECT
                'comment',
                c.date,
                u.UserID,
                u.Username,
                c.CommentID,
                c.SetID,
                CONCAT(bs.Artist, ' - ', bs.Title),
                JSON_OBJECT(
                    'Comment', LEFT(c.Comment, 250),
                    'SetID', bs.SetID
                )

            FROM user_relations fr
            JOIN users u
                ON u.UserID = fr.UserIDTo
            JOIN comments c
                ON c.UserID = u.UserID
            LEFT JOIN beatmapsets bs
                ON bs.SetID = c.SetID

            WHERE fr.UserIDFrom = ?
            AND fr.type = 1
            AND c.date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )
        UNION ALL
        (
            /* ==========================
            NEW LISTS
            ========================== */
            SELECT
                'list',
                l.CreatedAt,
                u.UserID,
                u.Username,
                l.ListID,
                l.ListID,
                l.Title,
                JSON_OBJECT(
                    'Description', LEFT(l.Description, 250)
                )

            FROM user_relations fr
            JOIN users u
                ON u.UserID = fr.UserIDTo
            JOIN lists l
                ON l.UserID = u.UserID

            WHERE fr.UserIDFrom = ?
            AND fr.type = 1
            AND l.Private = 0
            AND l.CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )
        UNION ALL
        (
            /* ==========================
            REVIEW LIKES
            ========================== */
            SELECT
                'review_like',
                rv.date,
                u.UserID,
                u.Username,
                rh.HeartID,
                rv.ReviewID,
                CONCAT(bs.Artist, ' - ', bs.Title),
                JSON_OBJECT(
                    'ReviewID', rv.ReviewID
                )

            FROM user_relations fr
            JOIN users u
                ON u.UserID = fr.UserIDTo
            JOIN review_hearts rh
                ON rh.UserID = u.UserID
            JOIN reviews rv
                ON rv.ReviewID = rh.ReviewID
            LEFT JOIN beatmapsets bs
                ON bs.SetID = rv.SetID

            WHERE fr.UserIDFrom = ?
            AND fr.type = 1
            AND rv.date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )
        UNION ALL
        (
            /* ==========================
            LIST LIKES
            ========================== */
            SELECT
                'list_like',
                l.CreatedAt,
                u.UserID,
                u.Username,
                lh.HeartID,
                l.ListID,
                l.Title,
                JSON_OBJECT()

            FROM user_relations fr
            JOIN users u
                ON u.UserID = fr.UserIDTo
            JOIN list_hearts lh
                ON lh.UserID = u.UserID
            JOIN lists l
                ON l.ListID = lh.ListID

            WHERE fr.UserIDFrom = ?
            AND fr.type = 1
            AND l.Private = 0
            AND l.CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )
        UNION ALL
        (
            SELECT
                'ranked_map',
                bs.DateRanked,
                u.UserID,
                u.Username,
                b.BeatmapID,
                b.BeatmapID,
                CONCAT(
                    bs.Artist,
                    ' - ',
                    bs.Title,
                    ' [',
                    b.DifficultyName,
                    ']'
                ),
                JSON_OBJECT(
                    'SetID', bs.SetID
                )

            FROM user_relations fr
            JOIN users u
                ON u.UserID = fr.UserIDTo

            JOIN beatmap_creators bc
                ON bc.CreatorID = u.UserID

            JOIN beatmaps b
                ON b.BeatmapID = bc.BeatmapID

            JOIN beatmapsets bs
                ON bs.SetID = b.SetID

            WHERE fr.UserIDFrom = ?
            AND fr.type = 1
            AND bs.DateRanked >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )
        ORDER BY ActivityDate DESC LIMIT 500;");
        
        $feed_query->bind_param("iiiiiii", $userId, $userId, $userId, $userId, $userId, $userId, $userId);
        $feed_query->execute();
        $result = $feed_query->get_result();

    while ($row = $result->fetch_assoc()) {
        $extra = json_decode($row["ExtraData"], true);

        echo '<div class="flex-container ratingContainer alternating-bg" style="overflow:hidden;">';

        /* Thumbnail */
        echo '<div class="flex-child" style="margin-left:0.5em;">';

        $setID = $extra["SetID"] ?? $row["SetID"];

        switch ($row["ActivityType"])
        {
            case 'rating':
            case 'review':
            case 'comment':
            case 'review_like':
            case 'ranked_map':
                if (!empty($setID)) {
                    echo '<a href="/mapset/' . intval($setID) . '">';
                    echo '<img src="https://b.ppy.sh/thumb/' . intval($setID) . 'l.jpg"
                            class="diffThumb"
                            onerror="this.onerror=null; this.src=\'/charts/INF.png\';"/>';
                    echo '</a>';
                }
                break;
            case 'list':
            case 'list_like':
                echo '<div style="
                    height:32px;
                    width:32px;
                    text-align:center;
                    line-height:32px;
                    font-size:16px;
                ">';
                echo '<i class="icon-list"></i>';
                echo '</div>';

                break;
        }

        echo '</div>';

        /* Main content */
        echo '<div class="flex-child">';
        echo '<a href="/profile/' . intval($row["FriendUserID"]) . '">';
        echo '<img
                src="https://s.ppy.sh/a/' . intval($row["FriendUserID"]) . '"
                style="height:24px;width:24px;"
                title="' . safe_htmlspecialchars($row["FriendUsername"], ENT_QUOTES) . '"/>';
        echo '</a> ';
        echo '<a
                href="/profile/' . intval($row["FriendUserID"]) . '">';
        echo safe_htmlspecialchars($row["FriendUsername"], ENT_QUOTES);
        echo '</a> ';    

        switch ($row["ActivityType"])
        {
            case 'rating':
                echo RenderRating($extra["Score"]);
                echo ' on ';
                echo '<a href="/beatmap/' . intval($extra["BeatmapID"]) . '">';
                echo safe_htmlspecialchars($row["Title"], ENT_QUOTES);
                echo '</a>';
                break;

            case 'review':
                echo 'reviewed ';
                echo '<a href="/mapset/' . intval($row["ObjectID"]) . '">';
                echo safe_htmlspecialchars($row["Title"], ENT_QUOTES);
                echo '</a>';
                if (!empty($extra["Review"])) {
                    echo '<div style="padding: 0.5em;">';
                    echo nl2br(
                        safe_htmlspecialchars(
                            mb_strimwidth($extra["Review"], 0, 800, "..."),
                            ENT_QUOTES
                        )
                    );
                    echo '</div>';
                }
                break;

            case 'comment':
                echo 'commented on ';
                echo '<a href="/mapset/' . intval($row["ObjectID"]) . '">';
                echo safe_htmlspecialchars($row["Title"], ENT_QUOTES);
                echo '</a>';
                if (!empty($extra["Comment"])) {
                    echo '<div style="padding: 0.5em;">';
                    echo safe_htmlspecialchars(
                        mb_strimwidth($extra["Comment"], 0, 250, "..."),
                        ENT_QUOTES
                    );
                    echo '</div>';
                }
                break;

            case 'list':
                echo 'created list ';
                echo '<a href="/list/' . intval($row["ObjectID"]) . '">';
                echo safe_htmlspecialchars($row["Title"], ENT_QUOTES);
                echo '</a>';
                break;

            case 'review_like':
                echo 'liked a review for ';
                echo '<a href="/review/' . intval($extra["ReviewID"]) . '">';
                echo safe_htmlspecialchars($row["Title"], ENT_QUOTES);
                echo '</a>';
                break;

            case 'list_like':
                echo 'liked list ';
                echo '<a href="/list/' . intval($row["ObjectID"]) . '">';
                echo safe_htmlspecialchars($row["Title"], ENT_QUOTES);
                echo '</a>';
                break;

            case 'ranked_map':
                echo 'had a difficulty ranked: ';
                echo '<a href="/beatmap/' . intval($row["ObjectID"]) . '">';
                echo safe_htmlspecialchars($row["Title"], ENT_QUOTES);
                echo '</a>';
                break;
        }

        echo '<div style="font-size:11px;color:#999;margin-top:2px;">';
        echo GetHumanTime($row["ActivityDate"]);
        echo '</div>';

        echo '</div>';
        echo '</div>';
    }
    ?>

    </div>
    <div style="flex: 0 0 40%; padding-left: 1em;">
        <b>Your friends</b> <br>
        <?php
            foreach ($friends as $friend) {
                echo "<a href='/profile/" . $friend . "'>";
                echo GetUserNameFromId($friend, $conn) . "<br>";
                echo "</a>";
            }
        ?>
    </div>
</div>
<?php
    include '../footer.php';
?>