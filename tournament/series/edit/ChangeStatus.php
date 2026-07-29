<?php
require '../../../base.php';

if (!$loggedIn || !isIdEditRequestAdmin($userId)) {
    header('HTTP/1.0 403 Forbidden');
    http_response_code(403);
    die("Forbidden");
}

$editID = $_POST['EditID'] ?? $_GET['EditID'] ?? null;
$newStatus = $_POST['Status'] ?? $_GET['Status'] ?? null;

if (!$editID || !in_array($newStatus, ['Pending', 'Approved', 'Denied'], true)) {
    http_response_code(400);
    exit();
}

$stmt = $conn->prepare("
    SELECT *
    FROM tournament_series_edit_requests
    WHERE EditID = ?
");
$stmt->bind_param('i', $editID);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();

if (!$request) {
    http_response_code(404);
    exit();
}

if ($newStatus === 'Approved') {
    $editData = json_decode($request['EditData'], true);
    $seriesName = trim($editData['SeriesName'] ?? '');
    $seriesAcronym = trim($editData['SeriesAcronym'] ?? '');

    if (empty($seriesName) || empty($seriesAcronym)) {
        http_response_code(400);
        exit();
    }

    $seriesID = $request['SeriesID'];
    if (!empty($seriesID)) {
        $updateStmt = $conn->prepare("
            UPDATE tournament_series
            SET Name = ?, Acronym = ?
            WHERE SeriesID = ?
        ");
        $updateStmt->bind_param('ssi', $seriesName, $seriesAcronym, $seriesID);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        $insertStmt = $conn->prepare("
            INSERT INTO tournament_series (Name, Acronym)
            VALUES (?, ?)
        ");
        $insertStmt->bind_param('ss', $seriesName, $seriesAcronym);
        $insertStmt->execute();
        $seriesID = $insertStmt->insert_id;
        $insertStmt->close();
    }

    $statusStmt = $conn->prepare("
        UPDATE tournament_series_edit_requests
        SET Status = 'Approved',
            AdminID = ?,
            SeriesID = ?,
            UpdatedAt = CURRENT_TIMESTAMP
        WHERE EditID = ?
    ");
    $statusStmt->bind_param('iii', $userId, $seriesID, $editID);
    $statusStmt->execute();
    $statusStmt->close();

} else {
    $statusStmt = $conn->prepare("
        UPDATE tournament_series_edit_requests
        SET Status = ?,
            AdminID = ?,
            UpdatedAt = CURRENT_TIMESTAMP
        WHERE EditID = ?
    ");
    $statusStmt->bind_param('sii', $newStatus, $userId, $editID);
    $statusStmt->execute();
    $statusStmt->close();
}

echo "OK";
