<?php
// Jessie
/*
select_group_slot.php handles selection of one or many group meeting slots by members of group. 
*/ 

session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$slot_ids_str = $_POST['slot_id'] ?? '';

if (empty($slot_ids_str)) {
    header("Location: ../pages/bookcalmethod.php?error=no_slot");
    exit();
}

$slot_ids = explode(',', $slot_ids_str);
$skipped_slots = [];

foreach ($slot_ids as $slot_id) {
    $slot_id = trim($slot_id);

    // Check if user already selected this slot
    $check_sql = "SELECT * FROM SelectedGroupSlot WHERE user_id = ? AND gs_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $slot_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $skipped_slots[] = $slot_id;
        continue;
    }

    $sql = "INSERT INTO SelectedGroupSlot (user_id, gs_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $slot_id);

    if (!$stmt->execute()) {
        $skipped_slots[] = $slot_id;
        continue; 
    }
}

if (empty($skipped_slots)) {
    header("Location: ../pages/bookcalmethod.php?success=added");
} else {
    $encoded = urlencode(json_encode($skipped_slots));
    header("Location: ../pages/bookcalmethod.php?error=already_selected");
}
exit();
?>