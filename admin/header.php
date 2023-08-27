<?php
    require_once ('base.php');
?>

<html>
    <head>
        <title>Admin Panel | OMDB</title>
        <link rel="apple-touch-icon" sizes="180x180" href="../apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="../favicon-16x16.png">
        <link rel="stylesheet" type="text/css" href="/style.css?v=15" />
        <style>
            body {
                background-color: black;
                color: white;
                font-family: "MS UI Gothic", sans-serif;
            }

            .nav-bar {
                height: 100%;
                width: 12em;
                position: fixed;
                z-index: 1;
                top: 0;
                left: 0;
                background-color: #111;
                overflow-x: hidden;
                text-align: right;
            }

            .nav-bar .nav-link {
                text-decoration: none;
                font-size: 2em;
                color: #818181;
                display: block;
                padding-top: 0.5em;
                padding-bottom: 0.5em;
                box-sizing: border-box;
            }

            .nav-bar .nav-link:hover {
                background-color: white;
            }

            .content {
                padding-left: 16em;
            }
        </style>
    </head>
    <body>
        <div class="nav-bar">
            <a class="nav-link" href="index.php">home</a>
            <a class="nav-link" href="#">actions</a>
            <a class="nav-link" href="blacklist.php">blacklist</a>
            <a class="nav-link" href="logs.php">logs</a>
            <a class="nav-link" href="#">ban</a>

            <br><br>
            logged in as <?php echo $userName; ?>
            <a href="../index.php">return to omdb</a>
        </div>

        <div class="content">