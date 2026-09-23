<?php
    require_once __DIR__ . '/../../app/base.php';
    $artist_url_param = postOrGet('artist', '');

    if (empty($artist_url_param)) {
        http_response_code(400);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM beatmapsets WHERE Artist = ?");
    $stmt->bind_param("s", $artist_url_param);
    $stmt->execute();
    $result = $stmt->get_result();
    $beatmapCount = $result->num_rows;
    $stmt->close();
    if ($beatmapCount == 0) {
        http_response_code(404);
        exit;
    }

    $PageTitle = safe_htmlspecialchars($artist_url_param, ENT_QUOTES);
    require '../header.php';
?>

<h1><?php echo safe_htmlspecialchars($artist_url_param, ENT_QUOTES); ?></h1>
<span class="subText"><?php echo $beatmapCount; ?> mapsets</span>
<hr>

<h2 style="margin-bottom: 0px;">Highest ranked <?php echo safe_htmlspecialchars($artist_url_param, ENT_QUOTES); ?> maps</h2><br>
<div class="flex-container map-card-strip alternating-bg" style="width:100%;padding:0;justify-content: flex-start;">
    <?php
    $stmt = $conn->prepare("
        SELECT b.SetID, b.DifficultyName, s.Title
        FROM beatmaps b
        JOIN beatmapsets s ON b.SetID = s.SetID
        WHERE s.Artist = ?
          AND b.Mode = ?
          AND b.Rating IS NOT NULL
          AND b.RatingCount >= 5
        ORDER BY b.Rating DESC
        LIMIT 10;
    ");
    $stmt->bind_param("si", $artist_url_param, $mode);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $difficultyName = mb_strimwidth($row['DifficultyName'], 0, 35, "...");
        ?>
        <div class="flex-child map-card" style="max-width: 11%;">
            <a href="/mapset/<?php echo $row["SetID"]; ?>">
                <img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb" style="aspect-ratio: 1 / 1;width:90%;height:auto;" onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';">
            </a><br>
            <span class="subText">
                <a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo safe_htmlspecialchars("{$row["Title"]} [$difficultyName]", ENT_QUOTES); ?></a><br>
            </span>
        </div>
        <?php
    }

    $stmt->close();
    ?>
</div>

<?php
    include '../footer.php';
?>