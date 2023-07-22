<?php
    require '../../base.php';

    if (!($userName === "moonpoint" || $userId === 12704035) || !$loggedIn) {
        header('HTTP/1.0 403 Forbidden');
        http_response_code(403);
        die("Forbidden");
    }

    $beatmapID = $_GET["BeatmapID"];

    $stmt = $conn->prepare("SELECT * FROM beatmap_edit_requests WHERE `BeatmapID` = ? AND Status = 'Pending';");
    $stmt->bind_param('i', $beatmapID);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();

    $stmt = $conn->prepare("SELECT SetID FROM beatmaps WHERE BeatmapID = ?;");
    $stmt->bind_param('i', $beatmapID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0)
        die("NO");

    $setID = $result->fetch_assoc()["SetID"];

    if ($request) {
        $editDataArray = json_decode($request['EditData'], true);
        $newMappers = $editDataArray["Mappers"];

        $stmt = $conn->prepare("SELECT * FROM beatmap_creators WHERE `BeatmapID` = ?;");
        $stmt->bind_param('i', $beatmapID);
        $stmt->execute();
        $result = $stmt->get_result();

        $currentMappers = array();
        while ($row = $result->fetch_assoc())
            $currentMappers[] = $row['CreatorID'];

        $stmt = $conn->prepare("DELETE FROM beatmap_creators WHERE `BeatmapID` = ?;");
        $stmt->bind_param('i', $beatmapID);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO beatmap_creators (`BeatmapID`, `CreatorID`) VALUES (?, ?);");
        $stmt->bind_param('ii', $beatmapID, $creatorID);
        foreach ($newMappers as $creatorID) {
            $stmt->execute();
        }

        $stmt = $conn->prepare("UPDATE beatmap_edit_requests SET Status = 'Approved', EditorID = ? WHERE `EditID` = ?;");
        $stmt->bind_param('ii', $userId, $request['EditID']);
        $stmt->execute();

        header('Location: ../edit/?id=' . $setID);
    }
