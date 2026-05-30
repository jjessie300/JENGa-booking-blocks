<?php
// Jessie 
/* 
get_professors.php retrieves all the professors. 
This is for the datalist dropdown in requestblock.php and officehours.php
*/ 

header('Content-Type: application/json');

require '../db.php';

$profs = $conn->query("SELECT user_id, name, email FROM Users WHERE acc_type='owner' ORDER BY name");

if (!$profs) {
    echo json_encode(["error" => $conn->error]);
    exit;
}

$owners = [];

while ($owner = $profs->fetch_assoc()) {
    $owners[] = $owner;
}

echo json_encode($owners);
?>