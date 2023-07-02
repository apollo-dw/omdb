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

    <style>
        .friend-container {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
        }

        .friend-box {
            flex: 0 0 calc(100% / 8);
            display: flex;
            margin: 0.5em;
            padding: 1em;
            text-align: center;
            aspect-ratio: 1 / 1;
            vertical-align: middle;
            align-items: center;
            justify-content: center;
        }

        .mutual-friend {
            background-color: #6A4256;
            color: white;
        }

        .non-mutual-friend {
            background-color: DarkSlateGrey;
            color: white;
        }
    </style>

    <div class="friend-container">
        <?php
        while ($row = $friends->fetch_assoc()) {
            $friendClass = $row["isMutualFriend"] ? "mutual-friend" : "non-mutual-friend";
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