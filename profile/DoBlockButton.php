<?php
include '../base.php';

$user_id_from = $_POST['user_id_from'];
$user_id_to = $_POST['user_id_to'];

if ($user_id_from != $userId || !$loggedIn || $user_id_from == $user_id_to){
    die("NOOO");
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM `users` WHERE `UserID` = ?;");;
$stmt->bind_param("i", $user_id_to);
$stmt->execute();
if ($stmt->get_result()->fetch_row()[0] == 0)
    die("NOOO");
$stmt->close();

// Check if the user has already blocked
$stmt_check = $conn->prepare("SELECT * FROM user_relations WHERE UserIDFrom = ? AND UserIDTo = ? AND type = 2");
$stmt_check->bind_param("ii", $user_id_from, $user_id_to);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    // User already blocked, so time to unblock
    $stmt_remove = $conn->prepare("DELETE FROM user_relations WHERE UserIDFrom = ? AND UserIDTo = ? AND type = 2");
    $stmt_remove->bind_param("ii", $user_id_from, $user_id_to);
    $stmt_remove->execute();
    $stmt_remove->close();
} else{
    // Time to block.
    // Remove friend connections first. (soft-block)
    $stmt_delete_friends = $conn->prepare("DELETE FROM user_relations WHERE UserIDFrom = ? AND UserIDTo = ? AND type = 1");
    $stmt_delete_friends->bind_param("ii", $user_id_from, $user_id_to);
    $stmt_delete_friends->execute();
    $stmt_delete_friends->close();

    $stmt_delete_friends = $conn->prepare("DELETE FROM user_relations WHERE UserIDFrom = ? AND UserIDTo = ? AND type = 1");
    $stmt_delete_friends->bind_param("ii", $user_id_to, $user_id_from);
    $stmt_delete_friends->execute();
    $stmt_delete_friends->close();

    // And now add the blocked relation.
    $stmt_add = $conn->prepare("INSERT INTO user_relations (UserIDFrom, UserIDTo, type) VALUES (?, ?, 2)");
    $stmt_add->bind_param("ii", $user_id_from, $user_id_to);
    $stmt_add->execute();
    $stmt_add->close();
}
?>