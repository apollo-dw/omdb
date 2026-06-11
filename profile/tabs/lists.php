<?php
    if (file_exists("../../base.php"))
        include "../../base.php";

    $profileId = GetIntParam("id", null, "What are you trying to do man.");
?>

<div id="tabbed-lists" class="lists">

    <h3 style="padding-left: 1em;">Created lists</h3>

    <?php
    $stmt = $conn->prepare("
        SELECT
            l.ListID,
            l.Title,
            (
                SELECT COUNT(*)
                FROM list_items li
                WHERE li.ListID = l.ListID
            ) AS ItemCount
        FROM lists l
        WHERE l.UserID = ?
    ");

    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    while ($row = $result->fetch_assoc()) {

        $stmt = $conn->prepare("
            SELECT *
            FROM list_items
            WHERE ListID = ?
            AND `order` = 1
        ");

        $stmt->bind_param("i", $row["ListID"]);
        $stmt->execute();

        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        list($imageUrl, $title, $linkUrl) =
            getListItemDisplayInformation($item, $conn);
        ?>

        <div class="flex-container ratingContainer alternating-bg">
            <div class="flex-child">
                <a href="/list/?id=<?php echo $row["ListID"]; ?>">
                    <img
                        src="<?php echo $imageUrl; ?>"
                        style="height:24px;width:24px;object-fit:cover;object-position:center;"
                    />
                </a>
            </div>

            <div class="flex-child">
                <a href="/list/?id=<?php echo $row["ListID"]; ?>">
                    <?php echo htmlspecialchars($row["Title"], ENT_QUOTES); ?>
                </a>

                <span class="subText">
                    (<?php echo $row["ItemCount"]; ?> items)
                </span>
            </div>
        </div>

        <?php
    }
    ?>

    <hr />
    <h3 style="padding-left: 1em;">Hearted lists</h3>

    <?php
    $stmt = $conn->prepare("
        SELECT
            l.ListID,
            l.Title,
            (
                SELECT COUNT(*)
                FROM list_items li
                WHERE li.ListID = l.ListID
            ) AS ItemCount
        FROM list_hearts lh
        INNER JOIN lists l
            ON l.ListID = lh.ListID
        WHERE lh.UserID = ?
        AND l.UserID != ?
    ");

    $stmt->bind_param("ii", $profileId, $profileId);
    $stmt->execute();

    $heartedLists = $stmt->get_result();
    $stmt->close();

    while ($row = $heartedLists->fetch_assoc()) {

        $stmt = $conn->prepare("
            SELECT *
            FROM list_items
            WHERE ListID = ?
            AND `order` = 1
        ");

        $stmt->bind_param("i", $row["ListID"]);
        $stmt->execute();

        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        list($imageUrl, $title, $linkUrl) =
            getListItemDisplayInformation($item, $conn);
        ?>

        <div class="flex-container ratingContainer alternating-bg">
            <div class="flex-child">
                <a href="/list/?id=<?php echo $row["ListID"]; ?>">
                    <img
                        src="<?php echo $imageUrl; ?>"
                        style="height:24px;width:24px;object-fit:cover;object-position:center;"
                    />
                </a>
            </div>

            <div class="flex-child">
                <a href="/list/?id=<?php echo $row["ListID"]; ?>">
                    <?php echo htmlspecialchars($row["Title"], ENT_QUOTES); ?>
                </a>

                <span class="subText">
                    (<?php echo $row["ItemCount"]; ?> items)
                </span>
            </div>
        </div>

        <?php
    }
    ?>

</div>