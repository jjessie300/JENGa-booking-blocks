<?php
// Jessie
// Nikola: Sending out notifying email for all those involved, near bottom
/* 
create_gm.php creates one or many group meeting slots (recurring meetings), and books all 
invitees to the created slots.
*/ 

session_start();
require_once '../db.php';
require_once __DIR__ . '/send_group_email2.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/creategroupmeeting.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];
$group_name = $_POST['group_name'] ?? '';
$selected_date = $_POST['selected_date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$location = $_POST['location'] ?? '';
$recurring = $_POST['recurring'] ?? 'One-time';
$frequency = isset($_POST['frequency']) ? (int)$_POST['frequency'] : 1;

if(!$start_time || !$end_time){
    header("Location: ../pages/creategroupmeeting.php?error=notime");
    exit();
}

// Validate required fields
if (!$group_name || !$selected_date || !$start_time || !$end_time) {
    header("Location: ../pages/creategroupmeeting.php?error=empty");
    exit();
}

// Check that date is a future date 
$today = date('Y-m-d');
if ($selected_date < $today) {
    header("Location: ../pages/creategroupmeeting.php?error=date");
    exit();
}

// Check that start and end time make sense 
$start = DateTime::createFromFormat('H:i', $start_time);
$end = DateTime::createFromFormat('H:i', $end_time);

if ($start >= $end) {
    header("Location: ../pages/creategroupmeeting.php?error=time");
    exit();
}

$invitees_sql = "SELECT ir.invitee_id, u.email
                 FROM InviteRecipient ir 
                 JOIN Users u ON ir.invitee_id = u.user_id 
                 WHERE ir.group_name = ?";
$invitees_stmt = $conn->prepare($invitees_sql);
$invitees_stmt->bind_param("s", $group_name);
$invitees_stmt->execute();
$invitees_result = $invitees_stmt->get_result();

$invitees = [];
$invitee_emails = [];

while ($invitee = $invitees_result->fetch_assoc()) {
    $invitees[] = $invitee['invitee_id'];
    $invitee_emails[] = $invitee['email'];
}

$current_date = $selected_date;
$created_slots = [];

// Loop for recurring frequency
for ($i = 0; $i < $frequency; $i++) {
    // Create group meeting slot
    $slot_sql = "INSERT INTO Slots (owner_id, chosen_date, start_time, end_time, status, slot_type, location) 
                VALUES (?, ?, ?, ?, 'public', 'group meeting', ?)";
    $slot_stmt = $conn->prepare($slot_sql);
    $slot_stmt->bind_param("issss", $user_id, $current_date, $start_time, $end_time, $location);

    if (!$slot_stmt->execute()) {
        throw new Exception("Failed to create slot");
    }

    $slot_id = $conn->insert_id;
    
    // Book all group members for this slot
    $booking_sql = "INSERT INTO Bookings (user_id, slot_id, booking_date, note) VALUES (?, ?, CURRENT_DATE(), ?)";
    $booking_stmt = $conn->prepare($booking_sql);
    $note = "Group meeting for: " . $group_name;

    foreach ($invitees as $invitee_id) {
        $booking_stmt->bind_param("iis", $invitee_id, $slot_id, $note);
        if (!$booking_stmt->execute()) {
            throw new Exception("Failed to book slot for user: " . $invitee_id);
        }
    }
    
    // Store created slot info for email
    $created_slots[] = [
        'date' => $current_date,
        'start' => $start_time,
        'end' => $end_time,
        'location' => $location
    ];
    
    // Update current date based on recurring type
    switch($recurring) {
        case 'One-time': 
            break; 
        case 'Daily': 
            $current_date = date('Y-m-d', strtotime($current_date . ' + 1 day'));
            break; 
        case 'Weekly': 
            $current_date = date('Y-m-d', strtotime($current_date . ' + 1 week'));
            break; 
    }
}

// Generate email if slots were created
if (empty($created_slots)) {
    header("Location: ../pages/creategroupmeeting.php?error=no_slots");
    exit();      
}

// Update group invite status for dashboard update 
$update_sql = "UPDATE GroupInvite SET status = 'scheduled' WHERE group_name = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("s", $group_name);
$update_stmt->execute();


///// Send auto email confirming the meeting
$email_subject = "Group Meeting Scheduled: {$group_name}";

$body = "A group meeting has been scheduled for {$group_name}.\n\n";
$body .= "<ol>";

foreach($created_slots as $index => $slot){
    $date = date('l, F j, Y', strtotime($slot['date']));
    $start = date('g:i A', strtotime($slot['start']));
    $end = date('g:i A', strtotime($slot['end']));
    $location = ($slot['location'] ?: "TBD");

    $body .= 
    "
    <li> 
    Group Name: {$group_name} <br>
    Date: {$date} <br>
    Time: {$start} - {$end} <br>
    Location: {$location}
    </li>
    ";
}
$body .= "</ol>";

// People to add in CC
$email_list = array_unique($invitee_emails);

// Fire off email
$sent_success = sendConfirmGroupEmail($user_email, $email_subject, $body, $email_list);

if ($sent_success){
    header("Location: ../pages/creategroupmeeting.php?success=1");
    exit();
}
else {
    echo "Could not send Email.";
    exit();
}


/* OLD IMPLEMENTATION
// Email notif
$email_subject = "Group Meeting Scheduled: " . $group_name;
$email_body = "A group meeting has been scheduled for {$group_name}.\n\n";

foreach ($created_slots as $index => $slot) {
    $email_body .= "Date: " . date('l, F j, Y', strtotime($slot['date'])) . "\n";
    $email_body .= "Time: " . date('g:i A', strtotime($slot['start'])) . " - " . date('g:i A', strtotime($slot['end'])) . "\n";
    $email_body .= "Location: " . ($slot['location'] ?: "TBD") . "\n\n";
}

// URL encode the email content
$email_subject_encoded = rawurlencode($email_subject);
$email_body_encoded = rawurlencode($email_body);

// Create comma-separated list of emails for mailto
$email_list = implode(',', array_unique($invitee_emails));

// Create mailto link
$mailto = "mailto:" . $email_list . "?subject=" . $email_subject_encoded . "&body=" . $email_body_encoded;

echo "<script>
    window.location.href = '$mailto';
    
    setTimeout(function() {
        window.location.href = '../pages/creategroupmeeting.php?success=1';
    }, 1000);
</script>";
exit();
*/
?>