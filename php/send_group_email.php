<?php
// Nikola
// Sends out email when group slots have been created (not confirmed)
// Notifies participants they have been added to a group
// and which slots are available to pick from
require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Had a warning appear since other files already had sessions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require_once __DIR__ . '/../db.php';

function sendGroupEmail($creatorEmail, $groupCreator, $groupName, $email_array, $slots) {

    $members = htmlspecialchars(implode(', ', $email_array));

    $slots_to_HTML = "<ul>";
    foreach($slots as $slot) {
        $date = htmlspecialchars($slot['date']);
        $from = htmlspecialchars($slot['from']);
        $to = htmlspecialchars($slot['to']);
        $location = htmlspecialchars($slot['location']);
        $recurring = htmlspecialchars($slot['recurring']);

        $slots_to_HTML .= "<li><strong>Date:</strong> $date at <strong>Time:</strong> $from to $to in <strong>Location:</strong> $location.";

    }
    $slots_to_HTML .= "</ul>";

    $subject = "Group {$groupName} has been created!";
    $body = 
    "
        <h2> Group {$groupName} has been created by {$groupCreator} </h2>
        <p> Invitees: {$members}</p>
        <p>Scheduled blocks are the following: </p>
        $slots_to_HTML <br>
        <p> Remember to log into JENGa Booking Blocks for further details.</p>
    ";



    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();                                            
        $mail->Host       = 'smtp.gmail.com';                     
        $mail->SMTPAuth   = true;                                   
        $mail->Username   = 'JENGaBookingBlocks@gmail.com';
        $mail->Password   = 'qwdx xufi nfyp evvy'; //Gmail App password, NOT account password
        $mail->SMTPSecure = 'tls';            
        $mail->Port       = 587;

        // Sender and receiver
        $mail->setFrom('JENGaBookingBlocks@gmail.com', 'JENGa Booking Blocks'); //First is sender email, second is "title" name
        $mail->addAddress($creatorEmail, $groupCreator);

        // CC participants
        foreach ($email_array as $participant_email) {
            $mail->addCC(trim($participant_email)); // In case we have whitespaces
        }


        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();
    

    }
    catch (Exception $e) {
        // Message failed to send
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }

}