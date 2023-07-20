<?php
    require '../../base.php';
    if(!$loggedIn){
        die("NO");
    }

    $mappers = $_POST["mapperListData"];
    $meta = $_POST["meta"];
    $beatmapID = $_POST["BeatmapID"];

    $array = [
        "Meta" => $meta,
        "Mappers" => json_decode($mappers),
    ];

    $json = json_encode($array);

    $stmt = $conn->prepare("SELECT SetID FROM beatmaps WHERE BeatmapID = ?;");
    $stmt->bind_param('i', $beatmapID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0)
        die("NO");

    $setID = $result->fetch_assoc()["SetID"];

    $stmt = $conn->prepare("SELECT Status FROM `beatmap_edit_requests` WHERE BeatmapID = ? AND `Status` = 'Pending';");
    $stmt->bind_param('i', $beatmapID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0)
        die("NO");

    $stmt = $conn->prepare("INSERT INTO `beatmap_edit_requests` (BeatmapID, UserID, EditData) VALUES (?, ?, ?);");
    $stmt->bind_param('iis', $beatmapID, $userId, $json);
    $stmt->execute();

    header('Location: ../edit/?id=' . $setID);