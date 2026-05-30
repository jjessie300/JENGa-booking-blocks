<?php
// Jessie 
/* 
create_invite.php handles create group meeting request using the heat map method. 
It creates the group, and invites all members to the group. 
*/ 

session_start();
require_once '../db.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/createavailable.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$group_name = $_POST['group'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$members = $_POST['emails']; 

if (!$group_name || !$start_date || !$end_date) {
    header("Location: ../pages/createavailable.php?error=empty");
    exit();
}

// Check that start and end date is a future date 
$today = date('Y-m-d');
if ($start_date < $today || $end_date < $start_date) {
    header("Location: ../pages/createavailable.php?error=date");
    exit();
}

// Convert comma-separated string of emails to array
$email_array = array_map('trim', explode(',', $members));
$email_array = array_filter($email_array); 

if (empty($email_array)) {
    header("Location: ../pages/createavailable.php?error=members");
    exit();
}

// Check if group name already exists 
$check_sql = "SELECT group_name FROM GroupInvite WHERE group_name = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $group_name);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    header("Location: ../pages/createavailable.php?error=duplicate_group");
    exit();
}

$sql = "INSERT INTO GroupInvite (group_name, inviter_id, start_date, end_date, status, type) VALUES (?, ?, ?, ?, 'pending', 'heatmap')"; 
$stmt = $conn->prepare($sql); 
$stmt->bind_param("siss", $group_name, $user_id, $start_date, $end_date);

if ($stmt->execute()) {
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

    $to_emails = implode(',', $email_array);
    $subject = "Group Meeting Invitation: " . $group_name;
    $body = "You have been invited to a group meeting: " . $group_name . "\n";
    $body .= "Please submit your availability for a date between " . $start_date . " and " . $end_date . ".\n\n";
    
    $mailto = "mailto:" . $to_emails . "?subject=" . rawurlencode($subject) . "&body=" . rawurlencode($body);
    
    echo "<script>
        window.location.href = '{$mailto}';
        setTimeout(function() {
            window.location.href = '../pages/createavailable.php?success=1';
        }, 1000);
    </script>";
}

?> 