<?php
// Jessie 
/*
book_oh.php handles booking office hour slots and create email draft to the owner of the slot. 
*/ 

session_start();
require_once '../db.php'; 

// Only logged-in users can book office hours 
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/officehours.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name']; 
$slot_id = $_POST['slot_id'] ?? '';
$note = $_POST['note'] ?? ''; 

if (!$slot_id) {
    header('Location: ../pages/officehours.php?error=missingfields');
    exit();
}

// get owner email and slot info 
$stmt = $conn->prepare("SELECT u.email, s.start_time, s.end_time, s.chosen_date, s.location
    FROM Slots s
    JOIN Users u ON s.owner_id = u.user_id
    WHERE s.slot_id = ?"
); 
$stmt->bind_param("i", $slot_id); 
$stmt->execute(); 
$result = $stmt->get_result();
$slot_info = $result ? $result->fetch_assoc() : null; 

if (!$slot_info) { 
    die("No slot found.");
}

$owner_email = $slot_info['email'];
$start_time = $slot_info['start_time'];
$end_time = $slot_info['end_time'];
$chosen_date = $slot_info['chosen_date'];
$location = $slot_info['location'];

// Check if user already booked slot 
$stmt = $conn->prepare("SELECT * FROM Bookings WHERE slot_id = ? AND user_id = ?");
$stmt->bind_param("ii", $slot_id, $user_id);
$stmt->execute();
$existing_booking = $stmt->get_result();

if ($existing_booking->num_rows > 0) {
    header('Location: ../pages/officehours.php?error=alreadybooked');
    exit();
}

// Create booking 
$stmt = $conn->prepare("INSERT INTO Bookings (user_id, slot_id, booking_date, note) VALUES (?, ?, CURRENT_DATE(), ?)");
$stmt->bind_param("iis", $user_id, $slot_id, $note);

if ($stmt->execute()) {
    $formatted_start = date('g:i A', strtotime($start_time)); // 1:00 PM format 
    $formatted_end = date('g:i A', strtotime($end_time)); 
    
    $mailto = "mailto:$owner_email?subject=Office Hour Booking Confirmation&body=$user_name booked your $chosen_date from $formatted_start to $formatted_end office hour slot at $location."; 
    // Redirect back after 1 second
    echo "<script>
    window.location.href = '$mailto';
    
    setTimeout(function() {
        window.location.href = '../pages/officehours.php?success=1';
    }, 1000);
    </script>"; 
}
else {
    echo "Error submitting request: " . $stmt->error;
}

?>

