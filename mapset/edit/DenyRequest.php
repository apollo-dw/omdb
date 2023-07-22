<?php
require '../../base.php';

if ($userName != "moonpoint" || $userId != 12704035 || !$loggedIn) {
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
    $stmt = $conn->prepare("UPDATE beatmap_edit_requests SET Status = 'Denied', EditorID = ? WHERE `EditID` = ?;");
    $stmt->bind_param('ii', $userId, $request['EditID']);
    $stmt->execute();

    header('Location: ../edit/?id=' . $setID);
}
