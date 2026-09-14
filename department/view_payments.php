<?php
// view_payments.php - Submission list for ONE department (department admin).
// The page itself lives in includes/submission_list_page.php, shared with the
// grand admin view.

require_once '../includes/config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'department_admin') {
    header("Location: ../admin/");
    exit;
}

// The department comes from the session, set at login — never from the URL.
$dept_id = (int) ($_SESSION['department_id'] ?? 0);
if ($dept_id <= 0) {
    header("Location: ../admin/");
    exit;
}

$stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
$stmt->execute([$dept_id]);
$dept_name = $stmt->fetchColumn();

$scope_department_id = $dept_id;
$back_url            = 'dashboard.php';
$scope_label         = $dept_name ?: 'Your department';

require __DIR__ . '/../includes/submission_list_page.php';
