<?php
require '../../base.php';

if (!$loggedIn) {
    http_response_code(401);
    exit();
}

$mappers = $_POST["mapperListData"] ?? "{}";
$credits = $_POST["creditsListData"] ?? "{}";
$meta = $_POST["meta"];
$beatmapID = $_POST["BeatmapID"] ?? null;
$setID = $_POST["SetID"] ?? null;
$isEditingSet = !is_null($setID);

$array = [
    "Meta" => $meta,
    "Mappers" => json_decode($mappers),
    "Credits" => json_decode($credits)
];

$json = json_encode($array);

if ($isEditingSet) {
    $stmt = $conn->prepare("SELECT Count(*) FROM beatmaps WHERE SetID = ?;");
    $stmt->bind_param('i', $setID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        http_response_code(404);
        exit();
    }

    $stmt = $conn->prepare("SELECT Status FROM `beatmap_edit_requests` WHERE SetID = ? AND `Status` = 'Pending';");
    $stmt->bind_param('i', $setID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(404);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO `beatmap_edit_requests` (SetID, UserID, EditData) VALUES (?, ?, ?);");
    $stmt->bind_param('iis', $setID, $userId, $json);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("SELECT SetID FROM beatmaps WHERE BeatmapID = ?;");
    $stmt->bind_param('i', $beatmapID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        http_response_code(404);
        exit();
    }

    $setID = $result->fetch_assoc()["SetID"];

    $stmt = $conn->prepare("SELECT Status FROM `beatmap_edit_requests` WHERE BeatmapID = ? AND `Status` = 'Pending';");
    $stmt->bind_param('i', $beatmapID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        http_response_code(404);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO `beatmap_edit_requests` (BeatmapID, UserID, EditData) VALUES (?, ?, ?);");
    $stmt->bind_param('iis', $beatmapID, $userId, $json);
    $stmt->execute();
}

header('Location: ../edit/?id=' . $setID);
