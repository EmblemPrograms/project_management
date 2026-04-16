<?php
// approve_project.php
require_once 'config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'department_admin') {
    header("Location: grand_admin_login.php");
    exit;
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE projects SET status = 'approved' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}

header("Location: dept_admin_view_projects.php");
exit;
?>