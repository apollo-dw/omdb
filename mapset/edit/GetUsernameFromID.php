<?php
    require '../../base.php';
    $id = $_GET["id"];

    if (!is_numeric($id))
        return;

    echo htmlspecialchars(GetUserNameFromId($id, $conn), ENT_QUOTES);