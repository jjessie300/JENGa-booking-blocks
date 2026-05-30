<?php
// Jessie 
/* 
check_email.php is called from createavailable.php for the email tags. 
It verifies that the email belongs to a registered account. 
*/ 

require_once '../db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';

if (empty($email)) {
    echo json_encode(['exists' => false]);
    exit();
}

$sql = "SELECT user_id FROM Users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$exists = $result->num_rows > 0;

echo json_encode(['exists' => $exists]);
?>