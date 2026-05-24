<?php
require '../../base.php';

if (!$loggedIn) {
    die("NO");
}

header('Content-Type: application/json');

/*
    Step 1: Clear existing computed descriptor table
    (we fully rebuild it for consistency)
*/
$conn->query("TRUNCATE TABLE beatmap_descriptors");

/*
    Step 2: Get all beatmaps
*/
$beatmapsStmt = $conn->prepare("SELECT BeatmapID FROM beatmaps");
$beatmapsStmt->execute();
$beatmapsResult = $beatmapsStmt->get_result();

$rebuildCount = 0;

/*
    Step 3: Process each beatmap
*/
while ($beatmap = $beatmapsResult->fetch_assoc()) {

    $beatmapID = $beatmap['BeatmapID'];

    /*
        Step 3a: Get all descriptors that have votes for this beatmap
    */
    $stmt = $conn->prepare("
        SELECT 
            DescriptorID,
            SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) AS upvotes,
            SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END) AS downvotes
        FROM descriptor_votes
        WHERE BeatmapID = ?
        GROUP BY DescriptorID
    ");

    $stmt->bind_param("i", $beatmapID);
    $stmt->execute();
    $result = $stmt->get_result();

    /*
        Step 3b: Evaluate each descriptor
    */
    while ($row = $result->fetch_assoc()) {

        $descriptorID = $row['DescriptorID'];
        $upvotes = (int)$row['upvotes'];
        $downvotes = (int)$row['downvotes'];
        $net = $upvotes - $downvotes;

        /*
            Only persist valid descriptors
        */
        if ($net > 0) {

            $insertStmt = $conn->prepare("
                INSERT INTO beatmap_descriptors (BeatmapID, DescriptorID, Weight)
                VALUES (?, ?, ?)
            ");

            $insertStmt->bind_param("iid", $beatmapID, $descriptorID, $net);
            $insertStmt->execute();

            $rebuildCount++;
        }
    }
}

/*
    Step 4: Response
*/
echo json_encode([
    "status" => "OK",
    "rebuilt_entries" => $rebuildCount
]);