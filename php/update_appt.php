<?php
// Jessie 
/* 
update_appt.php updates the location of a slot.
This is done by a professor in their dashboard. 
*/ 

session_start();
header('Content-Type: application/json');
require_once '../db.php';


$input = json_decode(file_get_contents('php://input'), true);

$appointment_id = $input['appointment_id'];
$location = $input['location'];

// Update the slot location in the database
$query = "UPDATE Slots SET location = ? WHERE slot_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $location, $appointment_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Location updated successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
}

?>