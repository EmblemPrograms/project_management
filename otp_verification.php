<?php
// otp_verification.php

require_once 'config.php';
require_once 'send_otp.php';

if (!isset($_SESSION['reg_student_id']) || !isset($_SESSION['reg_email'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

// ====================== RESEND OTP ======================
if (isset($_POST['resend_otp'])) {
    $new_otp = rand(100000, 999999);
    $_SESSION['reg_otp'] = $new_otp;

    $sent = sendVerificationOTP($_SESSION['reg_email'], $new_otp, $_SESSION['reg_name']);

    if ($sent) {
        $success = "✅ A new OTP has been sent to your email.";
    } else {
        $error = "❌ Failed to resend OTP. Please try again.";
    }
}

// ====================== VERIFY OTP ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['resend_otp'])) {
    $entered_otp = trim($_POST['otp']);

    if ($entered_otp == $_SESSION['reg_otp']) {
        
        // FIXED: Removed 'status' column since it doesn't exist
        $stmt = $pdo->prepare("UPDATE students SET approved = 1 WHERE id = ?");
        $stmt->execute([$_SESSION['reg_student_id']]);

        // Set user session for login
        $_SESSION['user_id'] = $_SESSION['reg_student_id'];
        $_SESSION['role'] = 'student';
        $_SESSION['name'] = $_SESSION['reg_name'];
        $_SESSION['email'] = $_SESSION['reg_email'];

        // Clear registration session data
        unset($_SESSION['reg_otp'], $_SESSION['reg_student_id'], $_SESSION['reg_name'], $_SESSION['reg_email']);

        $_SESSION['success'] = "🎉 Registration completed successfully! Welcome to NACOS FPE Chapter.";

        header("Location: student_dashboard.php");
        exit;

    } else {
        $error = "❌ Invalid OTP. Please check and try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification - NACOS FPE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); 
            min-height: 100vh; 
        }
        .card { max-width: 500px; margin: 100px auto; }
    </style>
</head>
<body>
<div class="container">
    <div class="card shadow">
        <div class="card-header bg-success text-white text-center py-3">
            <h4>Verify Your Email</h4>
        </div>
        <div class="card-body p-5">

            <p class="text-center mb-4">
                A 6-digit OTP was sent to:<br>
                <strong><?= htmlspecialchars($_SESSION['reg_email']) ?></strong>
            </p>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <input type="text" name="otp" id="otp" 
                           class="form-control form-control-lg text-center fs-1 fw-bold" 
                           maxlength="6" placeholder="000000" 
                           autocomplete="off" autofocus required>
                </div>
                <button type="submit" class="btn btn-success btn-lg w-100 mb-3">Verify OTP</button>
            </form>

            <form method="POST">
                <button type="submit" name="resend_otp" value="1" 
                        class="btn btn-outline-secondary w-100">
                    🔄 Resend OTP
                </button>
            </form>

            <small class="text-muted d-block text-center mt-4">
                OTP expires in 30 minutes. Check spam folder if not received.
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