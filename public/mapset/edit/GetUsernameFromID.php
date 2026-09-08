<?php
    require_once __DIR__ . '/../../../app/base.php';
    header('Content-Type: application/json; charset=utf-8');

    $id = $_GET["id"];
    if (!is_numeric($id)) {
        $usernameStmt = $conn->prepare("SELECT Username, UserID FROM `mappernames` WHERE `Username` = ?");

        if (str_contains($id, "@") || preg_match("/[a-z]/i", $id)) {
            if ($id[0] === "@") {
                $id = substr($id, 1);
            }

            $usernameStmt->bind_param("s", $id);
            $usernameStmt->execute();
            $result = $usernameStmt->get_result();
            $profile = $result->fetch_assoc();
            $usernameStmt->close();

            if ($profile === null) {
                echo json_encode([
                    'success' => false,
                ]);
                exit();
            }

            echo json_encode([
                'success' => true,
                'id' => $profile["UserID"],
                'username' => safe_htmlspecialchars($profile["Username"], ENT_QUOTES),
            ]);
            exit();
        }

        echo json_encode([
            'success' => false,
        ]);
        exit();
    }

    $username = safe_htmlspecialchars(GetUserNameFromId($id, $conn), ENT_QUOTES);

    echo json_encode([
        'success' => true,
        'id' => $id,
        'username' => $username,
    ]);
    exit();
