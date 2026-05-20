<?php
// approve_project.php
require_once '../includes/config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'department_admin') {
    header("Location: ../admin/");
    exit;
}

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("UPDATE projects SET status = 'approved' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}

header("Location: view_projects.php");
exit;
?>