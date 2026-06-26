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
        <b>Filtering options</b> <br>
        <input type="checkbox" id="ratings" name="ratings" value="ratings" checked>
        <label for="ratings">Ratings</label><br>
        <input type="checkbox" id="reviews" name="reviews" value="reviews" checked>
        <label for="reviews">Reviews</label><br>
        <input type="checkbox" id="review_likes" name="review_likes" value="review_likes" checked>
        <label for="review_likes">Review likes</label><br>
        <input type="checkbox" id="lists" name="lists" value="lists" checked>
        <label for="lists">Lists</label><br>
        <input type="checkbox" id="list_likes" name="list_likes" value="list_likes" checked>
        <label for="list_likes">List likes</label><br>
        <input type="checkbox" id="ranked_maps" name="ranked_maps" value="ranked_maps" checked>
        <label for="ranked_maps">Ranked maps</label><br>
        <input type="checkbox" id="comments" name="comments" value="comments" checked>
        <label for="comments">Comments</label><br><br>
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
        $("#activity-listing").addClass("faded");
		var ratings = document.getElementById("ratings").checked;
		var reviews = document.getElementById("reviews").checked;
        var review_likes = document.getElementById("review_likes").checked;
        var lists = document.getElementById("lists").checked;
        var list_likes = document.getElementById("list_likes").checked;
        var ranked_maps = document.getElementById("ranked_maps").checked;
        var comments = document.getElementById("comments").checked;

		var xmlhttp = new XMLHttpRequest();
		xmlhttp.onreadystatechange=function() {
			if (this.readyState==4 && this.status==200) {
				document.getElementById("activity-listing").innerHTML=this.responseText;
                $("#activity-listing").removeClass("faded"); 
			}
		}

        xmlhttp.open("POST", "ActivityListing.php", true);
        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        var params = "ratings=" + ratings +
            "&reviews=" + reviews +
            "&review_likes=" + review_likes +
            "&lists=" + lists +
            "&list_likes=" + list_likes +
            "&ranked_maps=" + ranked_maps +
            "&comments=" + comments;
        xmlhttp.send(params);
	}

    const debouncedUpdateFeed = debounce(updateFeed, 1000);

    $(document).ready(function() {
        $('#ratings, #reviews, #review_likes, #lists, #list_likes, #ranked_maps').on('change', function() {
            $("#activity-listing").addClass("faded");
            debouncedUpdateFeed();
        });
    });
</script>
<?php
    include '../footer.php';
?>
