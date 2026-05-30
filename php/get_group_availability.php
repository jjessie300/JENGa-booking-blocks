 <?php
 // Nikola 
 // Called upon when the find availability page for heatmap is loaded by a user
session_start();
require_once '../db.php';
header("Content-Type: application/json");


$group_name = $_GET["group_name"] ?? null;

if (!$group_name){
    echo json_encode(["error" => "Group name is missing"]);
    exit();
}

$sql = "SELECT invitee_id, invitee_availability FROM InviteRecipient WHERE group_name = ? AND invitee_availability IS NOT NULL";
$stmt = $conn->prepare($sql);

$stmt->bind_param("s", $group_name);
$stmt->execute();
$result = $stmt->get_result();

// Populate array with availabilities from all members of the group
$all_availabilities = [];

// Loop through all users returned for that group
while ($record = $result->fetch_assoc()){
    $user_id = $record["invitee_id"];

    // Convert to a php array
    $user_available_slots = json_decode($record["invitee_availability"], true);

    $user_availability_mapping = [];

    foreach($user_available_slots as $slot_id) {
        // Format expected in groupmeetings.php
        $key = $slot_id["date"] . "_" . $slot_id["slot"];
        $user_availability_mapping[$key] = true;
    }

    // Bundle it up nicely for a given user
    $all_availabilities[$user_id] = $user_availability_mapping;
}

    //Send a group's availability back to be displayed in groupmeetings.php
    echo json_encode($all_availabilities);

    $stmt->close();

 ?>