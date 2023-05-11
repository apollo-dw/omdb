<?php
    $profileId = $_GET['id'] ?? -1;
    $PageTitle = "Comments";

    require "../../base.php";
    require '../../header.php';

    if($profileId == -1){
        die("Invalid page bro");
    }

    $profile = $conn->query("SELECT * FROM `users` WHERE `UserID`='${profileId}';")->fetch_row()[0];
    $isUser = true;

    if ($profile == NULL){
        die("Can't view this bros friends cuz they aint an OMDB user");
    }

    $friends = $conn->query("SELECT u.UserID as ID, u.Username as username FROM users u
                                   JOIN user_relations ur1 ON u.UserID = ur1.UserIDTo
                                   JOIN user_relations ur2 ON u.UserID = ur2.UserIDFrom
                                   WHERE ur1.UserIDFrom = {$profileId} AND ur2.UserIDTo = {$profileId}
                                   AND ur1.type = 1 AND ur2.type = 1
                                   ORDER BY LastAccessedSite DESC, ID;");

    ?>

<center><h1><a href="/profile/<?php echo $profileId; ?>"><?php echo GetUserNameFromId($profileId, $conn); ?></a>'s friends</h1></center>
<span class="subText">Better design coming soon. This is in a rushed state right now lol. Will eventually show non-mutual friends too (somehow) </span><br><br>

<?php
    while($row = $friends->fetch_assoc()){
        echo "<a href='../?id={$row["ID"]}'>{$row["username"]}</a><br>";
    }
?>

<?php
require '../../footer.php';
?>