<?php
    include '../../base.php';
    header('Content-Type: application/json');

    $id = $_GET["id"];

    if (!is_numeric($id))
        die(json_encode(array("error" => "id not valid")));

    if (!$loggedIn)
        die(json_encode(array("error" => "no logged in")));

    $stmt = $conn->prepare("SELECT UserID FROM lists WHERE UserID = ? AND ListID = ?;");
    $stmt->bind_param("ii", $userId, $id);
    $stmt->execute();
    $list = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (is_null($list))
        die(json_encode(array("error" => "not yours, or doesn't exist")));

    $stmt = $conn->prepare("DELETE FROM list_hearts WHERE ListID = ?;");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM list_items WHERE ListID = ?;");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM lists WHERE ListID = ?;");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();