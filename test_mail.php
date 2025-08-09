<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';     // Your SMTP server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'alertpoint.mrc@gmail.com'; // Your Gmail address
    $mail->Password   = 'ekoeiclfidgaaxko';   // Use an App Password for Gmail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    //Recipients
    $mail->setFrom('alertpoint.mrc@gmail.com', 'AlertPoint Test');
    $mail->addAddress('bryantiversonmelliza03@gmail.com'); // Add recipient

    //Content
    $mail->isHTML(true);
    $mail->Subject = 'PHPMailer Test';
    $mail->Body    = 'Hello! PHPMailer is working on your Mac.';

    $mail->send();
    echo 'Message has been sent!';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
