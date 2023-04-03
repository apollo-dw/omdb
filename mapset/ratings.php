<?php
    include_once '../connection.php';
    include_once '../functions.php';
    include_once '../userConnect.php';

    $page = $_GET['p'] ?? 1;
    $mapset_id = $_GET['id'] ?? $mapset_id;

    if(!is_numeric($page) || !is_numeric($mapset_id)){
        die("NOO");
    }

    $stmt = $conn->prepare("SELECT Count(*) FROM `ratings` WHERE BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID=?) ORDER BY date DESC;");
    $stmt->bind_param("s", $mapset_id);
    $stmt->execute();

    $lim = 18;
    $counter = ($page - 1) * $lim;
    $numberOfSetRatings = $stmt->get_result()->fetch_row()[0];
    $amountOfSetPages = floor($numberOfSetRatings / $lim) + 1;

    $pageString = "LIMIT {$lim}";

    if ($page > 1){
        $lower = ($page - 1) * $lim;
        $pageString = "LIMIT {$lower}, {$lim}";
    }

    $stmt = $conn->prepare("SELECT r.*, 
        CASE
            WHEN r.UserID = ? THEN 3  -- if the rating is made by the logged-in user, give it the highest weight
            WHEN r.UserID IN (SELECT UserIDTo FROM user_relations WHERE UserIDFrom = ? AND Type = 1) THEN 2  -- if the rating is made by a friend, give it a high weight
            ELSE 1  -- for all other ratings, give a default weight
        END AS order_weight
    FROM `ratings` r 
    WHERE r.BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID = ?)
    ORDER BY order_weight DESC, date DESC {$pageString}");
    $stmt->bind_param("iii", $userId, $userId, $mapset_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()) {
        $counter += 1;
?>
<div class="flex-container ratingContainer" <?php if($row["order_weight"] == 2){ echo "style='background-color:#4F2F3F;'"; } else if($counter % 2 == 1){ echo "style='background-color:#203838;'"; } ?>>
    <div class="flex-child">
        <a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
    </div>
    <div class="flex-child" style="flex:0 0 70%;">
        <?php
        $stmt2 = $conn->prepare("SELECT DifficultyName FROM `beatmaps` WHERE `BeatmapID`=?");
        $stmt2->bind_param("s", $row["BeatmapID"]);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        $row2 = $result2->fetch_row();
        echo renderRating($conn, $row) . " on " . htmlspecialchars($row2[0]);
        ?>
    </div>
    <div class="flex-child" style="width:100%;text-align:right;">
        <?php echo GetHumanTime($row["date"]); ?>
    </div>
</div>

    <?php
        }
    ?>
<br>
<div style="text-align:center;">
    <div class="pagination">
        <b><span><?php if($page > 1) { echo "<a href='javascript:lowerRatingPage()'>&laquo; </a>"; } ?></span></b>
        <span id="page"><?php echo $page; ?></span>
        <b><span><?php if($page < $amountOfSetPages) { echo "<a href='javascript:increaseRatingPage()'>&raquo; </a>"; } ?></span></b><br>
        <span class="subText">Page</span>
    </div>
</div>
<script>
    var ratingPage = 1;

    function lowerRatingPage() {
        changeRatingPage(ratingPage - 1)
    }

    function increaseRatingPage() {
        changeRatingPage(ratingPage + 1)
    }

    function changeRatingPage(newPage) {
        ratingPage = Math.min(Math.max(newPage, 1), <?php echo $amountOfSetPages; ?>);
        updateRatings();
    }

    function updateRatings() {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange=function() {
            if (this.readyState==4 && this.status==200) {
                document.getElementById("setRatingsDisplay").innerHTML=this.responseText;
            }
        }
        xmlhttp.open("GET","ratings.php?p=" + ratingPage + "&id=" + <?php echo $mapset_id; ?>, true);
        xmlhttp.send();
    }

</script>
