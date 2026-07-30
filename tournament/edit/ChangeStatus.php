<?php
require "../../base.php";

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

try {
    $stmt = $conn->prepare("
        SELECT EditID, TournamentID, EditData, Status
        FROM tournament_edit_requests
        WHERE EditID = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $editID);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$request) {
        http_response_code(404);
        exit();
    }

    if ($newStatus === 'Denied' || $newStatus === 'Pending') {
        $stmt = $conn->prepare("UPDATE tournament_edit_requests SET Status = ?, UpdatedAt = CURRENT_TIMESTAMP WHERE EditID = ?");
        $stmt->bind_param("si", $newStatus, $editID);
        $stmt->execute();
        $stmt->close();

        header('Location: ./?id=' . $editID);
        exit();
    }

    $data = json_decode($request['EditData'], true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($data['Tournament'])) {
        http_response_code(400);
        die("Corrupted request payload.");
    }

    $conn->begin_transaction();

    $tData = $data['Tournament'];
    $tournamentID = $request['TournamentID'];
    $seriesID = isset($tData['SeriesID']) && $tData['SeriesID'] !== '' ? (int)$tData['SeriesID'] : null;
    $startDate = $tData['StartDate'] !== '' ? $tData['StartDate'] : null;
    $endDate = $tData['EndDate'] !== '' ? $tData['EndDate'] : null;

    if ($tournamentID !== null) {
        $stmt = $conn->prepare("
            UPDATE tournaments
            SET Name = ?, Acronym = ?, SeriesID = ?, StartDate = ?, EndDate = ?, UpdatedAt = CURRENT_TIMESTAMP
            WHERE TournamentID = ?
        ");

        $stmt->bind_param("ssissi", $tData['Name'], $tData['Acronym'], $seriesID, $startDate, $endDate, $tournamentID);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("
            INSERT INTO tournaments (Name, Acronym, SeriesID, StartDate, EndDate)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssiss", $tData['Name'], $tData['Acronym'], $seriesID, $startDate, $endDate);
        $stmt->execute();
        $tournamentID = $conn->insert_id;
        $stmt->close();
    }

    $stmt = $conn->prepare("
        DELETE tm FROM tournament_maps tm
        INNER JOIN tournament_stages ts ON tm.StageID = ts.StageID
        WHERE ts.TournamentID = ?
    ");
    $stmt->bind_param("i", $tournamentID);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM tournament_stages WHERE TournamentID = ?");
    $stmt->bind_param("i", $tournamentID);
    $stmt->execute();
    $stmt->close();

    if (!empty($data['Stages']) && is_array($data['Stages'])) {
        $stageStmt = $conn->prepare("
            INSERT INTO tournament_stages (TournamentID, Name, Acronym, SortOrder)
            VALUES (?, ?, ?, ?)
        ");

        $mapStmt = $conn->prepare("
            INSERT INTO tournament_maps (TournamentID, StageID, BeatmapID, Slot, SortOrder, IsCustom)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($data['Stages'] as $stageIdx => $stage) {
            $stageOrder = isset($stage['SortOrder']) ? (int)$stage['SortOrder'] : ($stageIdx + 1);
            $stageStmt->bind_param("issi", $tournamentID, $stage['Name'], $stage['Acronym'], $stageOrder);
            $stageStmt->execute();
            $newStageID = $conn->insert_id;

            if (!empty($stage['Maps']) && is_array($stage['Maps'])) {
                foreach ($stage['Maps'] as $mapIdx => $map) {
                    $mapOrder = isset($map['SortOrder']) ? (int)$map['SortOrder'] : ($mapIdx + 1);
                    $beatmapID = (int)$map['BeatmapID'];
                    $slot = $map['Slot'];
                    $isCustom = isset($map['IsCustom']) ? (int)$map['IsCustom'] : 0;


                    $mapStmt->bind_param("iiissi", $tournamentID, $newStageID, $beatmapID, $slot, $mapOrder, $isCustom);
                    $mapStmt->execute();
                }
            }
        }

        $stageStmt->close();
        $mapStmt->close();
    }

    $stmt = $conn->prepare("
        UPDATE tournament_edit_requests
        SET TournamentID = ?, AdminID = ?, Status = 'Approved', UpdatedAt = CURRENT_TIMESTAMP
        WHERE EditID = ?
    ");
    $stmt->bind_param("iii", $tournamentID, $userId, $editID);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    header('Location: ./?id=' . $editID);
    exit();

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("Approval transaction error in ChangeStatus.php: " . $e->getMessage());
    http_response_code(500);
    die($e->getMessage());
}
