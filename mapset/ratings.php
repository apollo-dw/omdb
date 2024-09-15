<?php
    include_once '../connection.php';
    include_once '../functions.php';
    include_once '../userConnect.php';

    $page = $_GET['p'] ?? 1;
    $mapset_id = $_GET['id'] ?? $mapset_id;
    $beatmap_id = $_GET['bID'] ?? -1;
    $order = $_GET['o'] ?? "newest";

    if(!is_numeric($page) || !is_numeric($mapset_id)){
        die("NOO");
    }

    if ($beatmap_id == -1) {
        $countQuery = "SELECT Count(*) FROM `ratings` WHERE BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID = ?)";
        $selectString = "WHERE r.BeatmapID IN (SELECT BeatmapID FROM beatmaps WHERE SetID = ?)";
        $bindParams = "i";
        $bindValues = [$mapset_id];
        $bindParamsMain = "ii";
        $bindValuesMain = [$userId, $mapset_id];
    } else {
        $countQuery = "SELECT Count(*) FROM `ratings` WHERE BeatmapID = ?";
        $selectString = "WHERE r.BeatmapID = ?";
        $bindParams = "i";
        $bindValues = [$beatmap_id];
        $bindParamsMain = "ii";
        $bindValuesMain = [$userId, $beatmap_id];
    }

    $orderString = "date DESC";
    if($order == "oldest")
        $orderString = "date ASC";
    if($order == "rating")
        $orderString = "score DESC";

$mainQuery = 
    "SELECT r.*, CASE WHEN (r.UserID IN (SELECT UserIDTo FROM user_relations WHERE UserIDFrom = ? AND Type = 1)) THEN 2 WHEN r.UserID={$userID} THEN 3 ELSE 1 END AS order_weight,
    (SELECT GROUP_CONCAT(t.`Tag` SEPARATOR ', ') FROM `rating_tags` t 
    WHERE t.`BeatmapID` = r.`BeatmapID` AND t.`UserID` = r.`UserID`) AS Tags
    FROM `ratings` r
    LEFT JOIN beatmaps ON r.BeatmapID = beatmaps.BeatmapID
    {$selectString}
    AND beatmaps.Blacklisted = 0
    ORDER BY order_weight DESC, {$orderString}";

    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param($bindParams, ...$bindValues);
    $stmt->execute();

    $lim = 18;
    $numberOfSetRatings = $stmt->get_result()->fetch_row()[0];
    $amountOfSetPages = floor($numberOfSetRatings / $lim) + 1;

    $pageString = "LIMIT {$lim}";

    if ($page > 1){
        $lower = ($page - 1) * $lim;
        $pageString = "LIMIT {$lower}, {$lim}";
    }

    $stmt = $conn->prepare($mainQuery . " " . $pageString);
    $stmt->bind_param($bindParamsMain, ...$bindValuesMain);
    $stmt->execute();
    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()) {
?>
<div class="flex-container ratingContainer <?php echo ($row["order_weight"] == 2) ? 'alternating-bg-pink' : ($row["order_weight"] == 3) ? 'bg-self' : 'alternating-bg'; ?>"
    <div class="flex-child">
        <a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
    </div>
    <div class="flex-child" style="flex:0 0 70%;">
        <a style="display:flex;" href="/profile/<?php echo $row["UserID"]; ?>">
            <?php echo GetUserNameFromId($row["UserID"], $conn); ?>
        </a>
        <?php
            $stmt2 = $conn->prepare("SELECT DifficultyName FROM `beatmaps` WHERE `BeatmapID`=?");
            $stmt2->bind_param("s", $row["BeatmapID"]);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $row2 = $result2->fetch_row();
            echo RenderUserRating($conn, $row) . " on " . htmlspecialchars(mb_strimwidth($row2[0], 0, 40, "..."));
        ?>
    </div>
    <div class="flex-child" style="width:100%;text-align:right;">
        <?php if (strlen($row["Tags"]) > 0) { ?>
            <i title='<?php echo $row["Tags"] ?>' style='border-bottom:1px dotted white;' class="icon-tags"></i>
        <?php } ?>
        <?php echo GetHumanTime($row["date"]); ?>
    </div>
</div>

<?php
    }
?>

<label for="difficulties">
    Difficulty:
</label>
<select name="difficulties" id="difficulties" onchange="updateRatings()">
    <option value="-1">Any</option>
    <?php
        $stmt = $conn->prepare("SELECT DifficultyName, BeatmapID FROM beatmaps WHERE `SetID` = ? AND Blacklisted = 0;");
        $stmt->bind_param("i", $mapset_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while($row = $result->fetch_assoc()){
            $selectedString = $beatmap_id == $row['BeatmapID'] ? "selected" : "";
            $difficultyName = htmlspecialchars(mb_strimwidth($row['DifficultyName'], 0, 40, "..."));
            echo "<option value='{$row['BeatmapID']}' {$selectedString}>{$difficultyName}</option>";
        }
    ?>
</select>
<br>

<label for="rating-order">
    Order:
</label>
<select name="rating-order" id="rating-order" onchange="updateRatings()">
    <option value="newest" <?php if ($order === 'newest') echo 'selected'; ?>>Date (newest)</option>
    <option value="oldest" <?php if ($order === 'oldest') echo 'selected'; ?>>Date (oldest)</option>
    <option value="rating" <?php if ($order === 'rating') echo 'selected'; ?>>Highest score</option>
</select>

<div style="text-align:center;">
    <div class="pagination">
        <b><span><?php if($page > 1) { echo "<a href='javascript:lowerRatingPage()'>&laquo; </a>"; } ?></span></b>
        <span id="page"><?php echo $page; ?></span>
        <b><span><?php if($page < $amountOfSetPages) { echo "<a href='javascript:increaseRatingPage()'>&raquo; </a>"; } ?></span></b><br>
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

        var difficulty = document.getElementById("difficulties").value;
        var order = document.getElementById("rating-order").value;

        xmlhttp.onreadystatechange=function() {
            if (this.readyState==4 && this.status==200) {
                document.getElementById("setRatingsDisplay").innerHTML=this.responseText;
            }
        }

        xmlhttp.open("GET","ratings.php?p=" + ratingPage + "&id=" + <?php echo $mapset_id; ?> + "&bID=" + difficulty + "&o=" + order, true);
        xmlhttp.send();
    }

</script>
