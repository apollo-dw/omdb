<?php
    include_once 'connection.php';
    include_once 'functions.php';
    include_once 'userConnect.php';

    session_start();
    $timeAtPageLoad = microtime(true);

    $mode = isset($_COOKIE["mode"]) ? $_COOKIE["mode"] : 0;

    if ($loggedIn && $user["banned"]) {
        die("ur banned");
    }

    // Should be database'd instead
    $maintenance = false;
	$ip = $_SERVER['HTTP_CLIENT_IP'] 
   ? $_SERVER['HTTP_CLIENT_IP'] 
   : ($_SERVER['HTTP_X_FORWARDED_FOR'] 
        ? $_SERVER['HTTP_X_FORWARDED_FOR'] 
        : $_SERVER['REMOTE_ADDR']);
    if ($maintenance && $ip != "82.10.155.251"){
        require("maintenance.php");
        die("");
    }
?>
