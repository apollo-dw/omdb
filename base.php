<?php
    include_once 'connection.php';
    include_once 'functions.php';
    include_once 'userConnect.php';

    if ($loggedIn && $user["banned"]) {
        die(".");
    }

    // Should be database'd instead
    $maintenance = false;
    if ($maintenance && $userName != "Apo11o"){
        require("maintenance.php");
        die("");
    }
?>