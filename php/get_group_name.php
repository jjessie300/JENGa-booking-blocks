<?php
// Jessie 
/* 
get_group_name.php retrieves all group names created by the logged-in user.
It is used to populate a datalist dropdown in the frontend, bookcalmethod.php
*/ 
session_start();
header('Content-Type: application/json');

require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all groups created by this user
$sql = "SELECT group_name 
        FROM GroupInvite 
        WHERE inviter_id = ?
        ORDER BY group_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$group_names = [];

while ($group_name = $result->fetch_assoc()) {
    $group_names[] = $group_name;
}

echo json_encode($group_names);
?>