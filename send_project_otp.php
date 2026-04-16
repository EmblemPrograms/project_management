<?php
// send_project_otp.php

if (!isset($_SESSION['user_id']) || !isset($_SESSION['pending_submission'])) {
    header("Location: student_dashboard.php");
    exit;
}

// Generate 6-digit OTP
$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

// Save OTP to database - Removed 'created_at' column
$stmt = $pdo->prepare("INSERT INTO email_verification (user_id, otp, expires_at) 
                       VALUES (?, ?, ?)");
$stmt->execute([$_SESSION['user_id'], $otp, $expires_at]);

// ==================== SEND EMAIL WITH PHPMailer ====================
require_once 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'your_gmail@gmail.com';           // ← CHANGE THIS
    $mail->Password   = 'your_16_digit_app_password';     // ← CHANGE THIS (App Password)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // For debugging (set to 0 when everything works)
    $mail->SMTPDebug  = 2;
    $mail->Debugoutput = 'html';

    $mail->setFrom('your_gmail@gmail.com', 'NACOS FPE Chapter');
    $mail->addAddress($student['email']);

    $mail->isHTML(true);
    $mail->Subject = 'Your Project Upload OTP';
    $mail->Body    = "
        <h3>Project Submission OTP</h3>
        <p>Your One-Time Password is:</p>
        <h2 style='color:#28a745; letter-spacing:8px;'>{$otp}</h2>
        <p>This OTP expires in 30 minutes.</p>
    ";

    $mail->send();

} catch (Exception $e) {
    echo "<div style='background:red;color:white;padding:20px;margin:20px;font-family:monospace;'>";
    echo "<strong>Mailer Error:</strong> " . htmlspecialchars($mail->ErrorInfo) . "<br><br>";
    echo "Exception: " . htmlspecialchars($e->getMessage());
    echo "</div>";
    exit;
}
?>