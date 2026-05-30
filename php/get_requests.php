<?php
// Jessie, Emily (html)
/* 
get_requests.php retrieves and displays all meeting requests and group meeting invites 
for both professors and students in their dashboard. 
For group meeting invites, the inviter has view invitee availabilities button and the invitees 
have a submit availability button. 
*/ 

session_start(); 
require_once '../db.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// MEETING REQUESTS 
$sql = "SELECT r.request_id,
        r.chosen_date,
        r.requested_start,
        r.requested_end,
        r.note,
        r.status,
        u.name as professor_name,
        u.email as professor_email
    FROM Requests r
    JOIN Users u ON r.user_id = u.user_id -- User who sent request 
    WHERE r.owner_id = ? -- Prof receiving requests made by students 
        AND r.status = 'pending'
        AND r.chosen_date >= ? 
    ORDER BY r.chosen_date ASC, r.requested_start ASC"; 

$stmt = $conn->prepare($sql); 
$stmt->bind_param("is", $user_id, $today); 
$stmt->execute(); 
$result = $stmt->get_result();

$has_requests = false;

if ($result->num_rows > 0) {
    $has_requests = true;

    while ($row = $result->fetch_assoc()) {
        // Format data for display 
        $request_id = $row['request_id'];
        $date = date('M d, Y', strtotime($row['chosen_date'])); // Apr 25, 2026 format 
        $start_time = date('g:i A', strtotime($row['requested_start'])); // 10:00 AM format 
        $end_time = date('g:i A', strtotime($row['requested_end']));
        $professor_name = htmlspecialchars($row['professor_name']);
        $professor_email = htmlspecialchars($row['professor_email']);
        $note = htmlspecialchars($row['note'] ?: 'No notes');
        
        echo "
            <div class='appointment-item' data-id='{$request_id}'>
                <div class='field'>Request</div>
                <div class='field'>{$date}</div>
                <div class='short-field'>{$start_time}</div>
                <div class='short-field'>{$end_time}</div>
                <div class='field'><a href='mailto:{$professor_email}'>{$professor_name} &#9993;</a></div>
                <div class='field'>TBD</div>
                <div class='field'>Notes: 
                    <div class='dropdown-wrapper'>
                        <button class='dropbtn' onclick='showMore(this)'>Read More</button>
                        <div class='more-info' id='moreInfo-{$request_id}'>
                            <p>{$note}</p>
                        </div>
                    </div>
                </div>
                <div class='buttons'>
                    <div><button class='accept' onclick='acceptAppointment({$request_id})'>Accept &#9745;</button></div>
                    <div><button class='decline' onclick='declineAppointment({$request_id})'>Remove &#9746;</button></div>
                </div>
            </div>";
    }
}

// GROUP MEETING INVITES 
$group_sql = "SELECT 
        g.group_name,
        g.start_date,
        g.end_date,
        g.type as method_type, 
        u.user_id as inviter_id, 
        u.name as inviter_name,
        u.email as inviter_email
    FROM InviteRecipient i
    JOIN GroupInvite g ON i.group_name = g.group_name
    JOIN Users u ON g.inviter_id = u.user_id -- User who made the invite 
    WHERE i.invitee_id = ? 
        AND i.invitee_id != g.inviter_id
        AND g.status = 'pending'";

$stmt = $conn->prepare($group_sql); 
$stmt->bind_param("i", $user_id); 
$stmt->execute(); 
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $has_requests = true;

    while ($row = $result->fetch_assoc()) {
        $group_name = htmlspecialchars($row['group_name']);
        $inviter_name = htmlspecialchars($row['inviter_name']);
        $inviter_email = htmlspecialchars($row['inviter_email']);
        $inviter_id = $row['inviter_id']; 
        $method_type = htmlspecialchars($row['method_type']); 

        // INVITEE VIEW 
        if ($method_type == 'heatmap') {
            $start_date = date('M d, Y', strtotime($row['start_date']));
            $end_date = date('M d, Y', strtotime($row['end_date']));

            echo "
                <div class='appointment-item' data-group='{$group_name}'>
                    <div class='field'>Group Invite</div>
                    <div class='field'>{$group_name}</div>
                    <div class='field'><a href='mailto:{$inviter_email}'>{$inviter_name} &#9993;</a></div> 
                    <div class='field'>From: {$start_date}</div>
                    <div class='field'>To: {$end_date}</div>
                    <div class='buttons'>
                        <div><button class='submit' onclick='submitAvail(\"{$method_type}\", \"{$group_name}\")'>Submit Availabilities</button></div>
                    </div>
                </div>";

        } else {
            echo "
                <div class='appointment-item' data-group='{$group_name}'>
                    <div class='field'>Group Invite</div>
                    <div class='field'>{$group_name}</div>
                    <div class='field'><a href='mailto:{$inviter_email}'>{$inviter_name} &#9993;</a></div> 
                    <div class='buttons'>
                        <div><button class='submit' onclick='submitAvail(\"{$method_type}\", \"{$group_name}\")'>Submit Availabilities</button></div>
                    </div>
                </div>";
        }
    }
}

$inviter_sql = "SELECT 
        g.group_name,
        g.start_date,
        g.end_date, 
        g.type as method_type
    FROM GroupInvite g
    WHERE g.inviter_id = ? 
        AND g.status = 'pending'"; 

$stmt = $conn->prepare($inviter_sql); 
$stmt->bind_param("i", $user_id); 
$stmt->execute(); 
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $has_requests = true;

    while ($row = $result->fetch_assoc()) {
        $group_name = htmlspecialchars($row['group_name']);
        $method_type = htmlspecialchars($row['method_type']); 
        
        // INVITER VIEW 
        if ($method_type == 'heatmap') {
            $start_date = date('M d, Y', strtotime($row['start_date']));
            $end_date = date('M d, Y', strtotime($row['end_date']));

            echo "
                <div class='appointment-item' data-group='{$group_name}'>
                    <div class='field'>Group Invite</div>
                    <div class='field'>{$group_name}</div>
                    <div class='field'>From: {$start_date}</div>
                    <div class='field'>To: {$end_date}</div>
                    <div class='buttons'>
                        <div><button class='submit' onclick='submitAvail(\"{$method_type}\", \"{$group_name}\")'>View Availabilities</button></div>
                    </div>
                </div>";
        } else {
            echo "
                <div class='appointment-item' data-group='{$group_name}'>
                    <div class='field'>Group Invite</div>
                    <div class='field'>{$group_name}</div>
                    <div class='buttons'>
                        <div><button class='submit' onclick='submitAvail(\"{$method_type}\", \"{$group_name}\")'>View Availabilities</button></div>
                    </div>
                </div>";
        }
        
    }
}

if (!$has_requests) {
    echo "<div class='no-requests'>No pending requests found</div>";
}

?>

