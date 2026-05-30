<?php
// Nikola 
// Obtains a group's meeting date interval
session_start();
require_once "../db.php";

header("Content-Type: application/json");

$group_name = $_GET["group_name"] ?? null;

if (!$group_name){
    echo json_encode(["error" => "Group name missing"]);
    exit();
}

$sql = "SELECT start_date, end_date FROM GroupInvite WHERE group_name = ?";
$stmt = $conn->prepare($sql);

$stmt->bind_param("s", $group_name);
$stmt->execute();
$result = $stmt->get_result();

/// GEMINI, how to generate and format dates for our interval ///

if ($row = $result->fetch_assoc()) {
    $start_date_str = $row['start_date'];
    $end_date_str = $row['end_date'];

    $dates_array = [];

    // Make sure neither date is NULL before calculating
    if ($start_date_str && $end_date_str) {
        $start = new DateTime($start_date_str);
        $end = new DateTime($end_date_str);
        
        // DatePeriod stops before the end date, so we add 1 day to make it inclusive
        $end->modify('+1 day');

        // Create a 1-day interval
        $interval = new DateInterval('P1D');
        $daterange = new DatePeriod($start, $interval, $end);

        // Loop through every day in the range and format it as an object
        foreach($daterange as $date){
            $dates_array[] = [
                "date" => $date->format("Y-m-d")
            ];
        }
    }
    
    // Output the generated array
    echo json_encode($dates_array);
    
} else {
    // If the group doesn't exist or hasn't been created yet
    echo json_encode(["error" => "Group not found"]);
}
///
$stmt->close();

?>