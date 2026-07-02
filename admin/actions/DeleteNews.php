<?php
require_once '../base.php';

header('Content-Type: text/plain');

if ($userId === -1) {
    http_response_code(403);
    echo "Not logged in";
    exit;
}

$newsID = (int)($_POST["newsID"] ?? 0);

if ($newsID <= 0) {
    http_response_code(400);
    echo "Invalid news ID";
    exit;
}

$stmt = $conn->prepare("DELETE FROM news_posts WHERE NewsID = ?");
$stmt->bind_param("i", $newsID);

if ($stmt->execute()) {
    echo "OK";
} else {
    http_response_code(500);
    echo "Database error";
}

$stmt->close();