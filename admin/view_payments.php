<?php
// view_payments.php - Submission list across every department (grand admin).
// The page itself lives in includes/submission_list_page.php, shared with the
// department admin view.

require_once '../includes/config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'grand_admin') {
    header("Location: index.php");
    exit;
}

$scope_department_id = null;              // null = all departments
$back_url            = 'dashboard.php';
$scope_label         = 'All departments';

require __DIR__ . '/../includes/submission_list_page.php';
