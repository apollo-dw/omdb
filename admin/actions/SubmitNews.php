<?php
require_once ('../../base.php');
require_once '../base.php';

requireCSRF();

header('Content-Type: text/plain');

if ($userId === -1) {
    http_response_code(403);
    echo "Not logged in";
    exit;
}

$newsID = (int)($_POST["newsID"] ?? 0);
$title = trim($_POST["title"] ?? "");
$content = trim($_POST["content"] ?? "");

if ($title === "" || $content === "") {
    http_response_code(400);
    echo "U forgot title (or content)";
    exit;
}

if ($newsID > 0) {
    $stmt = $conn->prepare("UPDATE news_posts SET Title = ?, Content = ?, DateEdited = NOW() WHERE NewsID = ?");
    $stmt->bind_param("ssi", $title, $content, $newsID);
} else {
    $stmt = $conn->prepare("INSERT INTO news_posts (Title, Content, AuthorID) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $title, $content, $userId);
}

if ($stmt->execute()) {
    echo "OK";
} else {
    http_response_code(500);
    echo "Database error";
}

$stmt->close();