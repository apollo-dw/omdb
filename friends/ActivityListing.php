<?php
    include_once '../base.php';

    $ratings      = filter_var($_POST['ratings'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $reviews      = filter_var($_POST['reviews'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $review_likes = filter_var($_POST['review_likes'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $lists        = filter_var($_POST['lists'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $list_likes   = filter_var($_POST['list_likes'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $ranked_maps  = filter_var($_POST['ranked_maps'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $comments     = filter_var($_POST['comments'] ?? true, FILTER_VALIDATE_BOOLEAN);

    $queries = [];

    if ($ratings) {
        $queries[] = "(
            SELECT
                'rating' AS ActivityType,
                r.date AS ActivityDate,
                u.UserID AS FriendUserID,
                u.Username AS FriendUsername,
                r.RatingID AS ObjectID,
                r.BeatmapID AS ObjectType,
                CONCAT(bs.Artist, ' - ', bs.Title, ' [', b.DifficultyName, ']') AS Title,
                JSON_OBJECT('Score', r.Score, 'BeatmapID', b.BeatmapID, 'SetID', bs.SetID) AS ExtraData
            FROM user_relations fr
            JOIN users u ON u.UserID = fr.UserIDTo
            JOIN ratings r ON r.UserID = u.UserID
            LEFT JOIN beatmaps b ON b.BeatmapID = r.BeatmapID
            LEFT JOIN beatmapsets bs ON bs.SetID = b.SetID
            WHERE fr.UserIDFrom = ? AND fr.type = 1 AND r.date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )";
    }

    if ($reviews) {
        $queries[] = "(
            SELECT
                'review' AS ActivityType, 
                rv.date AS ActivityDate, 
                u.UserID AS FriendUserID, 
                u.Username AS FriendUsername, 
                rv.ReviewID AS ObjectID, 
                rv.SetID AS ObjectType,
                CONCAT(bs.Artist, ' - ', bs.Title) AS Title,
                JSON_OBJECT('Review', LEFT(rv.Comment, 250), 'SetID', bs.SetID) AS ExtraData
            FROM user_relations fr
            JOIN users u ON u.UserID = fr.UserIDTo
            JOIN reviews rv ON rv.UserID = u.UserID
            LEFT JOIN beatmapsets bs ON bs.SetID = rv.SetID
            WHERE fr.UserIDFrom = ? AND fr.type = 1 AND rv.date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )";
    }

    if ($comments) {
        $queries[] = "(
            SELECT
                'comment' AS ActivityType, 
                c.date AS ActivityDate, 
                u.UserID AS FriendUserID, 
                u.Username AS FriendUsername, 
                c.CommentID AS ObjectID, 
                c.SetID AS ObjectType,
                CONCAT(bs.Artist, ' - ', bs.Title) AS Title,
                JSON_OBJECT('Comment', LEFT(c.Comment, 250), 'SetID', bs.SetID) AS ExtraData
            FROM user_relations fr
            JOIN users u ON u.UserID = fr.UserIDTo
            JOIN comments c ON c.UserID = u.UserID
            LEFT JOIN beatmapsets bs ON bs.SetID = c.SetID
            WHERE fr.UserIDFrom = ? AND fr.type = 1 AND c.date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )";
    }

    if ($lists) {
        $queries[] = "(
            SELECT
                'list' AS ActivityType, 
                l.CreatedAt AS ActivityDate, 
                u.UserID AS FriendUserID, 
                u.Username AS FriendUsername, 
                l.ListID AS ObjectID, 
                l.ListID AS ObjectType, 
                l.Title AS Title,
                JSON_OBJECT('Description', LEFT(l.Description, 250)) AS ExtraData
            FROM user_relations fr
            JOIN users u ON u.UserID = fr.UserIDTo
            JOIN lists l ON l.UserID = u.UserID
            WHERE fr.UserIDFrom = ? AND fr.type = 1 AND l.Private = 0 AND l.CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )";
    }

    if ($review_likes) {
        $queries[] = "(
            SELECT
                'review_like' AS ActivityType, 
                rh.CreatedAt AS ActivityDate, 
                u.UserID AS FriendUserID, 
                u.Username AS FriendUsername, 
                rh.HeartID AS ObjectID, 
                rv.ReviewID AS ObjectType,
                CONCAT(bs.Artist, ' - ', bs.Title) AS Title,
                JSON_OBJECT('ReviewID', rv.ReviewID, 'SetID', bs.SetID) AS ExtraData
            FROM user_relations fr
            JOIN users u ON u.UserID = fr.UserIDTo
            JOIN review_hearts rh ON rh.UserID = u.UserID
            JOIN reviews rv ON rv.ReviewID = rh.ReviewID
            LEFT JOIN beatmapsets bs ON bs.SetID = rv.SetID
            WHERE fr.UserIDFrom = ? AND fr.type = 1 AND rh.CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )";
    }

    if ($list_likes) {
        $queries[] = "(
            SELECT
                'list_like' AS ActivityType, 
                lh.CreatedAt AS ActivityDate, 
                u.UserID AS FriendUserID, 
                u.Username AS FriendUsername, 
                lh.HeartID AS ObjectID, 
                l.ListID AS ObjectType, 
                l.Title AS Title,
                JSON_OBJECT() AS ExtraData
            FROM user_relations fr
            JOIN users u ON u.UserID = fr.UserIDTo
            JOIN list_hearts lh ON lh.UserID = u.UserID
            JOIN lists l ON l.ListID = lh.ListID
            WHERE fr.UserIDFrom = ? AND fr.type = 1 AND l.Private = 0 AND lh.CreatedAt >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )";
    }

    if ($ranked_maps) {
        $queries[] = "(
            SELECT
                'ranked_map' AS ActivityType, 
                bs.DateRanked AS ActivityDate, 
                u.UserID AS FriendUserID, 
                u.Username AS FriendUsername, 
                b.BeatmapID AS ObjectID, 
                b.BeatmapID AS ObjectType,
                CONCAT(bs.Artist, ' - ', bs.Title, ' [', b.DifficultyName, ']') AS Title,
                JSON_OBJECT('SetID', bs.SetID) AS ExtraData
            FROM user_relations fr
            JOIN users u ON u.UserID = fr.UserIDTo
            JOIN beatmap_creators bc ON bc.CreatorID = u.UserID
            JOIN beatmaps b ON b.BeatmapID = bc.BeatmapID
            JOIN beatmapsets bs ON bs.SetID = b.SetID
            WHERE fr.UserIDFrom = ? AND fr.type = 1 AND bs.DateRanked >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
        )";
    }

    if (empty($queries)) {
        $result = false; 
    } else {
        $sql = implode(" UNION ALL ", $queries) . " ORDER BY ActivityDate DESC LIMIT 250;";
        
        $feed_query = $conn->prepare($sql);
        
        $query_count = count($queries);
        $types = str_repeat("i", $query_count);
        $params = array_fill(0, $query_count, $userId);
        
        $feed_query->bind_param($types, ...$params);
        $feed_query->execute();
        $result = $feed_query->get_result();
    }

    while ($row = $result->fetch_assoc()) {
        $extra = json_decode($row["ExtraData"], true);

        echo '<div class="flex-container ratingContainer alternating-bg" style="overflow:hidden;">';

        /* Thumbnail */
        echo '<div class="flex-child" style="margin-left:0.5em;">';

        switch ($row["ActivityType"])
        {
            case 'rating':
            case 'review':
            case 'comment':
            case 'review_like':
            case 'ranked_map':
                if (!empty($extra["SetID"])) {
                    echo '<a href="/mapset/' . intval($extra["SetID"]) . '">';
                    echo '<img src="https://b.ppy.sh/thumb/' . intval($extra["SetID"]) . 'l.jpg"
                            class="diffThumb"
                            onerror="this.onerror=null; this.src=\'/assets/img/missing-map-thumbnail.png\';"/>';
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
                echo '<a href="/mapset/' . intval($extra["SetID"]) . '">';
                echo safe_htmlspecialchars($row["Title"], ENT_QUOTES);
                echo '</a>';
                break;

            case 'review':
                echo 'reviewed ';
                echo '<a href="/mapset/' . intval($extra["SetID"]) . '">';
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
                echo '<a href="/mapset/' . intval($extra["SetID"]) . '">';
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
                echo '<a href="/list/?id=' . intval($row["ObjectID"]) . '">';
                echo safe_htmlspecialchars($row["Title"], ENT_QUOTES);
                echo '</a>';
                break;

            case 'review_like':
                echo 'liked a review for ';
                echo '<a href="/mapset/' . intval($extra["SetID"]) . '">';
                echo safe_htmlspecialchars($row["Title"], ENT_QUOTES);
                echo '</a>';
                break;

            case 'list_like':
                echo 'liked list ';
                echo '<a href="/list/?id=' . intval($row["ObjectType"]) . '">';
                echo safe_htmlspecialchars($row["Title"], ENT_QUOTES);
                echo '</a>';
                break;

            case 'ranked_map':
                echo 'had a difficulty ranked: ';
                echo '<a href="/mapset/' . intval($extra["SetID"]) . '">';
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
