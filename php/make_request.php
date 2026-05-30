<?php
// Jessie 
// Nikola (Prep+Send Email)
/* 
make_request.php creates a meeting request to a professor. 
*/ 

session_start();
require_once '../db.php';
require_once __DIR__ . '/send_direct_email.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/requestblock.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$owner_id = $_POST['owner_id'] ?? ''; 
$selected_date = $_POST['selected_date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$note = $_POST['note'] ?? '';

// Check all required fields present 
if (!$owner_id || !$selected_date || !$start_time || !$end_time) {
    header('Location: ../pages/requestblock.php?error=' . urlencode('Please fill in all required fields.'));
    exit();
}

$stmt = $conn->prepare("SELECT email, acc_type FROM Users WHERE user_id=?");
$stmt->bind_param("s", $owner_id);
$stmt->execute(); 
$result = $stmt->get_result();
$owner = $result->fetch_assoc();

// Check if user selected a prof 
if ($owner['acc_type'] !== 'owner') {
    header('Location: ../pages/requestblock.php?error=' . urlencode('Please select a professor.'));
    exit();
}

// Check that selected date is a future date 
$today = date('Y-m-d');
if ($selected_date < $today) {
    header('Location: ../pages/requestblock.php?error=' . urlencode('Please select a future date.'));
    exit();
}

// Check that start and end time make sense 
$start = DateTime::createFromFormat('H:i', $start_time);
$end = DateTime::createFromFormat('H:i', $end_time);

if ($start > $end) {
    header('Location: ../pages/requestblock.php?error=' . urlencode('Please select a professor.'));
    exit();
}

// Create request
$stmt = $conn->prepare("INSERT INTO Requests (user_id, owner_id, status, chosen_date, requested_start, requested_end, note) VALUES (?, ?, 'pending', ?, ?, ?, ?)");
$stmt->bind_param("iissss", $user_id, $owner_id, $selected_date, $start_time, $end_time, $note);

// Prep Email to fire off
if ($stmt->execute()) {

    $owner_email = $owner['email'];
    $user_name = $_SESSION['name'];

    $subject = "New Meeting Request from {$user_name}";

    $body = 
    "
        <p>You have a new meeting request from {$user_name} on {$selected_date} from $start_time to {$end_time}</p>
        <p>Please log into JENGa Booking Blocks for further details</p>
    ";


    $sent_success = sendDirectEmail($owner_email, $subject, $body);

    if ($sent_success){
        header("Location: ../pages/requestblock.php?success=1");
        exit();
    }

    else {
        echo "Could not send Email.";
        exit();
    }

}

/* OLD-IMPLEMENTATION
if ($stmt->execute()) {
    $owner_email = $owner['email']; 
    $user_name = $_SESSION['name']; 
    $formatted_start = date('g:i A', strtotime($start_time)); // 1:00 PM format 
    $formatted_end = date('g:i A', strtotime($end_time)); 

    $mailto = "mailto:$owner_email?subject=New Meeting Request&body=You have a new meeting request from $user_name on $selected_date from $formatted_start to $formatted_end."; 
    
    // Redirect back after 1 second
    echo "<script>
        window.location.href = '$mailto';
        setTimeout(function() {
            window.location.href = '../pages/requestblock.php?success=1';
        }, 1000);
    </script>"; 
} 
    */

else {
    echo "Error submitting request: " . $stmt->error;
}

?>

