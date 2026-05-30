<?php
// Jessie
// Nikola: Sending out notifying email for all those involved, near bottom
/*
create_gm_direct.php creates a group meeting directly from selected group slots. 
It creates the group meeting and books all invitees to that meeting. 
*/ 

session_start();
require_once '../db.php';
require_once __DIR__ . '/send_direct_email.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/bookcalmethod.php');
    exit();
}

$group_slot_ids = $_POST['slot_id'] ?? '';;
$user_id = $_SESSION['user_id'];

if (empty($group_slot_ids)) {
    header("Location: ../pages/bookcalmethod.php?error=no_slot");
    exit();
}

$slot_ids = explode(',', $group_slot_ids);
$group_meetings = [];

foreach ($slot_ids as $slot_id) {
    $slot_id = trim($slot_id);

    // Get group slot info and invitees 
    $sql = "SELECT *  
            FROM GroupSlot
            WHERE gs_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $slot_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $group_slot_data = $result->fetch_assoc();

    if (!$group_slot_data) {
        continue;  
    }

    // Get all invitees for this group
    $invitees_sql = "SELECT i.invitee_id, u.email
                    FROM InviteRecipient i 
                    JOIN Users u ON i.invitee_id = u.user_id 
                    WHERE i.group_name = ?";
    $invitees_stmt = $conn->prepare($invitees_sql);
    $invitees_stmt->bind_param("s", $group_slot_data['group_name']);
    $invitees_stmt->execute();
    $invitees_result = $invitees_stmt->get_result();

    $invitees = [];
    $invitees_emails = [];

    while ($invitee = $invitees_result->fetch_assoc()) {
        $invitees[] = $invitee['invitee_id'];
        $invitees_emails[] = $invitee['email'];
    }

    // Create group meeting 
    $slot_sql = "INSERT INTO Slots (owner_id, chosen_date, start_time, end_time, status, slot_type, location) 
                VALUES (?, ?, ?, ?, 'public', 'group meeting', ?)";
    $slot_stmt = $conn->prepare($slot_sql);
    $slot_stmt->bind_param("issss", $user_id, $group_slot_data['chosen_date'], $group_slot_data['start_time'], $group_slot_data['end_time'], $group_slot_data['location']);

    if (!$slot_stmt->execute()) {
        header("Location: ../pages/bookcalmethod.php?error=db_failed");
        exit();
    }

    $groupslot_id = $conn->insert_id;

    // Book all invitees to group meeting 
    $booking_sql = "INSERT INTO Bookings (user_id, slot_id, booking_date, note) VALUES (?, ?, CURRENT_DATE(), ?)";
    $booking_stmt = $conn->prepare($booking_sql);
    $note = "Group meeting for: " . $group_slot_data['group_name'];

    foreach ($invitees as $invitee_id) {
        $booking_stmt->bind_param("iis", $invitee_id, $groupslot_id, $note);
        if (!$booking_stmt->execute()) {
            error_log("Failed to book slot for user: " . $invitee_id);
        }
    }

    // Update GroupInvite status to schedule (for dashbaord update)
    $update_sql = "UPDATE GroupInvite SET status = 'scheduled' WHERE group_name = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("s", $group_slot_data['group_name']);
    $update_stmt->execute();

    $group_meetings[] = [
        'group_name' => $group_slot_data['group_name'],
        'date' => $group_slot_data['chosen_date'],
        'start_time' => $group_slot_data['start_time'],
        'end_time' => $group_slot_data['end_time'],
        'location' => $group_slot_data['location']
    ];
}

// Prep email to be sent out for scheduled group meetings
$email_subject = "Group Meeting Scheduled";

$body = "The group meeting(s) have been booked..\n\n";
$body .= "<ol>";

foreach($group_meetings as $index => $meeting){
    $name = htmlspecialchars($meeting['group_name']);
    $date = date('l, F j, Y', strtotime($meeting['date']));
    $start = date('g:i A', strtotime($meeting['start_time']));
    $end = date('g:i A', strtotime($meeting['end_time']));
    $location = ($meeting['location'] ?: "TBD");


    $body .= 
    "
    <li> 
    Group Name: {$name} <br>
    Date: {$date} <br>
    Time: {$start} - {$end} <br>
    Location: {$location}
    </li>
    ";
}

$body .= "</ol>";

$unique_invitees_emails = array_unique($invitees_emails);
$sent_success = sendDirectEmail($_SESSION['email'], $email_subject, $body, $unique_invitees_emails);

if ($sent_success){
    header("Location: ../pages/bookcalmethod.php?success=1");
    exit();
}
else {
    echo "Could not send Email.";
    exit();
}

/* OLD-IMPLEMENTATION
$email_subject = "Group Meeting Scheduled";
$email_body = "The group meeting(s) have been scheduled.}.\n\n";

foreach ($group_meetings as $index => $meeting) {
    $email_body .= ($index + 1) . ". " . $meeting['group_name'] . "\n";
    $email_body .= "Date: " . date('l, F j, Y', strtotime($meeting['date'])) . "\n"; // Monday, April 28, 2026 format 
    $email_body .= "Time: " . date('g:i A', strtotime($meeting['start_time'])) . " - " . date('g:i A', strtotime($meeting['end_time'])) . "\n"; // 2:30 PM format
    $email_body .= "Location: " . ($meeting['location'] ?: "TBD") . "\n\n";
}

// URL encode the email content
$email_subject_encoded = rawurlencode($email_subject);
$email_body_encoded = rawurlencode($email_body);

// Create mailto link
$mailto = "mailto:" . $_SESSION['email'] . "?subject=" . $email_subject_encoded . "&body=" . $email_body_encoded;

// Redirect to mail client then back to success page
echo "<script>
    window.location.href = '$mailto';
    
    setTimeout(function() {
        window.location.href = '../pages/bookcalmethod.php?success=1';
    }, 1000);
</script>";
exit();
*/
?>