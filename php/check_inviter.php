<?php
// Jessie 
/* 
check_inviter.php checks whether the logged-in user is the inviter/creator of a group meeting.  
This is called by bookcalmethod.php and is used to determine what button should be shown. 
*/ 

session_start();
header('Content-Type: application/json');
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id']; 
$group_name = $_GET['group_name'];

// Check if user is the inviter of group meeting 
$sql = "SELECT inviter_id FROM GroupInvite WHERE group_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $group_name);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $isInviter = ($row['inviter_id'] == $user_id);
    echo json_encode(['isInviter' => $isInviter]);
} else {
    echo json_encode(['isInviter' => false]);
}
?>