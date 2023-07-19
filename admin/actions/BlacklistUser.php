<?php
    require_once ('../../base.php');
    require ('../base.php');

    $blacklistUserID = $_POST['blacklistID'];

    $stmt = $conn->prepare("INSERT INTO blacklist VALUES (?)");
    $stmt->bind_param("s", $blacklistUserID);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE beatmaps SET ChartRank = NULL, ChartYearRank = NULL, Rating = NULL, Blacklisted = '1', BlacklistReason = 'mapper has requested blacklist' WHERE CreatorID = ?");
    $stmt->bind_param("s", $blacklistUserID);
    $stmt->execute();
