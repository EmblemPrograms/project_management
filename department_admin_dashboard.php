<?php
// department_admin_dashboard.php

require_once 'config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'department_admin') {
    header("Location: grand_admin_login.php");
    exit;
}

// Get department info and student count
$stmt = $pdo->prepare("
    SELECT d.name, COUNT(s.id) AS total_students 
    FROM departments d
    LEFT JOIN students s ON s.department_id = d.id
    WHERE d.id = ?
    GROUP BY d.id
");
$stmt->execute([$_SESSION['department_id']]);
$dept = $stmt->fetch();

$department_name = $dept['name'] ?? 'Unknown Department';
$total_students = $dept['total_students'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .stat-card { background: linear-gradient(135deg,rgb(3, 141, 88),rgb(35, 177, 122)); color: white; }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h4><?= htmlspecialchars($department_name) ?> - Admin Dashboard</h4>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
        <div class="card-body">

            <!-- Student Count -->
            <div class="row justify-content-center mb-5">
                <div class="col-md-5">
                    <div class="stat-card card text-center p-4 shadow">
                        <h6>Total Students in Department</h6>
                        <h1 class="display-1 fw-bold"><?= $total_students ?></h1>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <a href="dept_admin_view_students.php" class="text-decoration-none">
                        <div class="card text-center h-100 shadow-sm">
                            <div class="card-body py-4">
                                <h5>👨‍🎓 View All Students</h5>
                                <p class="text-muted">Manage students in your department</p>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6">
                    <a href="dept_admin_view_projects.php" class="text-decoration-none">
                        <div class="card text-center h-100 shadow-sm">
                            <div class="card-body py-4">
                                <h5>📂 View Projects</h5>
                                <p class="text-muted">Pending & Approved Projects</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>