<?php
    $PageTitle = "Lists";
    include '../header.php';

    ?>

<style>
    h1 {
        margin: 0;
    }

    .list-container {
        box-sizing: border-box;
        min-height: 12em;
        display: flex;
        align-items: center;
        padding: 1em;
    }
</style>
<h1>Lists</h1>
<br>
<a href="../list/edit/">Create new list</a>
<hr>

<div>
    <?php
    $stmt = $conn->prepare("SELECT l.ListID, l.Title, l.Description, l.UserID, l.Private, (SELECT COUNT(*) FROM list_hearts lh WHERE lh.ListID = l.ListID) AS HeartCount, (SELECT COUNT(*) FROM list_items li WHERE li.ListID = l.ListID) AS ItemCount 
    FROM lists l
    WHERE l.Private = 0 OR l.UserID = ?
    ORDER BY COALESCE(l.UpdatedAt, l.CreatedAt) DESC;");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    while ($row = $result->fetch_assoc()) {
        $stmt = $conn->prepare("SELECT * FROM list_items WHERE `ListID` = ? AND `order` = 1;");
        $stmt->bind_param("i", $row["ListID"]);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();

        list($imageUrl, $title, $linkUrl) = getListItemDisplayInformation($item, $conn);
        ?>
        <div class="flex-container alternating-bg list-container">
            <div class="flex-child">
                <a href="/list/?id=<?php echo $row["ListID"]; ?>"><img src="<?php echo $imageUrl; ?>" style="height:8em;width:8em;object-fit:cover;object-position:center;"/></a>
            </div>
            <div class="flex-child" style="align-self:baseline;">
                <b><a href="/list/?id=<?php echo $row["ListID"]; ?>"><?php echo safe_htmlspecialchars($row["Title"], ENT_QUOTES); ?></a></b> <span class="subText">by <a href="/profile/<?php echo $row["UserID"]; ?>"><?php echo safe_htmlspecialchars(GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?></a><?php if (!empty($row["Private"])) echo " | private"; ?></span>
                <span class="subText">(<?php echo $row["ItemCount"]; ?> items)</span> <br>
                <span class="subText"><?php echo $row["HeartCount"]; ?> <i class="icon-heart"></i></span> <br><br>

                <?php echo explode("\n", ParseCommentLinks($conn, $row["Description"]))[0]; ?>
            </div>
        </div>
        <?php
    }
    ?>
</div>

<?php
    $PageTitle = "Lists";
    include '../footer.php';

    ?>