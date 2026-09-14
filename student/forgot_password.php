<?php
// forgot_password.php - Student password reset by emailed OTP.
// The flow itself lives in includes/password_reset_page.php, shared with admin.
declare(strict_types=1);

require_once '../includes/config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$reset_type    = 'student';
$reset_title   = 'Reset Password - School of Computing';
$reset_heading = 'Student';

require __DIR__ . '/../includes/password_reset_page.php';
