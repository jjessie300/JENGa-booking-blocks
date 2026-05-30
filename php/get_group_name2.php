<?php
// Jessie 
/* 
get_group_name.php retrieves all group names created by the logged-in user and 
the groups the user is apart of.
It is used to populate a datalist dropdown in the frontend, creategroupmeeting.php
*/

session_start();
header('Content-Type: application/json');

require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all groups created by user and groups user is apart of
$sql = "SELECT g.group_name 
        FROM InviteRecipient i
        JOIN GroupInvite g ON i.group_name = g.group_name
        WHERE i.invitee_id = ? 
            AND g.status = 'pending'
        UNION
        SELECT group_name 
        FROM GroupInvite 
        WHERE inviter_id = ? 
            AND status = 'pending'
        
        ORDER BY group_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$group_names = [];

while ($group_name = $result->fetch_assoc()) {
    $group_names[] = $group_name;
}

echo json_encode($group_names);
?>