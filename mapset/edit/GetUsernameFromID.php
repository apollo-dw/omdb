<?php
    require '../../base.php';
    $id = $_GET["id"];
    echo GetUserNameFromId($id, $conn);