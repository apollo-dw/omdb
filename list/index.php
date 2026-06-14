<?php
    $PageTitle = "List";
    require "../base.php";
    require '../header.php';

    $listId = GetIntParam('id', -1, "How dare you");

    $stmt = $conn->prepare("SELECT * FROM `lists` WHERE `ListID` = ? AND (`Private` = 0 OR `UserID` = ?);");
    $stmt->bind_param("ii", $listId, $userId);
    $stmt->execute();
    $list = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (is_null($list))
        die("List not found");

    $title = htmlspecialchars($list['Title'], ENT_QUOTES);

    $stmt = $conn->prepare("
        SELECT
            COUNT(*) AS totalHearts,
            SUM(CASE WHEN UserID = ? THEN 1 ELSE 0 END) AS userLiked,
            GROUP_CONCAT(UserID) AS heartedUsers
        FROM list_hearts
        WHERE ListID = ?
    ");
    $stmt->bind_param("ii", $userId, $listId);
    $stmt->execute();

    $heartData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $listHeartCount = (int)$heartData['totalHearts'];
    $userHasLikedList = ((int)$heartData['userLiked']) > 0;

    $heartedUserIds = [];
    if (!empty($heartData['heartedUsers'])) {
        $heartedUserIds = array_map('intval', explode(',', $heartData['heartedUsers']));
    }
    $heartedUsernames = [];

    foreach ($heartedUserIds as $uid) {
        $heartedUsernames[] =
            "<span style='white-space: nowrap;'>" .
            htmlspecialchars(GetUserNameFromId($uid, $conn), ENT_QUOTES) .
            "</span>";
    }

    $heartedUsernamesString = implode(", ", $heartedUsernames);
?>

<style>
    .container {
        background-color: DarkSlateGrey;
        width: 100%;
        box-sizing: border-box;
        padding: 1em;
    }

    .container h1 {
        margin: 0;
    }

    .list-container {
        display: flex;
        flex-direction: column;
        width: 100%;
        box-sizing: border-box;
    }

    .list-item {
        box-sizing: border-box;
        min-height: 12em;
        display:flex;
        align-items: center;
        padding: 1em;
    }

    .list-item>div {
        margin-left:1em;
    }

    #list-heart {
        cursor: pointer;
    }
</style>

<div class="container">
    <?php if ($loggedIn) { ?>
        <div class="tooltip-wrapper" style="float:right;">
            <span class="subText">[<?php echo $listHeartCount; ?>]</span>

            <i
                id="list-heart"
                class="icon-heart<?php if (!$userHasLikedList) echo "-empty"; ?>"
            ></i>
            <?php if ($heartedUsernamesString) { ?>
                <div class="tooltip-box">
                    <?php echo $heartedUsernamesString; ?>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
    <h1><?php echo $title; ?></h1>
    <span class="subText">
        Made by <a href="../profile/<?php echo $list["UserID"]; ?>"><?php echo htmlspecialchars(GetUserNameFromId($list["UserID"], $conn), ENT_QUOTES); ?></a><br>
        <?php if (!empty($list["Private"])) { ?><span class="subText">Private list</span><br><?php } ?>
    </span>
    <hr>
    <div>
        <?php echo ParseCommentLinks($conn, $list['Description']); ?>
    </div>
</div>

<?php if ($loggedIn && $userId == $list["UserID"]) { ?>
    <div style="margin-top: 1em;">
            <a href="edit/?id=<?php echo $listId; ?>"><span class="subText"><i class="icon-edit"></i> Edit</span></a>
    </div>
<?php } ?>
<hr>

<div class="list-container">
    <?php
    $stmt = $conn->prepare("SELECT * FROM list_items WHERE ListID = ?;");
    $stmt->bind_param("i", $listId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    while ($listItem = $result->fetch_assoc()){
        list($imageUrl, $title, $linkUrl) = getListItemDisplayInformation($listItem, $conn);
        ?>
        <div class="list-item alternating-bg">
            <div>
                #<?php echo $listItem['order']; ?>
            </div>
            <div style="text-align: center; width: 8em;">
                <a href="<?php echo $linkUrl; ?>"><img src="<?php echo $imageUrl; ?>" class="diffThumb" style="height: 8em; width: 8em;" onerror="this.onerror=null; this.src='../../../charts/INF.png';" /></a><br>
                <span class="subText"><?php echo htmlspecialchars($title, ENT_QUOTES); ?></span>
            </div>
            <div style="flex-grow: 1; box-sizing: border-box;">
                <?php echo ParseCommentLinks($conn, $listItem['Description']); ?>
            </div>
        </div>
        <?php
    }
    ?>
</div>

<script>
    $('#list-heart').on('click', function() {
        $.ajax({
            type: 'POST',
            url: 'HeartList.php',
            data: { bID: <?php echo $listId; ?> },
            dataType: 'json',
            success: function(response) {
                if (response.state === 1) {
                    $('#list-heart').removeClass('icon-heart-empty').addClass('icon-heart');
                } else if (response.state === 0) {
                    $('#list-heart').removeClass('icon-heart').addClass('icon-heart-empty');
                }
            }
        });
    });
</script>

<?php
    require '../footer.php';
?>