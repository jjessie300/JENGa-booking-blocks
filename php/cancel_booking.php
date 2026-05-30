<?php
// Jessie 
/* 
cancel_booking.php handles cancelling a slot that the user has booked. 
This is triggered when user clicks on cancel button in their dashboard. 
*/ 

session_start();
header('Content-Type: application/json');
require_once '../db.php';

$input = json_decode(file_get_contents('php://input'), true);
$slot_id = $input['slot_id'];
$user_id = $_SESSION['user_id'];

// Check slot type 
$type_sql = "SELECT slot_type FROM Slots WHERE slot_id = ?";
$type_stmt = $conn->prepare($type_sql);
$type_stmt->bind_param("i", $slot_id);
$type_stmt->execute();
$result = $type_stmt->get_result();
$row = $result->fetch_assoc();
$slot_type = $row['slot_type'];

// Delete booking 
$sql = "DELETE FROM Bookings WHERE slot_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $slot_id, $user_id);

if ($slot_type == 'request') {
    // For requests, delete booking and mark slot as cancelled
    if ($stmt->execute()) {
        $update_sql = "UPDATE Slots SET status = 'cancelled' WHERE slot_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("i", $slot_id);

        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Slot status updated succesfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $update_stmt->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    } 

} else {
    // For offic hours/group meetings, delete booking (only owner can cancel those)
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Slot status updated succesfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    }
}  

?>