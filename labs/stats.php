<?php
    $PageTitle = "Labs | Stats";
    require "../base.php";
    require '../header.php';    
?>

<h1> Stats </h1>

<hr>


<b>Most credited mappers</b>
    <div id="credit-ranking" style="width:32em;">
    <?php
        $stmt = $conn->prepare("SELECT mn.UserID, mn.Username, COUNT(*) AS CreditCount
                                FROM beatmapset_credits AS bc
                                JOIN mappernames AS mn ON bc.UserID = mn.UserID
                                GROUP BY mn.UserID, mn.Username
                                ORDER BY CreditCount DESC, mn.Username ASC
                                LIMIT 20;");
        $stmt->execute();
        $result = $stmt->get_result();

        while($row = $result->fetch_assoc()) {
    ?>

    <div class="flex-container ratingContainer alternating-bg">
        <div class="flex-child">
            <a href="/profile/<?php echo $row["UserID"]; ?>"><img src="https://s.ppy.sh/a/<?php echo $row["UserID"]; ?>" style="height:24px;width:24px;" title="<?php echo safe_htmlspecialchars($row["Username"] ?? GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>"/></a>
        </div>
        <div class="flex-child" style="flex:0 0 50%;">
            <a style="display:flex;" href="/profile/<?php echo $row["UserID"]; ?>">
                <?php echo safe_htmlspecialchars($row["Username"] ?? GetUserNameFromId($row["UserID"], $conn), ENT_QUOTES); ?>
            </a>
        </div>
        <div class="flex-child" style="width:100%;text-align:right;">
            credited <?php echo $row["CreditCount"]; ?> times
        </div>
    </div>

    <?php
        }
    ?>
</div>

<?php
    require "../footer.php";
?>