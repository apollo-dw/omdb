<?php
    include '../base.php';

    $body = file_get_contents('php://input');
    $body_json = json_decode($body, true);

    $random = $body_json["randomBehaviour"] ?? -1;
    $ratingNames = $body_json["ratingNames"];
    $hideRatings = $body_json["hideRatings"];
	$customDescription = $body_json["customDescription"];
	$onlyFriendsOnFrontPage = $body_json["onlyFriendsOnFrontPage"];
	
	ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
	
    $stmt = $conn->prepare("UPDATE `users` SET `DoTrueRandom`=?, `Custom50Rating`=?, `Custom45Rating`=?, `Custom40Rating`=?, `Custom35Rating`=?, `Custom30Rating`=?, `Custom25Rating`=?, `Custom20Rating`=?, `Custom15Rating`=?, `Custom10Rating`=?, `Custom05Rating`=?, `Custom00Rating`=?, `HideRatings`=?, `CustomDescription`=?, `OnlyFriendsOnFrontPage`=? WHERE `UserID`=?");
    $stmt->bind_param("sssssssssssssssi", $random, $ratingNames[0], $ratingNames[1], $ratingNames[2], $ratingNames[3], $ratingNames[4], $ratingNames[5], $ratingNames[6], $ratingNames[7], $ratingNames[8], $ratingNames[9], $ratingNames[10], $hideRatings, $customDescription, $onlyFriendsOnFrontPage, $userId);
    $stmt->execute();
