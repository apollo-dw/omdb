<?php
require "../../base.php";

if (!$loggedIn) {
    http_response_code(401);
    exit();
}

$editorID = $userId;

$tournamentID = isset($_POST['TournamentID']) && $_POST['TournamentID'] !== '' ? (int)$_POST['TournamentID'] : null;
$seriesID = isset($_POST['SeriesID']) && $_POST['SeriesID'] !== '' ? (int)$_POST['SeriesID'] : null;
$editDataRaw = $_POST['EditData'] ?? '';

if (empty($editDataRaw)) {
    http_response_code(400);
    die("Missing edit payload data.");
}

$parsedData = json_decode($editDataRaw, true);
if (json_last_error() !== JSON_ERROR_NONE || empty($parsedData['Tournament']['Name'])) {
    http_response_code(400);
    die("Invalid JSON data format.");
}

if (!isset($parsedData['Tournament']['SeriesID'])) {
    $parsedData['Tournament']['SeriesID'] = $seriesID;
}

$editData = json_encode($parsedData, JSON_UNESCAPED_UNICODE);

$existingEditID = null;
$existingEditorID = null;

if ($tournamentID !== null) {
    $stmt = $conn->prepare("
        SELECT EditID, EditorID
        FROM tournament_edit_requests
        WHERE TournamentID = ? AND Status = 'Pending'
        LIMIT 1
    ");
    $stmt->bind_param("i", $tournamentID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $existingEditID = (int)$row['EditID'];
        $existingEditorID = (int)$row['EditorID'];
    }
    $stmt->close();
}

$stmt = $conn->prepare("
    SELECT SeriesID
    FROM tournament_series
    WHERE SeriesID = ? LIMIT 1
");
$stmt->bind_param("i", $seriesID);
$stmt->execute();
$series = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (is_null($series)) {
    http_response_code(400);
    exit();
}

$targetEditID = null;

if ($existingEditID !== null) {
    if ($editorID !== $existingEditorID && !($isAdmin ?? false)) {
        http_response_code(403);
        exit();
    }

    $stmt = $conn->prepare("
        UPDATE tournament_edit_requests
        SET EditData = ?,
            EditorID = ?,
            UpdatedAt = CURRENT_TIMESTAMP
        WHERE EditID = ?
    ");
    $stmt->bind_param("sii", $editData, $editorID, $existingEditID);
    $stmt->execute();
    $stmt->close();

    $targetEditID = $existingEditID;
} else {
    $stmt = $conn->prepare("
        INSERT INTO tournament_edit_requests
            (TournamentID, EditData, Status, EditorID)
        VALUES
            (?, ?, 'Pending', ?)
    ");

    $stmt->bind_param("isi", $tournamentID, $editData, $editorID);
    $stmt->execute();

    $targetEditID = $conn->insert_id;
    $stmt->close();
}

header('Location: ./?id=' . $targetEditID);
exit();
