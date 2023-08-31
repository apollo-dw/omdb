<?php
    include '../base.php';

    $body = file_get_contents('php://input');
    $body_json = json_decode($body, true);

    $random = $body_json["randomBehaviour"] ?? -1;
    $ratingNames = $body_json["ratingNames"];
    $hideRatings = $body_json["hideRatings"];

    $stmt = $conn->prepare("UPDATE `users` SET `DoTrueRandom`=?, `Custom50Rating`=?, `Custom45Rating`=?, `Custom40Rating`=?, `Custom35Rating`=?, `Custom30Rating`=?, `Custom25Rating`=?, `Custom20Rating`=?, `Custom15Rating`=?, `Custom10Rating`=?, `Custom05Rating`=?, `Custom00Rating`=?, `HideRatings`=? WHERE `UserID`=?");
    $stmt->bind_param("sssssssssssssi", $random, $ratingNames[0], $ratingNames[1], $ratingNames[2], $ratingNames[3], $ratingNames[4], $ratingNames[5], $ratingNames[6], $ratingNames[7], $ratingNames[8], $ratingNames[9], $ratingNames[10], $hideRatings, $userId);
    $stmt->execute();
