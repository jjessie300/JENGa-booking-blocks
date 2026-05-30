<?php
// Jessie
/*
get_group_slots.php retreives all group meeting slots for a given group. 
This is for the frontend, bookcalmethod.php. 
*/ 

header('Content-Type: application/json');
require '../db.php';

$group_name = $_GET['group_name'] ?? null;

if (empty($group_name)) {
    echo json_encode(["error" => "No group name"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT g.gs_id, 
           g.chosen_date, 
           g.start_time, 
           g.end_time,
           g.location,
           COUNT(s.user_id) as selected_count, 
           (SELECT COUNT(DISTINCT invitee_id) FROM InviteRecipient WHERE group_name = ?) as total_invitees
    FROM GroupSlot g
    LEFT JOIN SelectedGroupSlot s ON g.gs_id = s.gs_id
    WHERE g.group_name = ?
    GROUP BY g.gs_id, g.chosen_date, g.start_time, g.end_time, g.location
    ORDER BY g.chosen_date, g.start_time
");

$stmt->bind_param("ss", $group_name, $group_name);
$stmt->execute();

$result = $stmt->get_result();

$slots = [];

while ($slot = $result->fetch_assoc()) {
    $slots[] = $slot;
}

echo json_encode($slots);
exit;
?>

