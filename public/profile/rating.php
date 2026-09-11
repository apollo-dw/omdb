<?php
    require_once __DIR__ . '/../../app/base.php';

    $stmt = $conn->prepare("
        SELECT
            (SELECT COUNT(*)
            FROM rating_tags
            WHERE UserID = ?) AS tagCount,

            (SELECT COUNT(*)
            FROM beatmapset_nominators
            WHERE NominatorID = ?) AS nominationCount,

            (SELECT COUNT(*)
            FROM lists
            WHERE UserID = ? AND Private = 0) AS listCount,

            (SELECT COUNT(*)
            FROM list_hearts
            WHERE UserID = ?) AS heartedListCount,

            (SELECT COUNT(DISTINCT SetID)
            FROM beatmapset_credits
            WHERE UserID = ?) AS creditCount
    ");

    $stmt->bind_param(
        "iiiii",
        $profileId,
        $profileId,
        $profileId,
        $profileId,
        $profileId
    );

    $stmt->execute();

    $counts = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    $desc = trim($profile["CustomDescription"] ?? "");

    $tagCount = $counts["tagCount"];
    $nominationCount = $counts["nominationCount"];
    $listCount = $counts["listCount"];
    $heartedListCount = $counts["heartedListCount"];
    $creditCount = $counts["creditCount"];

    $tabs = [];

    if (strlen($desc) > 0 || $userId == $profileId || !$isValidUser || $shouldHideProfile) {
        $tabs[] = ["about-me", "About Me"];
    }

    if (!$shouldHideProfile && $isValidUser) {
        $tabs[] = ["latest", "Latest"];
        $tabs[] = ["ratings", "Ratings"];

        if ($tagCount > 0) {
            $tabs[] = ["tags", "Tags (" . $tagCount . ")"];
        }

        $tabs[] = ["stats", "Stats"];

        if (($listCount + $heartedListCount) > 0) {
            $tabs[] = ["lists", "Lists (" . $listCount . ")"];
        }
    }

    if ($nominationCount > 0) {
        $tabs[] = ["nominations", "Nominations (" . $nominationCount . ")"];
    }

    if ($creditCount > 0) {
        $tabs[] = ["credits", "Credits (" . $creditCount . ")"];
    }

    $initialTab = $tabs[0][0] ?? null;
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
    <?php foreach ($tabs as $tab) { ?>
        <button data-tab="<?php echo $tab[0]; ?>" class="<?php echo $tab[0] === $initialTab ? "active" : ""; ?>"><?php echo $tab[1]; ?></button>
    <?php } ?>
</div>

<div id="current-tab">
    <?php if ($initialTab) { ?>
        <?php include 'tabs/' . $initialTab . '.php'; ?>
    <?php } ?>
</div>

<script>
    const tabContent = {};

    <?php if ($initialTab) { ?>
    tabContent["<?php echo $initialTab; ?>"] = $("#current-tab").html();
    <?php } ?>

    $(".tabbed-container-nav button").on("click", function () {
        const tabName = $(this).data("tab");
        $(".tabbed-container-nav button").removeClass("active");
        $(this).addClass("active");
        showTab(tabName);
    });

    function showTab(tabName) {
        if (tabContent[tabName]) {
            $("#current-tab").html(tabContent[tabName]);
        } else {
            let dataToSend = { tab: tabName };

            <?php if ($isValidUser) { ?>
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
                    $("#current-tab").html(data);
                },
                error: function () {
                    console.log(this.error);
                }
            });
        }
    }
</script>
