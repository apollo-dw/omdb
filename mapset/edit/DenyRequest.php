<?php
require '../../base.php';

$beatmapID = $_GET["BeatmapID"] ?? null;
$setID = $_GET["SetID"] ?? null;
$isEditingSet = !is_null($setID);

if ($isEditingSet) {
    $stmt = $conn->prepare("SELECT Count(*) FROM beatmaps WHERE SetID = ?;");
    $stmt->bind_param('i', $setID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0)
        die("NO");

    $stmt = $conn->prepare("SELECT * FROM beatmap_edit_requests WHERE `SetID` = ? AND Status = 'Pending';");
    $stmt->bind_param('i', $setID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0)
        die("NO SET");

} else {
    $stmt = $conn->prepare("SELECT SetID FROM beatmaps WHERE BeatmapID = ?;");
    $stmt->bind_param('i', $beatmapID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0)
        die("NO");

    $setID = $result->fetch_assoc()["SetID"];

    $stmt = $conn->prepare("SELECT * FROM beatmap_edit_requests WHERE `BeatmapID` = ? AND Status = 'Pending';");
    $stmt->bind_param('i', $beatmapID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0)
        die("NO MAP");
}

$request = $result->fetch_assoc();
if (!$loggedIn || !(isIdEditRequestAdmin($userId) ||
					$userId == $request['UserID'])) {
    header('HTTP/1.0 403 Forbidden');
    http_response_code(403);
    die("Forbidden");
}

if ($request) {
    $stmt = $conn->prepare("UPDATE beatmap_edit_requests SET Status = 'Denied', EditorID = ? WHERE `EditID` = ?;");
    $stmt->bind_param('ii', $userId, $request['EditID']);
    $stmt->execute();

    header('Location: ../edit/?id=' . $setID);
}
