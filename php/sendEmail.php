<?php
// Nikola
// Fires off email with completed info
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

// GeeksForGeeks: How to send an email using PHPMailer ?
// https://www.geeksforgeeks.org/php/how-to-send-an-email-using-phpmailer/
function sendEmail($email, $name, $subject, $body){
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
        $mail->addAddress($email, $name);

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


function sendVerificationEmail($email, $name, $token){
    // Generate link needed
    $verification_link = "https://winter2026-comp307-group40.cs.mcgill.ca/Project/BookingBlocks/php/verify.php?token=$token";

    $subject = 'Verify your McGill Email for Booking Blocks';
    $body    = 
    "
        <h2>Welcome to Booking Blocks, $name!</h2>
        <p>Please click the link below to verify your McGill email address and activate your account:</p>
        <p><a href='{$verification_link}'>{$verification_link}</a></p>
    ";
    return sendEmail($email, $name, $subject, $body);
}

function sendPasswordResetEmail($email, $name, $token) {

// Generate link needed
    $verification_link = "https://winter2026-comp307-group40.cs.mcgill.ca/Project/BookingBlocks/php/forgotpassword.php?token=$token";

    $subject = 'Reset your JENGa BookingBlocks Password';
    $body    = 
    "
        <h2>Hello, $name!</h2>
        <p>We've received a request to reset your password.</p>
        <p> If this wasn't done by you, simply ignore this email. </p>
        <p>Please click the link below which will then prompt you to reset your password.</p>
        <p><a href='{$verification_link}'>{$verification_link}</a></p>
    ";
    return sendEmail($email, $name, $subject, $body);


}

?>
