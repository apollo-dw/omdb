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
    <div id="activity-listing" style="flex: 0 0 60%; width: 60%;">

    <?php
        include 'ActivityListing.php';
    ?>

    </div>
    <div style="flex: 0 0 40%; padding-left: 1em;">
        <?php
            $filterConfig = [
                'showYear' => true,
                'showRating' => false,
                'showTag' => false,
                'showActivityToggles' => true,
                'sortOptions' => [],
                'categories' => ['genre', 'language', 'country', 'descriptor', 'status'],
            ];
            require "../functions/filter/index.php";
        ?>

        <hr>
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

<script>
    function debounce(func, delay) {
        let timeoutId;
        return function (...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                func.apply(this, args);
            }, delay);
        };
    }

    function updateFeed() {
        var payload = window.getOmdbFilterPayload();

        $("#activity-listing").addClass("faded");

        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                document.getElementById("activity-listing").innerHTML = this.responseText;
                $("#activity-listing").removeClass("faded");
            }
        };

        xmlhttp.open("POST", "ActivityListing.php", true);
        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        var params =
            "ratings=" + payload.ratings +
            "&reviews=" + payload.reviews +
            "&review_likes="+ payload.review_likes +
            "&lists=" + payload.lists +
            "&list_likes=" + payload.list_likes +
            "&ranked_maps=" + payload.ranked_maps +
            "&comments=" + payload.comments +
            "&year=" + encodeURIComponent(payload.year) +
            "&tokens=" + encodeURIComponent(JSON.stringify(payload.tokens));

        xmlhttp.send(params);
    }

    const debouncedUpdateFeed = debounce(updateFeed, 100);

    $(document).on("omdbFiltersSubmitted", function() {
        debouncedUpdateFeed();
    });
</script>

<?php include '../footer.php'; ?>