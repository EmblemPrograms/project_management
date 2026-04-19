<?php
// grand_admin_dashboard.php

require_once 'config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'grand_admin') {
    header("Location: grand_admin_login.php");
    exit;
}

// Get filter values
$search     = $_GET['search'] ?? '';
$session    = $_GET['session'] ?? '';
$supervisor = $_GET['supervisor'] ?? '';
$status     = $_GET['status'] ?? '';

// Build query
$query = "
    SELECT p.*, s.name AS student_name, s.matric_no, d.name AS department_name 
    FROM projects p 
    JOIN students s ON p.student_id = s.id 
    JOIN departments d ON s.department_id = d.id 
    WHERE 1=1
";

$params = [];

if (!empty($search)) {
    $like = "%$search%";
    $query .= " AND (s.matric_no LIKE ? OR p.title LIKE ? OR s.name LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($session)) {
    $query .= " AND s.session = ?";
    $params[] = $session;
}

if (!empty($supervisor)) {
    $query .= " AND p.supervisor LIKE ?";
    $params[] = "%$supervisor%";
}

if (!empty($status)) {
    $query .= " AND p.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY p.uploaded_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Admin Dashboard - NACOS FPE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .filter-box { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        th { background-color: #f8f9fa; font-weight: 600; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
<div class="card shadow">

     <div class="card-header bg-dark text-white d-flex justify-content-between">
            <h4>Grand Admin Dashboard</h4>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>

    <!-- Quick Action Cards -->
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

    <!-- Filter -->
    <div class="filter-box p-4 mb-4">
    
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label"> (Matric No / Topic / Student)</label>
                <input type="text" name="search" class="form-control" 
                       value="<?= htmlspecialchars($search) ?>" placeholder="Search...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Session</label>
                <input type="text" name="session" class="form-control" 
                       value="<?= htmlspecialchars($session) ?>" placeholder="2023/2024">
            </div>
            <div class="col-md-3">
                <label class="form-label">Supervisor</label>
                <input type="text" name="supervisor" class="form-control" 
                       value="<?= htmlspecialchars($supervisor) ?>" placeholder="Supervisor name">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="pending" <?= $status=='pending'?'selected':'' ?>>Pending</option>
                    <option value="approved" <?= $status=='approved'?'selected':'' ?>>Approved</option>
                    <option value="rejected" <?= $status=='rejected'?'selected':'' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-success w-100">Apply Filter</button>
            </div>
        </form>
    </div>

    <!-- Projects Table -->
    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5>All Projects (<?= count($projects) ?> results)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Matric No</th>
                            <th>Student Name</th>
                            <th>Project Title</th>
                            <th>Supervisor</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Uploaded</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr><td colspan="7" class="text-center py-4">No projects found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($projects as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['matric_no']) ?></td>
                                    <td><?= htmlspecialchars($row['student_name']) ?></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['supervisor']) ?></td>
                                    <td><?= htmlspecialchars($row['department_name']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $row['status'] == 'approved' ? 'success' : ($row['status'] == 'rejected' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $row['uploaded_at'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>