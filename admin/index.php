<?php
    include 'header.php';

	//$stmt = $conn->prepare("SELECT e.*, b.SetID, b.Title, b.DifficultyName FROM beatmap_edit_requests e JOIN beatmaps b on e.BeatmapID = b.BeatmapID ORDER BY e.`Timestamp` DESC LIMIT 50;");
    $stmt = $conn->prepare("SELECT users.Username, descriptors.Name, beatmaps.SetID, s.Title, beatmaps.SetID, beatmaps.DifficultyName, Vote FROM descriptor_votes  LEFT JOIN users ON users.UserID = descriptor_votes.UserID LEFT JOIN descriptors ON
descriptors.DescriptorID = descriptor_votes.DescriptorID LEFT JOIN beatmaps ON beatmaps.BeatmapID = descriptor_votes.BeatmapID LEFT JOIN beatmapsets s on beatmaps.SetID = s.SetID ORDER BY VoteID DESC LIMIT 150;");
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<b>Open edit requests:</b><br>";
    while($row = $result->fetch_assoc()) {
        //$name = GetUserNameFromId($row["UserID"], $conn);
        //$status = "Pending";
        //if ($row["Status"] != "Pending"){
        //    $editorName = GetUserNameFromId($row["EditorID"], $conn);
        //    $status = "{$row["Status"]} by {$editorName}";
        //}
        echo "<a href='../mapset/?mapset_id={$row["SetID"]}'>{$row["Username"]} on {$row["Title"]} [{$row["DifficultyName"]}]</a> {$row["Name"]} {$row["Vote"]}<br>";
    }

