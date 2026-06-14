<?php
    require "../../base.php";
    header('Content-Type: application/json');

    if (!$loggedIn) {
        die(json_encode(array("error" => "log in")));
    }

    $postData = file_get_contents("php://input");
    $decodedData = json_decode($postData, true);

    if ($decodedData !== null) {
        $listTitle = $decodedData["listTitle"];
        $listDescription = $decodedData["listDescription"];
        $listPrivate = !empty($decodedData["listPrivate"]) ? 1 : 0;
        $items = $decodedData["items"];
        $listId = $decodedData["listId"] ?? "";

        if ($listId === "") {
            $stmt = $conn->prepare("INSERT INTO lists (Title, Description, UserID, Private) VALUES (?, ?, ?, ?);");
            $stmt->bind_param("ssii", $listTitle, $listDescription, $userId, $listPrivate);
            $stmt->execute();
            $listId = $stmt->insert_id;
            $stmt->close();
        } else {
            $stmt = $conn->prepare("SELECT UserID FROM lists WHERE UserID = ? AND ListID = ?;");
            $stmt->bind_param("ii", $userId, $listId);
            $stmt->execute();
            $existingList = $stmt->get_result()->fetch_assoc();

            if (is_null($existingList))
                die(json_encode(array("error" => "not yours")));

            $stmt->close();

            $stmt = $conn->prepare("UPDATE lists SET Title = ?, Description = ?, Private = ?, UpdatedAt = CURRENT_TIMESTAMP WHERE ListID = ? AND UserID = ?;");
            $stmt->bind_param("ssiii", $listTitle, $listDescription, $listPrivate, $listId, $userId);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("DELETE FROM list_items WHERE ListID = ?;");
            $stmt->bind_param("i", $listId);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("INSERT INTO list_items (`ListID`, `Type`, `SubjectID`, `Description`, `order`) VALUES (?, ?, ?, ?, ?);");
        foreach ($items as $item) {
            $type = $item["type"];
            $subjectId = $item["id"];
            $description = $item["description"];
            $order = $item["order"];

            $stmt->bind_param("isisi", $listId, $type, $subjectId, $description, $order);
            $stmt->execute();
        }
        $stmt->close();

        echo json_encode(array("id" => $listId));
    }