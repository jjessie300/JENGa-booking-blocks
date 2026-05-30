<?php
// Nikola
// Jessie: SQL HEAVILY based on Jessie's get_confirmed (removed order by, renaming, left join from prof)
/*
Queries all confirmed meetings for a person. Packages meetings into an .ics readable format
Downloads the file to the person's computer
*/
session_start();
require_once '../db.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id']; 
$user_type = $_SESSION['acc_type'];

// Obtain confirmed meetings for student or prof
if ($user_type == 'user') { 
    // Student
    $sql = " SELECT b.slot_id as id,
                s.chosen_date,
                s.start_time,
                s.end_time,
                s.slot_type,
                s.location,
                u.name,
                u.email,
                b.note,
                b.booking_date
            FROM Bookings b
            JOIN Slots s ON b.slot_id = s.slot_id
            JOIN Users u ON s.owner_id = u.user_id
            WHERE b.user_id = ?
            ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
} else {
    // Prof
    $sql = "SELECT s.slot_id as id,
                s.chosen_date,
                s.start_time,
                s.end_time,
                s.slot_type,
                s.location,
                u.name,
                u.email,
                b.note
            FROM Slots s
            JOIN Bookings b ON s.slot_id = b.slot_id
            JOIN Users u ON b.user_id = u.user_id
            WHERE s.owner_id = ?
                AND s.status = 'public' 
            ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<div>Nothing to export.</div>";

}

// Generate ICS entries for all records found

// https://en.wikipedia.org/wiki/ICalendar <-- Mention of needing \r\n after every line
// https://aonmeetings.com/how-to-create-an-ics-file/

else {
    // Requires these header to tell browser what to do with code below
    // Found on stackoverflow: 
    // https://stackoverflow.com/questions/12739247/how-to-generate-ics-file-using-php-for-a-given-date-range-and-time
    // answer from m4t1t0
    ////////
    header('Content-type:text/calendar');
    header('Content-Disposition: attachment; filename="JENGaAppointments.ics"');
    ////////

    //Open up calendar for formatting
    echo "BEGIN:VCALENDAR\r\n";
    echo "VERSION:2.0\r\n";
    echo "PRODID:-//JENGa//EN\r\n";


    // Populate calendar with events fetched from confirmed meetings
    while ($record = $result->fetch_assoc()){
        $uid = $record['id'] . "@JENGaBookingBlocks.com";
        $UNIX_start_time = strtotime($record['chosen_date'] . ' ' . $record['start_time']);
        $UNIX_end_time = strtotime($record['chosen_date'] . ' ' . $record['end_time']);
        $date_start = date('Ymd\THis', $UNIX_start_time);
        $date_end = date('Ymd\THis', $UNIX_end_time);
        $summary = ucfirst($record['slot_type']) . " with " . $record['name'];
        // GEMINI debug, have to add in str_replace in case person makes multi-line comment in note
        $description = str_replace(["\r", "\n"], ['', '\\n'], $record['note'] ?: 'No note attached');
        // GEMINI debug, have to add in str_replace in case user has multi-line comment in location
        $location = str_replace(["\r", "\n"], ['', '\\n'], $record['location'] ?: 'Unconfirmed');


        echo "BEGIN:VEVENT\r\n";
        echo "UID:{$uid}\r\n";
        echo "DTSTART:{$date_start}\r\n";
        echo "DTEND:{$date_end}\r\n";
        echo "SUMMARY:{$summary}\r\n";
        echo "DESCRIPTION:{$description}\r\n";
        echo "LOCATION:{$location}\r\n";
        echo "END:VEVENT\r\n";
    }

    // Close up calendar
    echo "END:VCALENDAR\r\n";
    exit();
}