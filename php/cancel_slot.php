<?php
// Jessie 
/* 
cancel_slot.php handles cancelling an available or booked slot.
This is triggered when a professor clicks on remove slot button in their dashboard. 
*/

session_start();
header('Content-Type: application/json');
require_once '../db.php';

$input = json_decode(file_get_contents('php://input'), true);
$slot_id = $input['slot_id'];

// Get emails of all users who booked the slot 
$email_sql = "SELECT u.email FROM Bookings b 
               JOIN Users u ON b.user_id = u.user_id 
               WHERE b.slot_id = ?";
$email_stmt = $conn->prepare($email_sql);
$email_stmt->bind_param("i", $slot_id);
$email_stmt->execute();
$result = $email_stmt->get_result();

$emails = [];
while ($email = $result->fetch_assoc()) {
    $emails[] = $email['email'];
}

// Delete the slot -> Bookings will be deleted automatically due to ON DELETE CASCADE
$sql = "DELETE FROM Slots WHERE slot_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $slot_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'emails' => $emails]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
}
?>