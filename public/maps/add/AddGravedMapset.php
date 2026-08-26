<?php
    include '../../base.php';

    if (!$loggedIn) {
        http_response_code(401);
        exit();
    }

    $requestedRaw = trim($_GET["id"] ?? "");

    if ($requestedRaw === "") {
        http_response_code(400);
        exit();
    }

    if (ctype_digit($requestedRaw)) {
        $requestedSetId = (int)$requestedRaw;
    } elseif (preg_match('~^https?://osu\.ppy\.sh/(?:s|beatmapsets)/(?P<setid>\d+)~', $requestedRaw, $matches)) {
        $requestedSetId = (int)$matches['setid'];
    } else {
        http_response_code(400);
        exit();
    }

    AddGraveyardSetToOMDB($conn, $token, $requestedSetId);

    header("Location: ../../mapset/" . $requestedSetId);
    exit();
