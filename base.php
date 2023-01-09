<?php
    include_once 'connection.php';
    include_once 'functions.php';
    include_once 'userConnect.php';

    if ($loggedIn && $user["banned"]) {
        die(".");
    }
?>