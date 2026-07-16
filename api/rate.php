<?php
    require 'init.php';

    $beatmapID = $_GET['id'] ?? null;
    $score = $_GET['score'] ?? null;

    if (!$beatmapID || !$score) {
        die(json_encode(array("error" => "Missing parameters")));
    }

    $result = SubmitRating($conn, $beatmapID, $userID, $score);

    if ($result) {
        echo json_encode(array("success" => "rating submitted"));
    } else {
        echo json_encode(array("error" => "rating not submitted"));
    }
?>