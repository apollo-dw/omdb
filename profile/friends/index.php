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

$stmt = $conn->prepare("
    SELECT u.UserID AS ID, u.Username AS username,
    CASE
        WHEN ur1.UserIDTo IS NOT NULL AND ur2.UserIDFrom IS NOT NULL THEN 1
        WHEN ur1.UserIDTo IS NOT NULL AND ur2.UserIDFrom IS NULL THEN 0
        ELSE 0
    END AS isMutualFriend
    FROM users u
    LEFT JOIN user_relations ur1 ON u.UserID = ur1.UserIDFrom AND ur1.UserIDTo = ?
    LEFT JOIN user_relations ur2 ON u.UserID = ur2.UserIDTo AND ur2.UserIDFrom = ?
    WHERE (ur1.UserIDTo IS NOT NULL OR ur2.UserIDFrom IS NOT NULL)
        AND ur1.type = 1
    ORDER BY LastAccessedSite DESC, ID;
");

    $stmt->bind_param("ii", $profileId, $profileId);
    $stmt->execute();
    $friends = $stmt->get_result();
    $stmt->close();

    ?>
<center><h1><a href="/profile/<?php echo $profileId; ?>"><?php echo GetUserNameFromId($profileId, $conn); ?></a>'s friends</h1></center>

    <div class="flex-row-container">
        <?php
        while ($row = $friends->fetch_assoc()) {
            $friendClass = $row["isMutualFriend"] ? "pink-background" : "";
            ?>
            <div class="friend-box <?php echo $friendClass; ?>">
                <a href="/profile/<?php echo $row["ID"]; ?>">
                    <div class="profileImage">
                        <img src="https://s.ppy.sh/a/<?php echo $row["ID"]; ?>" style="width:5em;height:5em;"/><br>
                        <?php echo $row["username"]; ?>
                    </div>
                </a>
            </div>
            <?php
        }
        ?>
    </div>


<?php
require '../../footer.php';
?>