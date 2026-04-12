<?php
// verify_otp_submission.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Security: Only logged-in students with pending submission
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['pending_submission'])) {
    header("Location: student_dashboard.php");
    exit;
}

// Fetch student
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp']);

    // Verify OTP
    $stmt = $pdo->prepare("SELECT * FROM email_verification 
                           WHERE user_id = ? AND otp = ? AND expires_at > NOW() AND used = 0");
    $stmt->execute([$_SESSION['user_id'], $entered_otp]);
    $record = $stmt->fetch();

    if ($record) {
        // Mark OTP as used
        $pdo->prepare("UPDATE email_verification SET used = 1 WHERE id = ?")
            ->execute([$record['id']]);

        // Now allow file upload - redirect to final upload page
        $_SESSION['otp_verified'] = true;
        header("Location: final_project_upload.php");
        exit;
    } else {
        $error = "Invalid or expired OTP. Please check your email and try again.";
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
                <strong><?php echo htmlspecialchars($student['email']); ?></strong>
            </p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
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

            <small class="text-muted d-block mt-4">
                OTP expires in 30 minutes. Didn't receive it? Check your spam folder.
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