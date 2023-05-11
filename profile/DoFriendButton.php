<?php
include '../base.php';

$user_id_from = $_POST['user_id_from'];
$user_id_to = $_POST['user_id_to'];

if ($user_id_from != $userId || !$loggedIn || $user_id_from == $user_id_to){
    die("NOOO");
}

$otherUser = $conn->query("SELECT * FROM `users` WHERE `UserID`='{$user_id_to}';")->fetch_assoc();
if ($otherUser == NULL)
    die("NOOO");

// Check if the users are already friends
$stmt_check = $conn->prepare("SELECT * FROM user_relations WHERE UserIDFrom = ? AND UserIDTo = ? AND type = 1");
$stmt_check->bind_param("ii", $user_id_from, $user_id_to);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    // The users are already friends, so remove the friend connection
    $stmt_remove = $conn->prepare("DELETE FROM user_relations WHERE UserIDFrom = ? AND UserIDTo = ? AND type = 1");
    $stmt_remove->bind_param("ii", $user_id_from, $user_id_to);
    $stmt_remove->execute();

    echo 'removed';
    $stmt_remove->close();
} else {
    // The users are not friends, so add the friend connection
    $stmt_add = $conn->prepare("INSERT INTO user_relations (UserIDFrom, UserIDTo, type) VALUES (?, ?, 1)");
    $stmt_add->bind_param("ii", $user_id_from, $user_id_to);
    $stmt_add->execute();

    // Check if the relationship is mutual
    $stmt_mutual = $conn->prepare("SELECT * FROM user_relations WHERE UserIDFrom = ? AND UserIDTo = ? AND type = 1");
    $stmt_mutual->bind_param("ii", $user_id_to, $user_id_from);
    $stmt_mutual->execute();
    $mutual_result = $stmt_mutual->get_result();

    if ($mutual_result->num_rows > 0) {
        echo 'mutual';
    } else {
        echo 'added';
    }

    $stmt_mutual->close();
    $stmt_add->close();
}

$stmt_check->close();