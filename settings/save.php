<?php
    include '../base.php';

    $random = $_GET['random'] ?? -1;
    $ratings = $_GET['ratings'] ?? ".";

    if($random == -1 || $ratings == ".")
        die("NO");

    $ratingNames = preg_split ("/\,/", $ratings);

    $stmt = $conn->prepare("UPDATE `users` SET `DoTrueRandom`=?, `Custom50Rating`=?, `Custom45Rating`=?, `Custom40Rating`=?, `Custom35Rating`=?, `Custom30Rating`=?, `Custom25Rating`=?, `Custom20Rating`=?, `Custom15Rating`=?, `Custom10Rating`=?, `Custom05Rating`=?, `Custom00Rating`=? WHERE `UserID`=?");
    $stmt->bind_param("ssssssssssssi", $random, $ratingNames[0], $ratingNames[1], $ratingNames[2], $ratingNames[3], $ratingNames[4], $ratingNames[5], $ratingNames[6], $ratingNames[7], $ratingNames[8], $ratingNames[9], $ratingNames[10], $userId);
    $stmt->execute();