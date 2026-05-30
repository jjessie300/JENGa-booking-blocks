<?php
// Jessie 
// Nikola: Sending out notifying email for all those gathered in successful slots
/* 
create_groupslot.php handles create group meeting request using the calendar method. 
It creates the group, invites the members to the group, and the available group slots to choose from. 
*/ 

session_start();
require_once '../db.php';
require_once 'send_group_email.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/calendarmethod.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$group_name = $_POST['group'] ?? '';
$members = $_POST['emails']; 
$slots_json = $_POST['slots_data'] ?? '';

$slots = json_decode($slots_json, true);

$skipped_slots = [];
$success_slots = [];

// Check if user input a group name
if (empty($group_name)) {
    header("Location: ../pages/calendarmethod.php?error=group");
    exit();
}

// Convert comma-separated string of emails to array
$email_array = array_map('trim', explode(',', $members));
$email_array = array_filter($email_array); 

if (empty($email_array)) {
    header("Location: ../pages/calendarmethod.php?error=members");
    exit();
}

// Check if group name already exist
$group_sql = "SELECT group_name FROM GroupInvite WHERE group_name = ?";
$stmt = $conn->prepare($group_sql);
$stmt->bind_param("s", $group_name);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    header("Location: ../pages/calendarmethod.php?error=duplicate_group");
    exit();
}   

// Create group invite
$insert_sql = "INSERT INTO GroupInvite (group_name, inviter_id, start_date, end_date, status, type) 
               VALUES (?, ?, NULL, NULL, 'pending', 'calendar')";
$stmt = $conn->prepare($insert_sql);
$stmt->bind_param("si", $group_name, $user_id);

if (!$stmt->execute()) {
    echo "<script>
    alert('Please select valid start and end times.');
    window.location.href = '../pages/requestblock.php';
    </script>";
    exit();
}

// Invite members to group 
$sql = "INSERT INTO InviteRecipient (invitee_id, group_name, invitee_availability) VALUES (?, ?, NULL)";
$stmt = $conn->prepare($sql);

foreach ($email_array as $email) {
    $user_sql = "SELECT user_id FROM Users WHERE email = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("s", $email);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_row = $user_result->fetch_assoc(); 
    $invitee_id = $user_row['user_id'];

    $stmt->bind_param("is", $invitee_id, $group_name);
    $stmt->execute();
} 

// Create group slots 
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
        $sql = "SELECT COUNT(*) AS count FROM GroupSlot 
                WHERE group_name = ? 
                  AND chosen_date = ? 
                  AND start_time < ? 
                  AND end_time > ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $group_name, $current_date, $to, $from);
        $stmt->execute();
        $result = $stmt->get_result();
        $overlap_slots = $result->fetch_assoc();
        
        // Create slot if there's no overlapping slots 
        if ($overlap_slots['count'] == 0) {
            $sql = "INSERT INTO GroupSlot (chosen_date, start_time, end_time, group_name, location) 
                    VALUES (?, ?, ?, ?, ?)"; 
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $current_date, $from, $to, $group_name, $location);
            $stmt->execute();
            $success_slots[] = [
                'date' => $current_date,
                'from' => $from,
                'to' => $to,
                'location' => $location,
                'recurring' => $recurring
            ];

        } else {
            $skipped_slots[] = [
                'date' => $current_date,
                'from' => $from,
                'to' => $to,
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

// Notify all those involved of a new group and details regarding possible slots
$creator_query = "SELECT name, email FROM Users WHERE user_id = ?";
$statement = $conn->prepare($creator_query);
$statement->bind_param("i", $user_id);
$statement->execute();
$creator_result = $statement->get_result();

if ($creator_record = $creator_result->fetch_assoc()){
    $creatorEmail = $creator_record['email'];
    $creatorName = $creator_record['name'];
}

if (!empty($creatorEmail)){
    sendGroupEmail($creatorEmail, $creatorName, $group_name, $email_array, $success_slots);
}

// For calendarmethod.php error msg popup 
if (!empty($skipped_slots)) {
    $encoded = urlencode(json_encode($skipped_slots));
    header("Location: ../pages/calendarmethod.php?skipped=" . $encoded);
    exit();
} else {
    header("Location: ../pages/calendarmethod.php?success=1");
    exit();
}

?>

