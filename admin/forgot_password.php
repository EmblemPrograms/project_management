<?php
// forgot_password.php - Admin password reset by emailed OTP.
// Covers both grand_admin and department_admin: they share the admins table.
// The flow itself lives in includes/password_reset_page.php.
declare(strict_types=1);

require_once '../includes/config.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: " . (($_SESSION['admin_role'] ?? '') === 'grand_admin'
        ? "dashboard.php"
        : "../department/dashboard.php"));
    exit;
}

$reset_type    = 'admin';
$reset_title   = 'Admin Password Reset - NACOS FPE';
$reset_heading = 'Admin';

require __DIR__ . '/../includes/password_reset_page.php';
