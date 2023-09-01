<?php
$PageTitle = "Edit Queue";

include '../header.php';
$stmt = $conn->prepare("SELECT e.*, b.SetID as SetID, e.SetID as EditSetID, b.Title, b.DifficultyName FROM beatmap_edit_requests e LEFT JOIN beatmaps b on e.BeatmapID = b.BeatmapID WHERE e.Status = 'Pending' ORDER BY e.`Timestamp`;");
$stmt->execute();
$result = $stmt->get_result();

?>

<style>
    table, tr, td {
        border-spacing: 0;
        padding: 0.5em;
    }

    tr:nth-of-type(odd):hover{
        background-color: #406565;
    }

    tr:nth-of-type(even):hover{
        background-color: #537e7e;
    }
</style>

<h1>Edit queue</h1>

<table style="width:100%;">
    <thead>
    <tr>
        <th>Name</th>
        <th>Title</th>
        <th>Difficulty</th>
        <th>Date</th>
    </tr>
    </thead>
    <tbody>
    <?php
    while ($row = $result->fetch_assoc()) {
        $isEditingSet = !is_null($row["EditSetID"]);
        $setId = $row['SetID'];
        $title = $row["Title"];

        if ($isEditingSet){
            $setId = $row['EditSetID'];
            // Look, I know this is not good. The answer to this is just keep queue sizes small :)
            $title = $conn->query("SELECT Title FROM beatmaps WHERE SetID = {$setId} LIMIT 1;")->fetch_assoc()["Title"];
        }

        $name = GetUserNameFromId($row["UserID"], $conn);
        $mapsetLink = "../mapset/edit/?id={$setId}";
        echo "<tr class='alternating-bg' onclick=\"window.open('{$mapsetLink}', '_blank');\" style=\"cursor: pointer;\">";
        echo "<td>{$name}</td>";
        echo "<td>{$title}</td>";
        if ($isEditingSet) {
            echo "<td>Mapset (general edit)</td>";
        } else {
            echo "<td>{$row["DifficultyName"]}</td>";
        }
        echo "<td>{$row["Timestamp"]}</td>";
        echo "</tr>";
    }
    ?>
    </tbody>
</table>

<?php
include '../footer.php';
?>
