<?php
// Nikola
// Sends out email when a group meeting has been created
// Notifies participants
// Needed a 2nd as they vary in implementation
// and did not want to play too much around it due to time constraint
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

function sendConfirmGroupEmail($creatorEmail, $subject, $body, $email_array) {


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
        $mail->addAddress($creatorEmail);

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