<?php
    require 'init.php';

    $setID = $_GET['id'] ?? null;

    if (!$setID) {
        die(json_encode(array("error" => "Missing set ID")));
    }

    $stmt = $conn->prepare("SELECT Artist, Title FROM beatmapsets WHERE SetID = ?");
    $stmt->bind_param("i", $setID);
    $stmt->execute();
    $setMetadata = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$setMetadata) {
        die(json_encode(array("error" => "Mapset not found")));
    }

    $stmt = $conn->prepare("SELECT bn.NominatorID as UserID, m.Username as Username 
        FROM beatmapset_nominators bn 
        JOIN mappernames m ON bn.NominatorID = m.UserID 
        WHERE bn.SetID = ?;
    ");
    $stmt->bind_param("i", $setID);
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

    $difficulties = array();

    $stmt = $conn->prepare("SELECT * FROM beatmaps WHERE SetID = ? AND Blacklisted = 0 ORDER BY SR DESC;");
    $stmt->bind_param("i", $setID);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $beatmapID = $row["BeatmapID"];

        $stmt2 = $conn->prepare("SELECT Score FROM ratings WHERE BeatmapID = ? AND UserID = ?");
        $stmt2->bind_param("ii", $beatmapID, $userID);
        $stmt2->execute();
        $ratingResult = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        $rating = $ratingResult ? $ratingResult["Score"] : null;
        
        $stmt3 = $conn->prepare("SELECT Score, COUNT(*) as Count FROM ratings WHERE BeatmapID = ? GROUP BY Score ORDER BY Score");
        $stmt3->bind_param("i", $beatmapID);
        $stmt3->execute();
        $ratingsResult = $stmt3->get_result();
        $stmt3->close();

        $ratingsCounts = array();
        while ($ratingRow = $ratingsResult->fetch_assoc()) {
            $ratingsCounts[$ratingRow["Score"]] = $ratingRow["Count"];
        }

        $stmt4 = $conn->prepare("SELECT bd.DescriptorID, d.Name
            FROM beatmap_descriptors bd
            JOIN descriptors d ON bd.DescriptorID = d.DescriptorID
            WHERE bd.BeatmapID = ?
            ORDER BY bd.Weight DESC, bd.DescriptorID
            LIMIT 5
        ");
        $stmt4->bind_param("i", $beatmapID);
        $stmt4->execute();
        $descriptorResult = $stmt4->get_result()->fetch_all();
        $stmt4->close();

        $descriptors = implode(', ', array_column($descriptorResult, 0));

        $difficulties[] = array(
            "BeatmapID" => $row["BeatmapID"],
            "Difficulty" => $row["DifficultyName"],
            "ChartRank" => $row["ChartRank"],
            "ChartYearRank" => $row["ChartYearRank"],
            "RatingCount" => $row["RatingCount"],
            "WeightedAvg" => $row["WeightedAvg"],
            "OwnRating" => $rating,
            "Ratings" => $ratingsCounts,
            "Descriptors" => $descriptors
        );
    }

    if (sizeof($difficulties) == 0) {
        echo json_encode(array("error" => "No valid difficulties found for this mapset"));
    } else {
        echo json_encode(array(
            "SetID" => $setID,
            "Artist" => $setMetadata["Artist"],
            "Title" => $setMetadata["Title"],
            "Nominators" => $nominators,
            "Difficulties" => $difficulties
        ));
    }
?>