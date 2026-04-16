<?php
// dept_admin_view_students.php

require_once 'config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'department_admin') {
    header("Location: grand_admin_login.php");
    exit;
}

$department_id = $_SESSION['department_id'];
$search   = trim($_GET['search'] ?? '');
$session  = trim($_GET['session'] ?? '');
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to'] ?? '';

// Build query
$sql = "SELECT * FROM students WHERE department_id = ?";
$params = [$department_id];

if ($search) {
    $sql .= " AND (name LIKE ? OR matric_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($session) {
    $sql .= " AND session = ?";
    $params[] = $session;
}

if ($date_from) {
    $sql .= " AND created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}
if ($date_to) {
    $sql .= " AND created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$sql .= " ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - <?= htmlspecialchars($_SESSION['department_name'] ?? 'Department') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3>Students in My Department</h3>
    <a href="department_admin_dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by Name or Matric No" 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <input type="text" name="session" class="form-control" 
                           placeholder="Session (e.g. 2024/2025)" 
                           value="<?= htmlspecialchars($session) ?>">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
                </div>
              
                <div class="col-md-1">
                    <button type="submit" class="btn btn-success w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <?php if (empty($students)): ?>
                <p class="text-center text-muted py-5">No students found.</p>
            <?php else: ?>
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Matric No</th>
                            <th>Full Name</th>
                            <th>Level</th>
                            <th>Session</th>
                            <th>Registered Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['matric_no']) ?></strong></td>
                            <td><?= htmlspecialchars($s['name']) ?></td>
                            <td><?= htmlspecialchars($s['level']) ?></td>
                            <td><?= htmlspecialchars($s['session'] ?? '-') ?></td>
                            <td><?= $s['created_at'] ? date('d M, Y', strtotime($s['created_at'])) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>