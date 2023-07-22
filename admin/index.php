<?php
    include 'header.php';

    $stmt = $conn->prepare("SELECT e.*, b.SetID, b.Title, b.DifficultyName FROM beatmap_edit_requests e JOIN beatmaps b on e.BeatmapID = b.BeatmapID ORDER BY e.`Timestamp` LIMIT 50;");
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<b>Open edit requests:</b><br>";
    while($row = $result->fetch_assoc()) {
        $name = GetUserNameFromId($row["UserID"], $conn);
        $status = "Pending";
        if ($row["Status"] != "Pending"){
            $editorName = GetUserNameFromId($row["EditorID"], $conn);
            $status = "{$row["Status"]} by {$editorName}";
        }
        echo "<a href='../mapset/?mapset_id={$row["SetID"]}'>{$name} on {$row["Title"]} [{$row["DifficultyName"]}]</a> ({$row["Timestamp"]}) - {$status}<br>";
    }

