<?php
// Jessie 
/* 
create_oh.php creates one or many office hours (can be recurring). 
Only professors can create office hours. 
*/ 

session_start();
require_once '../db.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/createofficehour.php');
    exit();
}

$owner_id = $_SESSION['user_id'];
$slots_json = $_POST['slots_data'] ?? '';

$slots = json_decode($slots_json, true);

$skipped_slots = [];

if (empty($slots)) {
    header("Location: ../pages/createofficehour.php?error=empty");
    exit();
}

foreach ($slots as $slot) {
    $date = $slot['date'];
    $from = $slot['from'];
    $to = $slot['to'];
    $location = $slot['location'];
    $recurring = $slot['recurring'];
    $frequency = $slot['frequency'] ?? 1; 

    $current_date = $date; 
    
    for ($i = 0; $i < $frequency; $i++) {
        // Check if current slot overlaps with any existing slots 
        // Overlap: existing start time before new slot end time 
        //          and existing end time after new start time 
        $sql = "SELECT COUNT(*) AS count FROM Slots 
                WHERE owner_id = ? 
                  AND chosen_date = ? 
                  AND start_time < ? 
                  AND end_time > ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $owner_id, $current_date, $to, $from);
        $stmt->execute();
        $result = $stmt->get_result();
        $overlap_slots = $result->fetch_assoc();
        
        // Create slot if there's no overlapping slots 
        if ($overlap_slots['count'] == 0) {
            $sql = "INSERT INTO Slots (owner_id, chosen_date, start_time, end_time, status, slot_type, location) 
                    VALUES (?, ?, ?, ?, 'public', 'office hour', ?)"; 
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issss", $owner_id, $current_date, $from, $to, $location);
            $stmt->execute();

        } else {
            $skipped_slots[] = [
                'date' => $current_date,
                'from' => $from,
                'to' => $to,
                'location' => $location,
            ];
        }

        switch($recurring) {
            case 'One-time': 
                break; 
            case 'Daily': 
                $current_date = date('Y-m-d', strtotime($current_date . ' + 1 days'));
                break; 
            case 'Weekly': 
                $current_date = date('Y-m-d', strtotime($current_date . ' + 1 weeks'));
                break; 
            case 'Monthly': 
                $current_date = date('Y-m-d', strtotime($current_date . ' + 1 months'));
                break; 
        }

    }
}

/*
foreach ($skipped_slots as $slot) {
    echo "console.log('Date: " . $slot['date'] . " | From: " . $slot['from'] . " | To: " . $slot['to'] . " | Location: " . $slot['location'] . "');";
}
*/

// For createofficehours.php error msg popup 
if (!empty($skipped_slots)) {
    $encoded = urlencode(json_encode($skipped_slots));
    header("Location: ../pages/createofficehour.php?skipped=" . $encoded);
} else {
    header("Location: ../pages/createofficehour.php?success=1");
}
exit();

?>

