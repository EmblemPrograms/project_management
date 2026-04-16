<?php
// verify_otp_submission.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'send_otp.php';

// Security: Only logged-in students with pending submission
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['pending_submission'])) {
    header("Location: student_dashboard.php");
    exit;
}

// Fetch student record
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: student_dashboard.php");
    exit;
}

$error = '';
$success = '';

/**
 * Generate and send OTP using your existing PHPMailer function.
 * Returns true on success, false on failure.
 */
function generate_and_send_otp($pdo, $user_id, $email, $name) {
    // Cryptographically secure 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Store OTP with 30-minute expiry
    $stmt = $pdo->prepare("INSERT INTO email_verification 
                          (user_id, otp, expires_at, used) 
                          VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), 0)");
    $stmt->execute([$user_id, $otp]);

    // Dispatch via your PHPMailer function
    return sendVerificationOTP($email, $otp, $name);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend_otp'])) {
        // Resend new OTP
        $name = $student['name'] ?? $student['fullname'] ?? 'Valued Student';
        $send_result = generate_and_send_otp($pdo, $_SESSION['user_id'], $student['email'], $name);
        
        if ($send_result) {
            $success = "A new 6-digit OTP has been sent to your registered email address.";
        } else {
            $error = "Failed to send OTP. Please verify SMTP configuration in config.php or check error logs.";
        }
    } else {
        // OTP verification
        $entered_otp = trim($_POST['otp']);

        $stmt = $pdo->prepare("SELECT * FROM email_verification 
                               WHERE user_id = ? AND otp = ? AND expires_at > NOW() AND used = 0");
        $stmt->execute([$_SESSION['user_id'], $entered_otp]);
        $record = $stmt->fetch();

        if ($record) {
            // Mark OTP as used
            $pdo->prepare("UPDATE email_verification SET used = 1 WHERE id = ?")
                 ->execute([$record['id']]);

            $_SESSION['otp_verified'] = true;
            header("Location: final_project_upload.php");
            exit;
        } else {
            $error = "Invalid or expired OTP. Please check your email (including spam folder) and try again.";
        }
    }
} else {
    // Initial page load: automatically generate and send OTP
    $name = $student['name'] ?? $student['fullname'] ?? 'Valued Student';
    $send_result = generate_and_send_otp($pdo, $_SESSION['user_id'], $student['email'], $name);
    
    if ($send_result) {
        $success = "A 6-digit OTP has been sent to your registered email address. Please check your inbox (and spam folder).";
    } else {
        $error = "Failed to send the OTP email. Please verify SMTP credentials in config.php and ensure the Gmail App Password is correct.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification - NACOS FPE CHAPTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        .verify-card { max-width: 500px; margin: 80px auto; }
    </style>
</head>
<body>
<div class="container">
    <div class="verify-card card shadow">
        <div class="card-header bg-success text-white text-center">
            <h4>One-Time Password Verification</h4>
        </div>
        <div class="card-body p-5 text-center">
            <p class="mb-4">
                A 6-digit OTP has been sent to:<br>
                <strong><?= safe_output($student['email']) ?></strong>
            </p>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= safe_output($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= safe_output($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <input type="text" name="otp" id="otp" 
                           class="form-control form-control-lg text-center fs-1 fw-bold" 
                           maxlength="6" placeholder="000000" 
                           autocomplete="off" autofocus required>
                </div>
                <button type="submit" class="btn btn-success btn-lg w-100">Verify OTP</button>
            </form>

            <div class="mt-3">
                <form method="POST">
                    <button type="submit" name="resend_otp" class="btn btn-outline-secondary btn-sm w-100">
                        Resend OTP
                    </button>
                </form>
            </div>

            <small class="text-muted d-block mt-4">
                OTP expires in 30 minutes. Didn't receive it? Check your spam folder or use the resend option above.
            </small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('otp').focus();
</script>
</body>
</html>