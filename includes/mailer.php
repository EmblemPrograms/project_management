<?php
// mailer.php - Shared outbound mail used by both the student and admin flows.
//
// student/send_otp.php keeps sendVerificationOTP() for registration. Anything
// needed by more than one area of the site belongs here instead, so admin/
// does not have to reach across into student/.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Emails a 6-digit password reset code.
 *
 * @param string $email Recipient address.
 * @param string $otp   The code, already zero-padded to 6 digits.
 * @param string $name  Recipient's display name.
 * @return bool False when the message could not be sent.
 */
function sendPasswordResetOTP($email, $otp, $name) {
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
        $mail->Subject = 'NACOS FPE CHAPTER - Password Reset Code';

        $body = "
            <h3>Dear " . htmlspecialchars($name) . ",</h3>
            <p>We received a request to reset your NACOS Project Register password.</p>
            <p>Your reset code is:</p>
            <h2 style='color:#28a745; letter-spacing: 8px;'>$otp</h2>
            <p>This code expires in 30 minutes and can only be used once.</p>
            <p><strong>If you did not request this, ignore this email.</strong>
               Your password stays as it is and no action is needed.</p>
            <br>
            <p>Best regards,<br>NACOS FPE CHAPTER Team</p>
        ";

        $mail->Body = $body;
        $mail->AltBody = "Your password reset code is: $otp (expires in 30 minutes). "
                       . "If you did not request this, ignore this email.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
