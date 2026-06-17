<?php
    require '../../base.php';
    $id = $_GET["id"];

    if (!is_numeric($id))
        return;

    echo safe_htmlspecialchars(GetUserNameFromId($id, $conn), ENT_QUOTES);