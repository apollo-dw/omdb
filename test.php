<?php
	require ('base.php');
	
	ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
	
function getRecommendations($beatmapID, $conn) {
    // Step 1: Get descriptors of the given BeatmapID
    $stmt = $conn->prepare("
        SELECT d.DescriptorID, d.Name
        FROM descriptor_votes 
        JOIN descriptors d ON descriptor_votes.DescriptorID = d.DescriptorID
        WHERE BeatmapID = ?
        GROUP BY d.DescriptorID
        HAVING SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) > (SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END) + 0)
        ORDER BY (SUM(CASE WHEN Vote = 1 THEN 1 ELSE 0 END) - SUM(CASE WHEN Vote = 0 THEN 1 ELSE 0 END)) DESC, d.DescriptorID
        LIMIT 5;
    ");
    $stmt->bind_param("i", $beatmapID);
    $stmt->execute();
    $result = $stmt->get_result();

    $descriptorIDs = [];
    while ($row = $result->fetch_assoc()) {
        $descriptorIDs[] = $row['DescriptorID'];
    }
    $stmt->close();

    // Step 2: Find users who rated this BeatmapID highly (e.g., 4 or 5)
    $stmt = $conn->prepare("
        SELECT DISTINCT UserID 
        FROM ratings 
        WHERE BeatmapID = ? AND Score >= 4
    ");
    $stmt->bind_param("i", $beatmapID);
    $stmt->execute();
    $result = $stmt->get_result();

    $userIDs = [];
    while ($row = $result->fetch_assoc()) {
        $userIDs[] = $row['UserID'];
    }
    $stmt->close();

    if (empty($userIDs)) {
        echo "No recommendations available.";
        return;
    }

    // Step 3: Find BeatmapIDs that these users also rated highly and compute the recommendation score
    $userPlaceholders = implode(',', array_fill(0, count($userIDs), '?'));
    $descriptorPlaceholders = implode(',', array_fill(0, count($descriptorIDs), '?'));
    $types = str_repeat('i', count($userIDs) + count($descriptorIDs) + 1 + 1 + 1);
    
    $stmt = $conn->prepare("
        SELECT r.BeatmapID, b.SetID, b.DifficultyName, AVG(r.Score) as AvgScore, COUNT(r.Score) as ScoreCount,
               COUNT(DISTINCT dv.DescriptorID) as MatchingDescriptors,
               b.Timestamp as BeatmapTimestamp,
               ABS(TIMESTAMPDIFF(YEAR, (SELECT Timestamp FROM beatmaps WHERE BeatmapID = ?), b.Timestamp)) AS YearDiff
        FROM ratings r
        LEFT JOIN beatmaps b ON b.BeatmapID = r.BeatmapID
        LEFT JOIN descriptor_votes dv ON dv.BeatmapID = r.BeatmapID AND dv.DescriptorID IN ($descriptorPlaceholders)
        WHERE r.UserID IN ($userPlaceholders) 
        AND r.BeatmapID != ? 
        GROUP BY r.BeatmapID 
        HAVING AvgScore >= 4 AND ScoreCount > 15 AND ScoreCount < 80
        ORDER BY (
            AVG(r.Score) * 1 + 
            COUNT(DISTINCT dv.DescriptorID) * 1.5 + 
            IF(ABS(TIMESTAMPDIFF(YEAR, (SELECT Timestamp FROM beatmaps WHERE BeatmapID = ?), b.Timestamp)) <= 2, 2, 0)
        ) DESC
        LIMIT 10;
    ");
	
	echo $conn->error;
    
    // Bind dynamic parameters
    $params = array_merge([$beatmapID], $descriptorIDs, $userIDs, [$beatmapID], [$beatmapID]);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // Step 4: Fetch and print the top 10 recommended BeatmapIDs with their scores
    $recommendations = [];
    while ($row = $result->fetch_assoc()) {
        $recommendations[] = [
			'SetID' => $row['SetID'],
            'BeatmapID' => $row['BeatmapID'],
            'DifficultyName' => $row['DifficultyName'],
            'AvgScore' => $row['AvgScore'],
            'MatchingDescriptors' => $row['MatchingDescriptors'],
            'YearDiff' => $row['YearDiff']
        ];
    }
    $stmt->close();

    if (empty($recommendations)) {
        echo "No recommendations available.";
    } else {
        echo "Recommendations for BeatmapID $beatmapID:<br>";
        foreach ($recommendations as $rec) {
            echo $rec['BeatmapID'] . " / {$rec['SetID']}" . " (" . $rec['DifficultyName'] . ") - " .
                 "Average Score: " . round($rec['AvgScore'], 2) . ", " .
                 "Matching Descriptors: " . $rec['MatchingDescriptors'] . ", " .
                 "Year diff: {$rec['YearDiff']}<br>";
        }
    }
}

// Example usage
$beatmapID = $_GET['id'] ?? 131891;  // Replace with the actual BeatmapID you want recommendations for
getRecommendations($beatmapID, $conn);
?>
