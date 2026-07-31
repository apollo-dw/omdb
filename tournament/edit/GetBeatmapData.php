<?php
    include '../../base.php';
    header('Content-Type: application/json');

    $id = $_GET["id"];

    if (is_null($id)) {
        die(json_encode(array("error" => "missing data")));
    }

    $stmt = $conn->prepare("SELECT s.SetID, s.Artist, s.Title, b.DifficultyName
                FROM `beatmapsets` s
                INNER JOIN `beatmaps` b ON s.SetID = b.SetID
                WHERE b.BeatmapID = ?;");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $map = $result->fetch_assoc();
        $response = array(
            "BeatmapID" => (int)$id,
            "SetID" => (int)$map["SetID"],
            "Artist" => $map["Artist"],
            "Title" => $map["Title"],
            "DifficultyName" => $map["DifficultyName"],
            "itemTitle" => "{$map["Artist"]} - {$map["Title"]} [{$map["DifficultyName"]}]",
            "imageUrl" => "https://b.ppy.sh/thumb/" . $map["SetID"] . "l.jpg",
            "inDb" => true
        );
    } else {
        $response = array(
            "BeatmapID" => (int)$id,
            "SetID" => null,
            "Artist" => "",
            "Title" => $id . " (Map not in OMDB)",
            "DifficultyName" => "",
            "itemTitle" => $id . " (Map not in OMDB)",
            "imageUrl" => "/assets/img/missing-map-thumbnail.png",
            "inDb" => false
        );
    }

    echo json_encode($response);
