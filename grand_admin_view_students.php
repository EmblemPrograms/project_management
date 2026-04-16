<?php
// grand_admin_view_students.php

require_once 'config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'grand_admin') {
    header("Location: grand_admin_login.php");
    exit;
}

$department_id = $_GET['department_id'] ?? '';
$search = trim($_GET['search'] ?? '');

// Fetch departments for filter dropdown
$stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
$departments = $stmt->fetchAll();

// Build query for students
$sql = "SELECT s.* FROM students s WHERE 1=1";
$params = [];

if ($department_id) {
    $sql .= " AND s.department_id = ?";
    $params[] = $department_id;
}

if ($search) {
    $sql .= " AND (s.name LIKE ? OR s.matric_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY s.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students - Grand Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Students Management</h3>
        <a href="grand_admin_view_departments.php" class="btn btn-secondary">← Back to Departments</a>
    </div>

    <!-- Search & Filter -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by name or matric number" 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-5">
                    <select name="department_id" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= $department_id == $dept['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <?php if (empty($students)): ?>
                <p class="text-center text-muted py-5">No students found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Matric No</th>
                                <th>Full Name</th>
                                <th>Level</th>
                                <th>Passport</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['matric_no']) ?></strong></td>
                                <td><?= htmlspecialchars($s['name']) ?></td>
                                <td><?= htmlspecialchars($s['level']) ?></td>
                                <td>
                                    <?php if (!empty($s['passport'])): ?>
                                        <img src="uploads/passports/<?= htmlspecialchars($s['passport']) ?>" 
                                             width="50" height="50" style="object-fit:cover; border-radius:50%;">
                                    <?php else: ?>
                                        <span class="text-muted">No photo</span>
                                    <?php endif; ?>
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