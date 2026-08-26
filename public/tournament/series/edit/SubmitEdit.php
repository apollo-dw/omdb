<?php
    require_once __DIR__ . '/../../../../app/base.php';

    if (!$loggedIn) {
        http_response_code(401);
        exit();
    }

    $seriesId = isset($_POST['SeriesID']) ? (int)$_POST['SeriesID'] : null;
    $seriesName = trim($_POST['SeriesName'] ?? '');
    $seriesAcronym = trim($_POST['SeriesAcronym'] ?? '');
    $editorId = $userId;

    if (empty($seriesName) || empty($seriesAcronym)) {
        http_response_code(400);
        exit();
    }

    $editData = json_encode([
        'SeriesName' => $seriesName,
        'SeriesAcronym' => $seriesAcronym
    ], JSON_UNESCAPED_UNICODE);

    $existingEditId = null;
    $existingEditorId = null;
    if ($seriesId !== null) {
        $stmt = $conn->prepare("
            SELECT EditID, EditorID
            FROM tournament_series_edit_requests
            WHERE SeriesID = ? AND Status = 'Pending'
            LIMIT 1;
        ");
        $stmt->bind_param("i", $seriesId);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $existingEditId = $row['EditID'];
            $existingEditorId = $row['EditorID'];
        }
        $stmt->close();
    }

    if ($existingEditId !== null) {
        if ($editorId !== $existingEditorId) {
            http_response_code(403);
            exit();
        }

        $stmt = $conn->prepare("
            UPDATE tournament_series_edit_requests
            SET EditData = ?,
                EditorID = ?,
                UpdatedAt = CURRENT_TIMESTAMP
            WHERE EditID = ?;
        ");
        $stmt->bind_param("sii", $editData, $editorId, $existingEditId);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("
            INSERT INTO tournament_series_edit_requests
            (SeriesID, EditData, Status, EditorID)
            VALUES
            (?, ?, 'Pending', ?);
        ");
        $stmt->bind_param("isi", $seriesId, $editData, $editorId);
        $stmt->execute();

        $editId = $conn->insert_id;
        $stmt->close();
    }

    header('Location: ./?id=' . $editId ?? $existingEditId);
    exit();
