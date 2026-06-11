<?php
    require_once ('../../base.php');
    require ('../base.php');

    $id = $_GET["id"];

    $stmt = $conn->prepare("SELECT Username FROM mappernames WHERE UserID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo $result->fetch_assoc()["Username"];