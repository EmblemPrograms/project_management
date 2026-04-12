<?php
require_once 'config.php';

if (!isset($_SESSION['verify_user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp']);

    // Improved query with debugging output
    $stmt = $pdo->prepare("SELECT id, otp, expires_at, TIMESTAMPDIFF(SECOND, NOW(), expires_at) as seconds_left 
                           FROM email_verification 
                           WHERE user_id = ? AND otp = ? AND used = 0");
    $stmt->execute([$_SESSION['verify_user_id'], $entered_otp]);
    $record = $stmt->fetch();

    if ($record) {
        $seconds_left = $record['seconds_left'];

        if ($seconds_left > 0) {
            // OTP is valid → Approve the user
            $pdo->prepare("UPDATE email_verification SET used = 1 WHERE id = ?")->execute([$record['id']]);
            $pdo->prepare("UPDATE users SET approved = 1 WHERE id = ?")->execute([$_SESSION['verify_user_id']]);

            $name = $_SESSION['verify_name'];
            unset($_SESSION['verify_user_id'], $_SESSION['verify_email'], $_SESSION['verify_name']);

            $success = "Email verified successfully!<br>Welcome, " . safe_output($name) . ".<br>Your account is now active.";
        } else {
            $error = "OTP has expired. Please register again.";
        }
    } else {
        $error = "Invalid OTP. Please check the email and try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification - NACOS FPE CHAPTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); 
            min-height: 100vh; 
            display: flex;
            align-items: center;
        }
        .verify-card {
            max-width: 480px;
            margin: auto;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="verify-card card shadow">
        <div class="card-body text-center p-5">
            <h3 class="text-success mb-4">Verify Your Email Address</h3>
            <p class="mb-4">A 6-digit OTP has been sent to:<br>
               <strong><?php echo safe_output($_SESSION['verify_email'] ?? 'your registered email'); ?></strong>
            </p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <hr>
                    <a href="login.php" class="btn btn-success btn-lg">Proceed to Login</a>
                </div>
            <?php else: ?>
                <form method="post">
                    <div class="mb-4">
                        <input type="text" name="otp" id="otp" 
                               class="form-control form-control-lg text-center fs-1 fw-bold" 
                               maxlength="6" placeholder="000000" 
                               autocomplete="off" autofocus required>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100">Verify OTP</button>
                </form>
                <small class="text-muted d-block mt-3">
                    OTP expires in 30 minutes. Check your spam folder if not received.
                </small>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-focus and select on load
    document.getElementById('otp').focus();
</script>
</body>
</html>