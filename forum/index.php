<?php
    $PageTitle = "Forums";
    require "../base.php";
    require '../header.php';

    $stmt = $conn->query("SELECT * FROM forum_topics;");
?>

<style>
    h1, h2 {
        margin: 0;
    }

    .forum-topic {
        width: 100%;
        padding: 0.5em;
        box-sizing: border-box;
    }
</style>

<h1>Forums</h1>
<hr>

<?php
    while ($row = $stmt->fetch_assoc()) {
        ?>
        <div class="alternating-bg forum-topic">
            <h2><?php echo $row["Name"]; ?></h2>
            <span class="subText"><?php echo $row["Description"]; ?></span>
        </div>
        <?php
    }
?>

<?php
    require '../footer.php';
?>
