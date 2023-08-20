<?php
    require "../base.php";
    $descriptor_id = $_GET['id'] ?? -1;

    $stmt = $conn->prepare("SELECT * FROM `descriptors` WHERE `DescriptorID` = ?;");
    $stmt->bind_param("i", $descriptor_id);
    $stmt->execute();
    $descriptor = $stmt->get_result()->fetch_assoc();

    $PageTitle = "Descriptor - " . $descriptor["Name"];
    require '../header.php';

    if (is_null($descriptor))
        die("Descriptor not found.");

    function getParentTree($descriptor, $conn) {
        if ($descriptor['ParentID'] === null) {
            return $descriptor['Name'];
        } else {
            $parentStmt = $conn->prepare("SELECT `Name`, `ParentID` FROM `descriptors` WHERE `DescriptorID` = ?;");
            $parentStmt->bind_param("i", $descriptor['ParentID']);
            $parentStmt->execute();
            $parentDescriptor = $parentStmt->get_result()->fetch_assoc();

            $parentTree = getParentTree($parentDescriptor, $conn);
            return $parentTree . ' >> ' . $descriptor['Name'];
        }
    }

    $parentTree = getParentTree($descriptor, $conn);

    echo "<h1>Descriptor - {$descriptor["Name"]}</h1>";
    echo $descriptor["ShortDescription"] . "<br>";
    echo "<span class='subText'>$parentTree</span>";
?>
<br><br>
<a href="../descriptors/">View all descriptors</a>
<br><br>

<h2 style="margin-bottom: 0px;">Highest ranked <?php echo $descriptor["Name"]; ?> maps</h2><br>
<div class="flex-container" style="width:100%;background-color:DarkSlateGrey;padding:0px;">
    <br>
    <?php
    $stmt = $conn->prepare("WITH RECURSIVE DescendantDescriptors AS (
                                    SELECT DescriptorID, ParentID
                                    FROM descriptors
                                    WHERE DescriptorID = ?  -- Specify the starting DescriptorID
                                    UNION ALL
                                    SELECT d.DescriptorID, d.ParentID
                                    FROM descriptors d
                                    JOIN DescendantDescriptors dd ON d.ParentID = dd.DescriptorID
                                )
                                SELECT b.*
                                FROM beatmaps b
                                JOIN descriptor_votes dv ON b.BeatmapID = dv.BeatmapID
                                JOIN DescendantDescriptors dd ON dv.DescriptorID = dd.DescriptorID
                                WHERE b.Mode = ? AND b.ChartRank IS NOT NULL
                                GROUP BY b.BeatmapID, b.ChartRank
                                HAVING SUM(CASE WHEN dv.Vote = 1 THEN 1 ELSE 0 END) > SUM(CASE WHEN dv.Vote = 0 THEN 1 ELSE 0 END)
                                ORDER BY b.ChartRank
                                LIMIT 9;");
    $stmt->bind_param("ii", $descriptor_id, $mode);
    $stmt->execute();
    $result = $stmt->get_result();

    while($row = $result->fetch_assoc()) {
        $difficultyName = mb_strimwidth(htmlspecialchars($row['DifficultyName']), 0, 35, "...");
        ?>
        <div class="flex-child" style="text-align:center;width:11%;padding:0.5em;display: inline-block;">
            <a href="/mapset/<?php echo $row["SetID"]; ?>"><img src="https://b.ppy.sh/thumb/<?php echo $row["SetID"]; ?>l.jpg" class="diffThumb" style="aspect-ratio: 1 / 1;width:90%;height:auto;" onerror="this.onerror=null; this.src='/charts/INF.png';"></a><br>
            <span class="subtext">
			    <a href="/mapset/<?php echo $row["SetID"]; ?>"><?php echo "{$row["Title"]} [$difficultyName]"; ?></a><br>
		    </span>
        </div>
        <?php
    }

    $stmt->close();
    ?>
</div>

<?php
    require '../footer.php';
?>
