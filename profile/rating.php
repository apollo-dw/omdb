<?php
    include_once '../connection.php';
    include_once '../functions.php';

    $stmt = $conn->prepare("SELECT Count(*) as count FROM rating_tags WHERE UserID = ?;");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $tagCount = $stmt->get_result()->fetch_assoc()["count"];
    $stmt->close();

    $stmt = $conn->prepare("SELECT Count(*) as count FROM beatmapset_nominators WHERE NominatorID = ?;");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $nominationCount = $stmt->get_result()->fetch_assoc()["count"];
    $stmt->close();

    $stmt = $conn->prepare("SELECT Count(*) as count FROM lists WHERE UserID = ?;");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $listCount = $stmt->get_result()->fetch_assoc()["count"];
    $stmt->close();
?>

<style>
    #tabbed-stats .year-box{
        width: 3.5em;
        display: flex;
        padding: 0.5em;
        text-align: center;
        aspect-ratio: 1 / 1;
        vertical-align: middle;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        border: 1px solid white;
        background-color: black;
        color: rgba(0, 0, 0, 0.7);
    }
</style>

<div class="tabbed-container-nav">
    <?php if ($isValidUser) { ?>
        <button data-tab="latest" class="active">Latest</button>
        <button data-tab="ratings">Ratings</button>
        <?php if ($tagCount > 0) { ?>
            <button data-tab="tags">Tags (<?php echo $tagCount; ?>)</button>
        <?php } ?>
        <button data-tab="stats">Stats</button>
        <?php if ($listCount > 0) { ?>
            <button data-tab="lists">Lists (<?php echo $listCount; ?>)</button>
        <?php } ?>
    <?php } ?>
    <?php if ($nominationCount > 0) { ?>
        <button data-tab="nominations" <?php if (!$isValidUser) { echo "class='active'"; } ?>>Nominations (<?php echo $nominationCount; ?>)</button>
    <?php } ?>
</div>

<div id="current-tab">
    <?php if($isValidUser) {
        include 'tabs/latest.php';
    } else if ($nominationCount > 0) {
        include 'tabs/nominations.php';
    } ?>
</div>

<script>
    const tabContent = {};

    $(".tabbed-container-nav button").on("click", function () {
        const tabName = $(this).data("tab");
        $(".tabbed-container-nav button").removeClass("active");
        $(this).addClass("active");
        showTab(tabName);
    });

    function showTab(tabName, onlyCache = false) {
        if (tabContent[tabName]) {
            $("#current-tab").html(tabContent[tabName]);
        } else {
            let dataToSend = { tab: tabName };

            <?php if($isValidUser) { ?>
            if (tabName === "ratings") {
                dataToSend.maxRating = <?php echo $maxRating; ?>;
            }
            <?php } ?>

            $.ajax({
                url: "tabs/" + tabName + ".php?id=<?php echo $profileId; ?>",
                method: "GET",
                data: dataToSend,
                success: function (data) {
                    tabContent[tabName] = data;
                    if (!onlyCache) {
                        $("#current-tab").html(data);
                    }
                },
                error: function () {
                    console.log(this.error);
                }
            });
        }
    }

    const initialTab = $(".tabbed-container-nav button.active").data("tab");
    showTab(initialTab, true);
</script>
