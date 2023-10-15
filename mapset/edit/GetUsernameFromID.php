<?php
    require '../../base.php';
    $id = $_GET["id"];

    if (!is_numeric($id))
        return;

    echo GetUserNameFromId($id, $conn);