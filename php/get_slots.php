<?php
// Jessie 
/*
get_slots.php retrieves all office hour slots for a given professor. 
This is for the frontend, officehours.php
*/ 

header('Content-Type: application/json');
require '../db.php';

$prof_id = $_GET['prof_id'] ?? null;

if (!$prof_id) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT slot_id, chosen_date, start_time, end_time, location
    FROM Slots
    WHERE owner_id = ?
      AND slot_type = 'office hour'
    ORDER BY chosen_date, start_time
");

$stmt->bind_param("i", $prof_id);
$stmt->execute();

$result = $stmt->get_result();

$slots = [];

while ($row = $result->fetch_assoc()) {
    $slots[] = $row;
}

echo json_encode($slots);
exit;