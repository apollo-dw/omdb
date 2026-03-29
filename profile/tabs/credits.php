<?php
    if (file_exists("../../base.php"))
        include "../../base.php";

    $profileId = $_GET["id"];
?>

<div id="tabbed-credits" class="tab" style="padding-top:0.5em;">
    <?php
    // CREDITS QUERY
    $stmt = $conn->prepare("
      SELECT 
        s.*,
        GROUP_CONCAT(br.Name ORDER BY br.Name SEPARATOR ', ') AS userCredits
      FROM beatmapsets s
      JOIN beatmapset_credits bc ON s.SetID = bc.SetID
      JOIN beatmap_roles br ON bc.RoleID = br.RoleID
      WHERE bc.UserID = ?
      GROUP BY s.SetID
      ORDER BY s.DateRanked DESC;
    ");
    $stmt->bind_param("i", $profileId);
    $stmt->execute();
    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()){
        $artist = htmlspecialchars($row["Artist"]);
        $title = htmlspecialchars($row["Title"]);
        $credits = htmlspecialchars($row["userCredits"]);

        ?>
        <div style='padding-left:0.25em;height:5em;display:flex;align-items: center;' class='alternating-bg'>
            <div>
                <a href="/mapset/<?php echo $row['SetID']; ?>">
                    <img src="https://b.ppy.sh/thumb/<?php echo $row['SetID']; ?>l.jpg" class="diffThumb" style="height:4em;width:4em;margin-right:0.5em;" onerror="this.onerror=null; this.src='../charts/INF.png';" />
                </a>
            </div>
            <div>
                <a href="/mapset/<?php echo $row['SetID']; ?>"><?php echo "{$artist} - {$title}"; ?></a> <br>
                  <b><span class="subText"><?php echo $credits; ?></span></b> 
            </div>
        </div>
        <?php
    }
    ?>
</div>