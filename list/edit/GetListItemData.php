<?php
    include '../../base.php';
    header('Content-Type: application/json');

    $type = $_GET["type"];
    $id = $_GET["id"];

    if (is_null($type) || is_null($id))
        die(json_encode(array("error" => "missing data")));

    if (!is_numeric($id))
        die(json_encode(array("error" => "id not valid")));

    $response = array();

    switch ($type){
        case "person":
            $username = GetUserNameFromId($id, $conn);
            if ($username == "")
                die(json_encode(array("error" => "user not found")));

            $response = array(
                "imageUrl" => "https://s.ppy.sh/a/" . $id,
                "itemTitle" => $username,
            );

            break;
        case "beatmap":
            $stmt = $conn->prepare("SELECT s.SetID, s.Artist, s.Title, b.DifficultyName
                        FROM `beatmapsets` s
                        INNER JOIN `beatmaps` b ON s.SetID = b.SetID
                        WHERE b.BeatmapID = ?;");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows != 1)
                die(json_encode(array("error" => "beatmap not found")));

            $map = $result->fetch_assoc();
            $title = "{$map["Artist"]} - {$map["Title"]} [{$map["DifficultyName"]}]";
            $response = array(
                "imageUrl" => "https://b.ppy.sh/thumb/" . $map["SetID"] . "l.jpg",
                "itemTitle" => $title,
            );

            break;
        case "beatmapset":
            $stmt = $conn->prepare("SELECT Artist, Title FROM `beatmapsets` WHERE `SetID` = ? LIMIT 1;");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows != 1)
                die(json_encode(array("error" => "set not found")));

            $set = $result->fetch_assoc();
            $title = "{$set["Artist"]} - {$set["Title"]}";
            $response = array(
                "imageUrl" => "https://b.ppy.sh/thumb/" . $id . "l.jpg",
                "itemTitle" => $title,
            );

            break;
    }

    echo json_encode($response);