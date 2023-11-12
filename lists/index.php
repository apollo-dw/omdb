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
<span class="subText">This listing shows the 25 latest updated lists.</span> <br><br>

<a href="../list/edit/">Create new list</a>
<hr>

<div>
    <?php
    $stmt = $conn->prepare("SELECT l.ListID, l.Title, l.Description, l.UserID,
                                  (SELECT COUNT(*) FROM list_hearts lh WHERE lh.ListID = l.ListID) AS HeartCount,
                                  (SELECT COUNT(*) FROM list_items li WHERE li.ListID = l.ListID) AS ItemCount 
                                  FROM lists l ORDER BY UpdatedAt LIMIT 25;");
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
                <b><a href="/list/?id=<?php echo $row["ListID"]; ?>"><?php echo htmlspecialchars($row["Title"]); ?></a></b> <span class="subText">by <a href="/profile/<?php echo $row["UserID"]; ?>"><?php echo GetUserNameFromId($row["UserID"], $conn); ?></a></span>
                <span class="subText">(<?php echo $row["ItemCount"]; ?> items)</span> <br>
                <span class="subText"><?php echo $row["HeartCount"]; ?> <i class="icon-heart"></i></span> <br><br>

                <?php echo explode("\n", ParseCommentLinks($conn, nl2br(htmlspecialchars($row["Description"]))))[0]; ?>
            </div>
        </div>
        <?php
    }
    ?>
</div>

