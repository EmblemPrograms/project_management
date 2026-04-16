<?php
// reject_project.php
require_once 'config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'department_admin') {
    header("Location: grand_admin_login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = $_POST['project_id'];
    $remark     = trim($_POST['remark'] ?? '');

    $stmt = $pdo->prepare("UPDATE projects SET status = 'rejected', remark = ? WHERE id = ?");
    $stmt->execute([$remark, $project_id]);
}

header("Location: dept_admin_view_projects.php");
exit;
?>