<?php
// Jessie 
/* 
decline_request.php handles when a professor declines a request made by another user in their dashboard. 
It marks the request as declined to update the dashboard. 
*/ 

session_start();
header('Content-Type: application/json');
require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
$request_id = $data['request_id'];

// Update the request status
$sql = "UPDATE Requests SET status = 'declined' WHERE request_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Could not decline request.']);
}

?>