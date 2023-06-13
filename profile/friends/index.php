<?php
    $profileId = $_GET['id'] ?? -1;
    $PageTitle = "Comments";

    require "../../base.php";
    require '../../header.php';

    if($profileId == -1){
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

    $stmt = $conn->prepare("SELECT u.UserID as ID, u.Username as username FROM users u
                                JOIN user_relations ur1 ON u.UserID = ur1.UserIDTo
                                JOIN user_relations ur2 ON u.UserID = ur2.UserIDFrom
                                WHERE ur1.UserIDFrom = ? AND ur2.UserIDTo = ?
                                AND ur1.type = 1 AND ur2.type = 1
                                ORDER BY LastAccessedSite DESC, ID;");
    $stmt->bind_param("ii", $profileId, $profileId);
    $stmt->execute();
    $friends = $stmt->get_result();
    $stmt->close();

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