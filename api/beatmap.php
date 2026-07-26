<?php
require 'init.php';

$beatmapID = $_GET['id'] ?? null;

if (!$beatmapID) {
    die(json_encode(array("error" => "Missing beatmap ID")));
}

$stmt = $conn->prepare("SELECT b.*, bs.Artist, bs.Title 
    FROM beatmaps b 
    JOIN beatmapsets bs ON b.SetID = bs.SetID 
    WHERE b.BeatmapID = ? AND b.Blacklisted = 0 
    ORDER BY b.SR DESC;
");
$stmt->bind_param("i", $beatmapID);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($result != null) {
    $response = array(
        "SetID" => $result["SetID"],
        "BeatmapID" => $result["BeatmapID"],
        "Artist" => $result["Artist"],
        "Title" => $result["Title"],
        "Difficulty" => $result["DifficultyName"],
        "ChartRank" => $result["ChartRank"],
        "ChartYearRank" => $result["ChartYearRank"],
        "RatingCount" => $result["RatingCount"],
        "WeightedAvg" => $result["WeightedAvg"],
    );
    
    $stmt = $conn->prepare("SELECT Score FROM ratings WHERE BeatmapID = ? AND UserID = ?");
    $stmt->bind_param("ii", $beatmapID, $userID);
    $stmt->execute();
    $ownRating = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $response["OwnRating"] = $ownRating["Score"] ?? null;
    
    $stmt = $conn->prepare("SELECT Score, COUNT(*) as Count FROM ratings WHERE BeatmapID = ? GROUP BY Score ORDER BY Score");
    $stmt->bind_param("i", $beatmapID);
    $stmt->execute();
    $ratingsResult = $stmt->get_result();
    $stmt->close();
    
    $ratingsCounts = array();
    while ($ratingRow = $ratingsResult->fetch_assoc()) {
        $ratingsCounts[$ratingRow["Score"]] = $ratingRow["Count"];
    }
    $response["Ratings"] = $ratingsCounts;
    
    $stmt = $conn->prepare("SELECT bd.DescriptorID, d.Name
        FROM beatmap_descriptors bd
        JOIN descriptors d ON bd.DescriptorID = d.DescriptorID
        WHERE bd.BeatmapID = ?
        ORDER BY bd.Weight DESC, bd.DescriptorID
        LIMIT 5
    ");
    $stmt->bind_param("i", $beatmapID);
    $stmt->execute();
    $descriptorResult = $stmt->get_result()->fetch_all();
    $stmt->close();

    $response["Descriptors"] = implode(', ', array_column($descriptorResult, 0));
    
    $stmt = $conn->prepare("SELECT bn.NominatorID as UserID, m.Username as Username 
        FROM beatmapset_nominators bn 
        JOIN mappernames m ON bn.NominatorID = m.UserID 
        WHERE bn.SetID = ?;
    ");
    $stmt->bind_param("i", $result["SetID"]);
    $stmt->execute();
    $nominatorResult = $stmt->get_result();

    $nominators = [];
    while($nominator = $nominatorResult->fetch_assoc()) {
        $nominators[] = array(
            "UserID" => $nominator["UserID"],
            "Username" => $nominator["Username"]
        );
    }
    $stmt->close();
    $response["Nominators"] = $nominators;
    
    echo json_encode($response);
} else {
    echo json_encode(array("error" => "Difficulty not found"));
}
?>