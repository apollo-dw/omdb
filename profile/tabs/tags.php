<?php
    if (file_exists("../../base.php"))
        include "../../base.php";

    $profileId = GetIntParam("id", null, "What are you trying to do man.");
?>

<div id="tabbed-tags" class="tab" style="padding: 2em;">
    <?php
    $stmt = $conn->prepare("SELECT Tag, COUNT(*) AS TagCount FROM rating_tags WHERE UserID = ? GROUP BY Tag ORDER BY TagCount DESC;");
    $stmt->bind_param('i', $profileId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $tag = htmlspecialchars($row["Tag"], ENT_QUOTES, "ISO-8859-1");
        $encodedTag = urlencode($row["Tag"]);
        echo "<a href='ratings/?id={$profileId}&t={$encodedTag}'>{$tag} ({$row["TagCount"]})</a> <br>";
    }
    ?>
</div>
