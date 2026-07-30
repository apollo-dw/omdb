<?php
    include '../base.php';

    $beatmapId = $_POST['bID'] ?? -1;
    $rating = $_POST['rating'] ?? -1;
    if ($beatmapId == -1) {
        http_response_code(400);
        exit();
    }

    $validRatings = array(-2, 0, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5);
    if (!in_array($rating, $validRatings)) {
        http_response_code(400);
        exit();
    }

    $stmt = $conn->prepare("SELECT BeatmapID FROM `beatmaps` WHERE `BeatmapID`= ?;");
    $stmt->bind_param("i", $beatmapId);
    $stmt->execute();

    if ($stmt->get_result()->fetch_row()[0] != 1) {
        http_response_code(404);
        exit();
    }

    $stmt->close();

    if ($loggedIn == false) {
        http_response_code(401);
        exit();
    }

    SubmitRating($conn, $beatmapId, $userId, $rating);
