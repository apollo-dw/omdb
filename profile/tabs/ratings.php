<?php
    include "../../base.php";

    $profileId = $_GET["id"];
    $maxRating = $_GET["maxRating"];
    $stmt = $conn->prepare("SELECT * FROM `users` WHERE `UserID` = ?");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    $stmt->close();
?>

<style>
    #tabbed-ratings .profile-rating-distribution-bar{
        background-color: lightgray;
        margin: 0;
        padding: 0;
        height: 2em;
        text-align: left;
        white-space: nowrap;
    }

    #tabbed-ratings table, #tabbed-ratings tr, #tabbed-ratings td{
        text-align: center;
        vertical-align: middle;
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        border-spacing: 0;
    }

    #tabbed-ratings tr, #tabbed-ratings td {
        padding: 0.1em;
        height: 4em;
    }
</style>

<div id="tabbed-ratings" class="tab">
    <table style="width:100%;">
        <?php for ($rating = 5.0; $rating >= 0.0; $rating -= 0.5){ ?>
            <?php
            $formattedRating = number_format($rating, 1);
            $ratingCount = $ratingCounts[$formattedRating] ?? 0;
            $ratingBarWidth = ($ratingCount / $maxRating) * 90;
            ?>
            <tr class="alternating-bg">
                <td style="width:20%;">
                    <a href="ratings/?id=<?php echo $profileId; ?>&r=<?php echo $formattedRating; ?>&p=1"><?php echo $formattedRating; ?><br>
                        <?php if ($profile["Custom" . str_replace('.', '', $formattedRating) . "Rating"] != ""){ ?>
                            <span class="subText"><?php echo htmlspecialchars($profile["Custom" . str_replace('.', '', $formattedRating) . "Rating"]); ?></span>
                        <?php } ?>
                    </a>
                </td>
                <td style="width:5%;">
                    <?php echo $ratingCount; ?>
                </td>
                <td style="width:100%;">
                    <a href="ratings/?id=<?php echo $profileId; ?>&r=<?php echo $formattedRating; ?>&p=1"> <div class="profile-rating-distribution-bar" style="width: <?php echo $ratingBarWidth; ?>%;">&nbsp;</div></a>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
