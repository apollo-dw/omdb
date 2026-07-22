<?php
    include '../base.php';

    $body = file_get_contents('php://input');
    $body_json = json_decode($body, true);

    $random = $body_json["randomBehaviour"] ?? -1;
    $ratingNames = $body_json["ratingNames"] ?? [];
    $hideRatings = $body_json["hideRatings"] ?? 0;
    $customDescription = $body_json["customDescription"] ?? '';
    $onlyFriendsOnFrontPage = $body_json["onlyFriendsOnFrontPage"] ?? 0;
    
    $fields = [
        "`DoTrueRandom`=?",
        "`Custom50Rating`=?",
        "`Custom45Rating`=?",
        "`Custom40Rating`=?",
        "`Custom35Rating`=?",
        "`Custom30Rating`=?",
        "`Custom25Rating`=?",
        "`Custom20Rating`=?",
        "`Custom15Rating`=?",
        "`Custom10Rating`=?",
        "`Custom05Rating`=?",
        "`Custom00Rating`=?",
        "`HideRatings`=?",
        "`CustomDescription`=?",
        "`OnlyFriendsOnFrontPage`=?"
    ];

    $params = [
        $random,
        $ratingNames[0], $ratingNames[1], $ratingNames[2], $ratingNames[3], $ratingNames[4],
        $ratingNames[5], $ratingNames[6], $ratingNames[7], $ratingNames[8], $ratingNames[9], $ratingNames[10],
        $hideRatings,
        $customDescription,
        $onlyFriendsOnFrontPage
    ];

    $types = "sssssssssssssss";

    if (
        isset($body_json['profileTheme']) &&
        is_array($body_json['profileTheme']) &&
        count($body_json['profileTheme']) > 0
    ) {
        $fields[] = "`ProfileTheme`=?";
        $params[] = json_encode($body_json['profileTheme']);
        $types .= "s";
    }

    $query = "UPDATE `users` SET " . implode(", ", $fields) . " WHERE `UserID`=?";
    $params[] = $userId;
    $types .= "i";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();