<?php
    $PageTitle = "Labs";

    require "../base.php";
    require '../header.php';

    $labsPages = [
        [
            'title' => 'Recommendations',
            'url' => '/labs/recommendations.php',
            'description' => 'Play with the weights and settings of the recommendation system!',
        ],
        [
            'title' => 'Usermap',
            'url' => '/labs/usermap.php',
            'description' => 'Shows an embedding map of users on OMDB',
        ],
        [
            'title' => 'Stats',
            'url' => '/labs/stats.php',
            'description' => 'See some random OMDB stats!',
        ],
    ];
?>

<h1>Labs</h1>
<span class="subText">Try Out Some Epic New Stuffz Here!!!!1</span>

<hr>

<div class="flex-row-container" style="gap:0.75em;">
    <?php foreach ($labsPages as $labPage) { ?>
        <div class="flex-child" style="padding:1em;box-sizing:border-box;background-color: var(--main-theme-color);">
            <h3 style="margin-top:0;margin-bottom:0.25em;"><a href="<?php echo safe_htmlspecialchars($labPage['url'], ENT_QUOTES); ?>"><?php echo safe_htmlspecialchars($labPage['title'], ENT_QUOTES); ?></a></h3>
            <div class="subText"><?php echo safe_htmlspecialchars($labPage['description'], ENT_QUOTES); ?></div>
        </div>
    <?php } ?>
</div>

<?php
require '../footer.php';
?>