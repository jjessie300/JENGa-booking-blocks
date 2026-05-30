<?php
// Jessie 
/*
accept_request.php handles when a professor accepts a request made by another user in their dashboard. 
It takes the information from the request, marks the request as accepted to update the dashboard, creates a slot, 
and books the person who made the request to the slot. 
*/

session_start();
header('Content-Type: application/json');
require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$request_id = $data['request_id'];
$user_id = $_SESSION['user_id'];

// Get request details
$sql = "SELECT * FROM Requests WHERE request_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'error' => 'Request not found']);
    exit();
}

$request = $result->fetch_assoc();
$student_id = $request['user_id'];

// Update request status 
$sql = "UPDATE Requests SET status = 'accepted' WHERE request_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();

// Create slot
$sql = "INSERT INTO Slots (owner_id, chosen_date, start_time, end_time, status, slot_type, location) 
        VALUES (?, ?, ?, ?, 'public', 'request', 'TBD')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("isss", $user_id, $request['chosen_date'], $request['requested_start'], $request['requested_end']);
$stmt->execute();
$slot_id = $conn->insert_id;

if (!$slot_id) {
    echo json_encode(['success' => false, 'error' => 'Failed to create slot']);
    exit();
}

// Create booking for student
$sql = "INSERT INTO Bookings (user_id, slot_id, booking_date, note) 
        VALUES (?, ?, CURRENT_DATE(), ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $student_id, $slot_id, $request['note']);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Failed to create booking']);
    exit();
}

echo json_encode(['success' => true]);

?>