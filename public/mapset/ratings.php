<?php
    require_once __DIR__ . '/../../app/base.php';

    $page = GetIntParam('p', 1, "NOO");
    $mapset_id = GetIntParam('id', $mapset_id ?? null, "NOO");
    $beatmap_id = GetIntParam('bID', -1, "NOO");
    $order = $_GET['o'] ?? "newest";

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
    if ($order == "oldest") {
        $orderString = "date ASC";
    }
    if ($order == "rating") {
        $orderString = "score DESC";
    }

    $mainQuery = "SELECT
                    r.*,
                    mn.Username,
                    beatmaps.DifficultyName,
                    IF(r.UserID IN (SELECT UserIDTo FROM user_relations WHERE UserIDFrom = ? AND Type = 1), 2, 1) AS order_weight,
                    (
                        SELECT GROUP_CONCAT(t.`Tag` SEPARATOR ', ') FROM `rating_tags` t
                        WHERE t.`BeatmapID` = r.`BeatmapID` AND t.`UserID` = r.`UserID`
                    ) AS Tags,
                    IF(
                        (
                            SELECT u.IsPrivate
                            FROM users u
                            WHERE u.UserID = r.UserID
                        ) = 1
                        AND NOT (
                            EXISTS (
                                SELECT 1
                                FROM user_relations ur1
                                WHERE ur1.UserIDFrom = ?
                                AND ur1.UserIDTo = r.UserID
                                AND ur1.Type = 1
                            )
                            AND EXISTS (
                                SELECT 1
                                FROM user_relations ur2
                                WHERE ur2.UserIDFrom = r.UserID
                                AND ur2.UserIDTo = ?
                                AND ur2.Type = 1
                            )
                        ),
                        1,
                        0
                    ) AS IsPrivate
                FROM `ratings` r
                LEFT JOIN beatmaps ON r.BeatmapID = beatmaps.BeatmapID
                LEFT JOIN mappernames mn ON mn.UserID = r.UserID
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

    if ($page > 1) {
        $lower = ($page - 1) * $lim;
        $pageString = "LIMIT {$lower}, {$lim}";
    }

    $stmt = $conn->prepare($mainQuery . " " . $pageString);
    $stmt->bind_param(
        "ii" . $bindParamsMain,
        $userId, $userId, ...$bindValuesMain
    );
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $isPrivate = $row["IsPrivate"] && $row["UserID"] != $userId;

?>
<div class="flex-container ratingContainer <?php echo ($row["order_weight"] == 2) ? 'alternating-bg-pink' : 'alternating-bg'; ?>">
    <div class="flex-child">
        <?php if ($isPrivate) { ?>
            <img class="square-thumb" src="/assets/img/missing-map-thumbnail.png" style="height:24px;width:24px;"/>
        <?php } else { ?>
            <a href="/profile/<?php echo $row["UserID"]; ?>">
                <img class="square-thumb" src="<?php echo "https://s.ppy.sh/a/{$row["UserID"]}"; ?>" style="height:24px;width:24px;" title="<?php echo safe_htmlspecialchars($row["Username"] ?? GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>"/>
            </a>
        <?php } ?>
    </div>
    <div class="flex-child" style="flex:0 0 70%;">
        <?php if ($isPrivate) { ?>
            <span style="display:flex; font-style: italic;">
                hidden
            </span>
        <?php } else { ?>
            <a style="display:flex;" href="/profile/<?php echo $row["UserID"]; ?>">
                <?php echo safe_htmlspecialchars($row["Username"] ?? GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>
            </a>
        <?php } ?>

        <?php
            echo RenderUserRating($conn, $row) . " on " . safe_htmlspecialchars(mb_strimwidth($row["DifficultyName"], 0, 40, "..."), ENT_QUOTES);
        ?>

    </div>
    <div class="flex-child" style="width:100%;text-align:right;">
        <?php if (strlen($row["Tags"] ?? "") > 0) { ?>
            <i title='<?php echo safe_htmlspecialchars($row["Tags"], ENT_QUOTES) ?>' style='border-bottom:1px dotted var(--main-theme-text-color);' class="icon-tags"></i>
        <?php } ?>
        <?php RenderHumanTime($row["date"]); ?>
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

        while ($row = $result->fetch_assoc()) {
            $selectedString = $beatmap_id == $row['BeatmapID'] ? "selected" : "";
            $difficultyName = safe_htmlspecialchars(mb_strimwidth($row['DifficultyName'], 0, 40, "..."), ENT_QUOTES);
            echo "<option value='{$row['BeatmapID']}' {$selectedString}>{$difficultyName}</option>";
        }
    ?>
</select>
<br>

<label for="rating-order">
    Order:
</label>
<select name="rating-order" id="rating-order" onchange="updateRatings()">
    <option value="newest" <?php if ($order === 'newest') {
        echo 'selected';
    } ?>>Date (newest)</option>
    <option value="oldest" <?php if ($order === 'oldest') {
        echo 'selected';
    } ?>>Date (oldest)</option>
    <option value="rating" <?php if ($order === 'rating') {
        echo 'selected';
    } ?>>Highest score</option>
</select>

<div style="text-align:center;">
    <div class="pagination">
        <b><span><?php if ($page > 1) {
            echo "<a href='javascript:lowerRatingPage()'>&laquo; </a>";
        } ?></span></b>
        <span id="page"><?php echo $page; ?></span>
        <b><span><?php if ($page < $amountOfSetPages) {
            echo "<a href='javascript:increaseRatingPage()'>&raquo; </a>";
        } ?></span></b><br>
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
