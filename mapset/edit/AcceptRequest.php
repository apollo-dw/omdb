<?php
require '../../base.php';

if (!$loggedIn || !isIdEditRequestAdmin($userId)) {
    header('HTTP/1.0 403 Forbidden');
    http_response_code(403);
    die("Forbidden");
}

$beatmapID = $_GET["BeatmapID"];
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
    $request = $result->fetch_assoc();

    if ($request) {
        $editDataArray = json_decode($request['EditData'], true);
        $newNominators = $editDataArray["Mappers"];
		$newCredits = $editDataArray["Credits"];

        $stmt = $conn->prepare("DELETE FROM beatmapset_nominators WHERE `SetID` = ?;");
        $stmt->bind_param('i', $setID);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO beatmapset_nominators (`SetID`, `NominatorID`) VALUES (?, ?);");
        $stmt->bind_param('ii', $setID, $nominatorID);
        foreach ($newNominators as $nominatorID) {
            $stmt->execute();
        }
		
		$stmt = $conn->prepare("DELETE FROM beatmapset_credits WHERE `SetID` = ?;");
		$stmt->bind_param('i', $setID);
		$stmt->execute();

		$stmt = $conn->prepare("INSERT INTO beatmapset_credits (`SetID`, `MapID`, `RoleID`, `UserID`) VALUES (?, NULL, ?, ?);");
		$stmt->bind_param('iii', $setID, $roleID, $userID);

		foreach ($newCredits as $credit) {
			$roleIDStmt = $conn->prepare("SELECT RoleID FROM beatmap_roles WHERE Name = ?");
			$roleIDStmt->bind_param('s', $credit['role']);
			$roleIDStmt->execute();
			$roleIDResult = $roleIDStmt->get_result();
			$roleIDRow = $roleIDResult->fetch_assoc();
			$roleID = $roleIDRow['RoleID'];

			$userID = $credit['userID'];
			$stmt->execute();
		}

        $stmt = $conn->prepare("UPDATE beatmap_edit_requests SET Status = 'Approved', EditorID = ? WHERE `EditID` = ?;");
        $stmt->bind_param('ii', $userId, $request['EditID']);
        $stmt->execute();
    }
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
    $request = $result->fetch_assoc();

    if ($request) {
        $editDataArray = json_decode($request['EditData'], true);
        $newMappers = $editDataArray["Mappers"];

        $stmt = $conn->prepare("DELETE FROM beatmap_creators WHERE `BeatmapID` = ?;");
        $stmt->bind_param('i', $beatmapID);
        $stmt->execute();

        $stmt = $conn->prepare("INSERT INTO beatmap_creators (`BeatmapID`, `CreatorID`) VALUES (?, ?);");
        $stmt->bind_param('ii', $beatmapID, $creatorID);
        foreach ($newMappers as $creatorID) {
            $stmt->execute();
        }

        $stmt = $conn->prepare("UPDATE beatmap_edit_requests SET Status = 'Approved', EditorID = ? WHERE `EditID` = ?;");
        $stmt->bind_param('ii', $userId, $request['EditID']);
        $stmt->execute();
    }
}

header('Location: ../edit/?id=' . $setID);