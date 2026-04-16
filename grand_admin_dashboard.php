<?php
// grand_admin_dashboard.php

require_once 'config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'grand_admin') {
    header("Location: grand_admin_login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Admin Dashboard - NACOS FPE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-dark text-white d-flex justify-content-between">
            <h4>Grand Admin Dashboard</h4>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <a href="grand_admin_create_department.php" class="text-decoration-none">
                        <div class="card text-center h-100 shadow-sm">
                            <div class="card-body">
                                <h5>➕ Create New Department</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="grand_admin_create_dept_admin.php" class="text-decoration-none">
                        <div class="card text-center h-100 shadow-sm">
                            <div class="card-body">
                                <h5>👤 Create Department Admin</h5>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="grand_admin_view_departments.php" class="text-decoration-none">
                        <div class="card text-center h-100 shadow-sm">
                            <div class="card-body">
                                <h5>📋 View All Departments</h5>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>