<?php
    require __DIR__ . '/../base.php';

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header("Access-Control-Allow-Headers: X-Requested-With");
    header('Content-Type: application/json; charset=utf-8');

    $apiKey = $_GET["key"] ?? "-1";

    if ($apiKey === "-1") {
        die(json_encode(array("error" => "Invalid request - missing api key")));
    }

    $stmt = $conn->prepare("SELECT UserID FROM `apikeys` WHERE ApiKey = ?;");
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 0) {
        die(json_encode(array("error" => "Invalid api key")));
    }

    $row = $result->fetch_assoc();
    $userID = $row["UserID"];
    $stmt->close();
?>