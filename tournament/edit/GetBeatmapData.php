<?php
    include '../../base.php';
    header('Content-Type: application/json');

    $id = $_GET["id"];

    if (is_null($id)) {
        die(json_encode(array("error" => "missing data")));
    }

    $response = array();
    $stmt = $conn->prepare("SELECT s.SetID, s.Artist, s.Title, b.DifficultyName
                FROM `beatmapsets` s
                INNER JOIN `beatmaps` b ON s.SetID = b.SetID
                WHERE b.BeatmapID = ?;");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows != 1) {
        die(json_encode(array("error" => "beatmap not found")));
    }

    $map = $result->fetch_assoc();
    $title = "{$map["Artist"]} - {$map["Title"]} [{$map["DifficultyName"]}]";
    $response = array(
        "imageUrl" => "https://b.ppy.sh/thumb/" . $map["SetID"] . "l.jpg",
        "itemTitle" => $title,
    );

    echo json_encode($response);
