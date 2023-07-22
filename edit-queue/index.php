<?php
    $PageTitle = "Edit Queue";

    include '../header.php';
    $stmt = $conn->prepare("SELECT e.*, b.SetID, b.Title, b.DifficultyName FROM beatmap_edit_requests e JOIN beatmaps b on e.BeatmapID = b.BeatmapID WHERE e.Status = 'Pending' ORDER BY e.`Timestamp`;");
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

    <table>
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
                $name = GetUserNameFromId($row["UserID"], $conn);
                $mapsetLink = "../mapset/?mapset_id={$row["SetID"]}";
                echo "<tr class='alternating-bg' onclick=\"window.open('{$mapsetLink}', '_blank');\" style=\"cursor: pointer;\">";
                echo "<td>{$name}</td>";
                echo "<td>{$row["Title"]}</td>";
                echo "<td>{$row["DifficultyName"]}</td>";
                echo "<td>{$row["Timestamp"]}</td>";
                echo "</tr>";
            }
        ?>
        </tbody>
    </table>

<?php
include '../footer.php';
?>
