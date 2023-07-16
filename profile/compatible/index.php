<?php
    $profileId = $_GET['id'] ?? -1;
    $PageTitle = "Similar users";

    require "../../base.php";
    require '../../header.php';

    if($profileId == -1 || !is_numeric($profileId)){
    die("Invalid page bro");
    }

    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `UserID` = ?");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();
    $isUser = true;

    if ($profile == NULL)
    die("Can't view this bros friends cuz they aint an OMDB user");

    $stmt = $conn->prepare("
        SELECT users.*, correlated_users.correlation
        FROM users 
        INNER JOIN (
            SELECT IF(user1_id = ?, user2_id, user1_id) AS id, correlation
            FROM user_correlations 
            WHERE ? IN (user1_id, user2_id) 
            ORDER BY correlation DESC 
            LIMIT 50
        ) AS correlated_users 
        ON users.UserID = correlated_users.id;
    ");

    $stmt->bind_param("ii", $profileId, $profileId);
    $stmt->execute();
    $similarUsers = $stmt->get_result();
    $stmt->close();
?>

<center><h1><a href="/profile/<?php echo htmlspecialchars($profileId, ENT_QUOTES, 'UTF-8'); ?>"><?php echo GetUserNameFromId($profileId, $conn); ?></a>'s most similar users</h1></center>

<div class="flex-row-container" style="width:50%;margin:auto;">
    <?php
    while ($row = $similarUsers->fetch_assoc()) {
    ?>
        <div class="flex-container alternating-bg" style="align-items: center;">
            <a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:4em;width:4em;padding:1em;" title="<?php echo GetUserNameFromId($row["UserID"], $conn); ?>"/></a>
            <a href="/profile/<?php echo $row["UserID"]; ?>"><?php echo GetUserNameFromId($row["UserID"], $conn); ?></a>
            <span style="margin-left:auto;padding:1em;"><?php echo $row["correlation"]; ?></span>
        </div>
    <?php
    }
    ?>
</div>


<?php
require '../../footer.php';
?>