<?php
// Jessie, Emily (html) 
/*
get_confirmed.php retrieves and displays appointments for both professors and students in their dashboard. 
For professors, it shows all the slots they've created (booked or still available) and 
any appointments they have booked. 
For students, it shows all appointments they have booked. 
 */

session_start();
require_once '../db.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id']; 
$user_type = $_SESSION['acc_type']; // owner or user 
$type_filter = $_GET['type']; // all, office-hours, request, or group-meeting 


if ($user_type == 'user') {
    // STUDENT VIEW -> show their bookings 
    $sql = "SELECT b.slot_id as id,
                s.chosen_date,
                s.start_time,
                s.end_time,
                s.slot_type,
                s.location,
                u.name as prof_name,
                u.email as prof_email,
                b.note,
                b.booking_date
            FROM Bookings b
            JOIN Slots s ON b.slot_id = s.slot_id
            JOIN Users u ON s.owner_id = u.user_id
            WHERE b.user_id = ?
            ORDER BY s.chosen_date ASC, s.start_time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
} else {
    // PROFESSOR VIEW -> show slots they've created (booked and not booked) and the slots they've booked 
    $sql = "(SELECT s.slot_id as id,
                s.chosen_date,
                s.start_time,
                s.end_time,
                s.slot_type,
                s.location,
                u.name as user_name,
                u.email as user_email,
                b.note, 
                'slot_created' as item_type
            FROM Slots s
            LEFT JOIN Bookings b ON s.slot_id = b.slot_id
            LEFT JOIN Users u ON b.user_id = u.user_id
            WHERE s.owner_id = ?
                AND s.status = 'public' 

            UNION ALL 

            SELECT b.slot_id as id,
                s.chosen_date,
                s.start_time,
                s.end_time,
                s.slot_type,
                s.location,
                u.name as user_name,
                u.email as user_email,
                b.note,
                'booking_made' as item_type
            FROM Bookings b
            JOIN Slots s ON b.slot_id = s.slot_id
            JOIN Users u ON s.owner_id = u.user_id
            WHERE b.user_id = ?)

            ORDER BY chosen_date ASC, start_time ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    if ($user_type == 'user') {
        echo "<div class='no-appointments'>No confirmed appointments</div>";
    } else {
        echo "<div class='no-appointments'>You haven't created any slots yet.</div>";
    }
} else {
    while ($row = $result->fetch_assoc()) {
        // Filter based on type 
        if ($type_filter != 'all') {
            $slot_type = strtolower($row['slot_type']);
            $filter_type = strtolower($type_filter);
            
            // Convert filter values to match slot_type in db 
            if ($filter_type == 'office-hours') $filter_type = 'office hour';
            if ($filter_type == 'group-meeting') $filter_type = 'group meeting';
            
            // Skip appointment if it doesn't match filter 
            if ($slot_type != $filter_type) {
                continue; 
            }
        }
        
        // Format data for display 
        $type_display = ucfirst($row['slot_type']);
        $date = date('M d, Y', strtotime($row['chosen_date'])); // Apr 25, 2026
        $start_time = date('g:i A', strtotime($row['start_time'])); // 10:00 AM
        $end_time = date('g:i A', strtotime($row['end_time']));
        $location = htmlspecialchars($row['location'] ?: 'TBD'); // returns location or TBD 
        $appointment_id = $row['id'];
        $note = ''; 
        $item_type = $row['item_type'];
        
        if ($user_type == 'owner') {
            // PROFESSOR VIEW -> show slot with info of student who booked it and slots they've booked 
            if ($item_type == 'booking_made') {
                $professor_name = htmlspecialchars($row['user_name']);
                $professor_email = htmlspecialchars($row['user_email']);
                
                $note = htmlspecialchars($row['note'] ?: 'No notes');
                
                echo "
                    <div class='appointment-item' data-id='{$appointment_id}'>
                        <div class='field'>{$type_display}</div>
                        <div class='field'>{$date}</div>
                        <div class='short-field'>{$start_time}</div>
                        <div class='short-field'>{$end_time}</div>
                        <div class='field'><a href='mailto:{$professor_email}'>{$professor_name} &#9993;</a></div>
                        <div class='field'>{$location}</div>
                        <div class='field'>Notes: 
                            <div class='dropdown-wrapper'>
                                <button class='dropbtn' onclick='showMore(this)'>Read More</button>
                                <div class='more-info' id='moreInfo-{$appointment_id}'>
                                    <p>{$note}</p>
                                </div>
                            </div>
                        </div>
                        <div class='buttons'>
                            <div><button class='decline' onclick='cancelBooking({$appointment_id})'>Cancel &#9746;</button></div>
                        </div>
                    </div>";
            } else {
                if ($row['user_name']) {
                    $student_name = htmlspecialchars($row['user_name']);
                    $student_email = htmlspecialchars($row['user_email']);
                    $note = htmlspecialchars($row['note'] ?: 'No notes');
                    $booking_info = "<div style='color: green;'>Booked by: <a href='mailto:{$student_email}'>{$student_name} &#9993;</a></div>";
                } else {
                    $booking_info = "<div style='color: orange;'>Available for booking</div>";
                    $note = "No notes"; 
                }
                
                echo "
                    <div class='appointment-item' data-id='{$appointment_id}'>
                        <div class='field'>{$type_display}</div>
                        <div class='field'>{$date}</div>
                        <div class='short-field'>{$start_time}</div>
                        <div class='short-field'>{$end_time}</div>
                        <div class='field'>{$booking_info}</div>
                        <div class='field'>{$location}</div>
                        <div class='field'>Notes: 
                            <div class='dropdown-wrapper'>
                                <button class='dropbtn' onclick='showMore(this)'>Read More</button>
                                <div class='more-info' id='moreInfo-{$appointment_id}'>
                                    <p>{$note}</p>
                                </div>
                            </div>
                        </div>
                        <div class='buttons'>
                            <div><button class='edit' id='editBtn' onclick='editAppointment({$appointment_id})'>Edit &#9998</button></div>
                            <div><button class='decline' onclick='cancelSlot({$appointment_id})'>Remove Slot &#9746;</button></div>
                        </div>
                    </div>";
            }
            
        } else {
            // STUDENT VIEW -> show prof info of booked slot 
            $professor_name = htmlspecialchars($row['prof_name']);
            $professor_email = htmlspecialchars($row['prof_email']);
            $note = htmlspecialchars($row['note'] ?: 'No notes');
            
            echo "
                <div class='appointment-item' data-id='{$appointment_id}'>
                    <div class='field'>{$type_display}</div>
                    <div class='field'>{$date}</div>
                    <div class='short-field'>{$start_time}</div>
                    <div class='short-field'>{$end_time}</div>
                    <div class='field'><a href='mailto:{$professor_email}'>{$professor_name} &#9993;</a></div>
                    <div class='field'>{$location}</div>
                    <div class='field'>Notes: 
                        <div class='dropdown-wrapper'>
                            <button class='dropbtn' onclick='showMore(this)'>Read More</button>
                            <div class='more-info' id='moreInfo-{$appointment_id}'>
                                <p>{$note}</p>
                            </div>
                        </div>
                    </div>
                    <div class='buttons'>
                        <div><button class='decline' onclick='cancelBooking({$appointment_id})'>Cancel &#9746;</button></div>
                    </div>
                </div>";
        }
    }
}

?>