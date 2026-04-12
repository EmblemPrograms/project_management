<?php
// send_otp.php - Email OTP Sender using PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';   // If using Composer

function sendVerificationOTP($email, $otp, $name) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = 'NACOS FPE CHAPTER - Email Verification OTP';

        $body = "
            <h3>Dear " . htmlspecialchars($name) . ",</h3>
            <p>Your verification OTP for NACOS Project Register is:</p>
            <h2 style='color:#28a745; letter-spacing: 8px;'>$otp</h2>
            <p>This OTP will expire in 30 minutes.</p>
            <p>If you did not register, please ignore this email.</p>
            <br>
            <p>Best regards,<br>NACOS FPE CHAPTER Team</p>
        ";

        $mail->Body = $body;
        $mail->AltBody = "Your OTP is: $otp (expires in 30 minutes)";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>