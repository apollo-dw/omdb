<?php
    require_once __DIR__ . '/../../app/base.php';
    $descriptor_id = $descriptor_id ?? GetIntParam('id', -1, "Y U POST CRINGE");
    $page = (int)(postOrGet('p', 1));
    $order = postOrGet('order', 'asc');
    $dateOrder = (strtolower($order) === 'asc') ? 'ASC' : 'DESC';

    $lim = 10;
    $offset = ($page > 1) ? ($page - 1) * $lim : 0;
    $counter = $offset;
    $pageString = ($page > 1) ? "LIMIT {$offset}, {$lim}" : "LIMIT {$lim}";

    $stmt = $conn->prepare("
    WITH RECURSIVE DescendantDescriptors AS (
        SELECT DescriptorID
        FROM descriptors
        WHERE DescriptorID = ?

        UNION ALL

        SELECT d.DescriptorID
        FROM descriptors d
        JOIN DescendantDescriptors dd
            ON d.ParentID = dd.DescriptorID
    )
    SELECT b.*, s.*
    FROM beatmaps b
    JOIN beatmapsets s
        ON b.SetID = s.SetID
    WHERE b.Mode = ?
      AND EXISTS (
          SELECT 1
          FROM beatmap_descriptors bd
          JOIN DescendantDescriptors dd
              ON bd.DescriptorID = dd.DescriptorID
          WHERE bd.BeatmapID = b.BeatmapID
      )
    ORDER BY s.DateRanked $dateOrder, b.SR DESC
    $pageString;");

  $stmt->bind_param("ii", $descriptor_id, $mode);
  $stmt->execute();
  $result = $stmt->get_result();

  $stmt->close();

  $countStmt = $conn->prepare("
  WITH RECURSIVE DescendantDescriptors AS (
      SELECT DescriptorID
      FROM descriptors
      WHERE DescriptorID = ?

      UNION ALL

      SELECT d.DescriptorID
      FROM descriptors d
      JOIN DescendantDescriptors dd
          ON d.ParentID = dd.DescriptorID
  )
  SELECT COUNT(DISTINCT b.BeatmapID) AS total
  FROM beatmaps b
  JOIN beatmapsets s
      ON b.SetID = s.SetID
  JOIN beatmap_descriptors bd
      ON b.BeatmapID = bd.BeatmapID
  JOIN DescendantDescriptors dd
      ON bd.DescriptorID = dd.DescriptorID
  WHERE b.Mode = ?;");

  $countStmt->bind_param("ii", $descriptor_id, $mode);
  $countStmt->execute();
  $totalCount = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
  $countStmt->close();

  $amountOfMapsPages = max(1, (int) ceil($totalCount / $lim));
  ?>

  <div style="height: 2em;">
      <a href="javascript:toggleSortOrder()" id="sort-toggle-btn" style="text-decoration: none; font-weight: bold;">
          Date <span id="sort-arrow"><?php echo ($dateOrder === 'ASC') ? '▲' : '▼'; ?></span>
      </a>
  </div>

  <?php
    
  foreach ($result as $row) {
      ?>
      <div class="flex-container ratingContainer alternating-bg">
          <div class="flex-child" style="margin-left:0.5em;">
      <a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb"/ onerror="this.onerror=null; this.src='/assets/img/missing-map-thumbnail.png';"></a>
    </div>
          <div class="flex-child">
              <a style="display:flex;" href="/mapset/<?php echo $row["SetID"]; ?>">
                  <?php echo safe_htmlspecialchars($row["Artist"], ENT_QUOTES) . " - " . safe_htmlspecialchars($row["Title"], ENT_QUOTES) . " [" . safe_htmlspecialchars($row["DifficultyName"], ENT_QUOTES) . "]"; ?> <br>
              </a>
              <span class="subText">by <?php RenderBeatmapCreators($row['BeatmapID'], $conn); ?> <br> <?php echo date('d-m-Y', strtotime($row["DateRanked"])); ?></span>
          </div>
      </div>
      <?php
  }
?>

<div style="text-align:center;">
    <div class="pagination">
        <b><span><?php if ($page > 1) {
            echo "<a href='javascript:lowerMapsPage()'>&laquo; </a>";
        } ?></span></b>
        <span id="page"><?php echo $page; ?></span>
        <b><span><?php if ($page < $amountOfMapsPages) {
            echo "<a href='javascript:increaseMapsPage()'>&raquo; </a>";
        } ?></span></b>
        <br>
    </div>
</div>

<script>
    var mapsPage = <?php echo $page; ?>;
    var sortOrder = "<?php echo strtolower($dateOrder); ?>";

    function toggleSortOrder() {
        sortOrder = (sortOrder === 'asc') ? 'desc' : 'asc';
        document.getElementById('sort-arrow').textContent = (sortOrder === 'asc') ? '▲' : '▼';
        
        mapsPage = 1;
        updateMaps();
    }

    function lowerMapsPage() {
        changeMapsPage(mapsPage - 1)
    }

    function increaseMapsPage() {
        changeMapsPage(mapsPage + 1)
    }

    function changeMapsPage(newPage) {
        mapsPage = Math.min(Math.max(newPage, 1), <?php echo $amountOfMapsPages; ?>);
        updateMaps();
    }

    function updateMaps() {
        var xmlhttp = new XMLHttpRequest();

        xmlhttp.onreadystatechange=function() {
            if (this.readyState==4 && this.status==200) {
                document.getElementById("descriptor-map-list").innerHTML=this.responseText;
            }
        }

        xmlhttp.open("GET", "descriptor-map-list.php?p=" + mapsPage + "&id=" + <?php echo $descriptor_id; ?> + "&order=" + sortOrder, true);
        xmlhttp.send();
    }
</script>