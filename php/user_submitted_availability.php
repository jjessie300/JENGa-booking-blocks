<?php
// Nikola
// Marks a users availability for heatmap when dragging their mouse over grid
session_start();
require_once '../db.php';
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'), true);


$group_name = $data['group_name'] ?? null;

$user_id = $_SESSION['user_id'];
$slots = $data["slots"] ?? []; // Empty array of slots

if (!$group_name || !$user_id) {
    echo json_encode(["success" => false, "error" => "group_name or user_id missing"]);
    exit();
}

$slots_to_json = json_encode($slots);
$sql = "UPDATE InviteRecipient SET invitee_availability = ? WHERE group_name = ? AND invitee_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("sss", $slots_to_json, $group_name, $user_id);

if ($stmt->execute()){
    // Managed to update some row
    if ($stmt->affected_rows > 0){
        echo json_encode(["success" => true]);
    }
    // No row updated, by query did actually run
    else {
        echo json_encode(["success"=> true, "remark"=> "Query executed, but no rows where updated"]);
    }
}
else {
    echo json_encode(["success" => false, "error" => 'Failed to execute query']);

}
$stmt->close();

?>





