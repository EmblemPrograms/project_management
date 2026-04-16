<?php
// grand_admin_view_departments.php

require_once 'config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'grand_admin') {
    header("Location: grand_admin_login.php");
    exit;
}

// Fetch all departments with student count
$stmt = $pdo->prepare("
    SELECT d.*, 
           COUNT(s.id) AS total_students
    FROM departments d
    LEFT JOIN students s ON s.department_id = d.id
    GROUP BY d.id
    ORDER BY d.name ASC
");
$stmt->execute();
$departments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Departments - Grand Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>All Departments</h3>
        <a href="grand_admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <?php if (empty($departments)): ?>
                <p class="text-center text-muted py-5">No departments created yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Department Name</th>
                                <th>Code</th>
                                <th class="text-center">Total Students</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($dept['name']) ?></strong></td>
                                
                                <td class="text-center">
                                    <span class="badge bg-success fs-6"><?= $dept['total_students'] ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="grand_admin_view_students.php?department_id=<?= $dept['id'] ?>" 
                                       class="btn btn-sm btn-success">
                                        View Students
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>