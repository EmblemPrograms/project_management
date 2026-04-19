<?php
// dept_admin_view_projects.php
require_once 'config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'department_admin') {
    header("Location: grand_admin_login.php");
    exit;
}

$department_id = $_SESSION['department_id'];

// Get filter values
$search     = $_GET['search'] ?? '';
$session    = $_GET['session'] ?? '';
$supervisor = $_GET['supervisor'] ?? '';
$status     = $_GET['status'] ?? '';

// Build query
$query = "
    SELECT p.*, s.name AS student_name, s.matric_no 
    FROM projects p 
    JOIN students s ON p.student_id = s.id 
    WHERE s.department_id = ?
";

$params = [$department_id];

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
    <title>View Projects - <?= htmlspecialchars($_SESSION['department_name'] ?? 'Department') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Projects in Your Department</h3>
        <a href="department_admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search (Matric No / Topic)</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Search...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Session</label>
                    <input type="text" name="session" class="form-control" value="<?= htmlspecialchars($session) ?>" placeholder="2023/2024">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Supervisor</label>
                    <input type="text" name="supervisor" class="form-control" value="<?= htmlspecialchars($supervisor) ?>" placeholder="Supervisor name">
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
    </div>

    <!-- Projects Table -->
    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5>Projects Found (<?= count($projects) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Matric No</th>
                            <th>Student Name</th>
                            <th>Project Title</th>
                            <th>Supervisor</th>
                            <th>Status</th>
                            <th>Uploaded</th>
                            <th>Action</th>
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
                                    <td>
                                        <span class="badge bg-<?= $row['status']=='approved' ? 'success' : ($row['status']=='rejected' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $row['uploaded_at'] ?></td>
                                   <td>
    <?php if ($row['status'] == 'pending'): ?>
        <!-- View Document Button -->
        <a href="<?= htmlspecialchars($row['file_path']) ?>" 
           class="btn btn-info btn-sm me-1" target="_blank">
            <i class="fas fa-eye"></i> View PDF
        </a>
        
        <a href="approve_project.php?id=<?= $row['id'] ?>" 
           class="btn btn-success btn-sm me-1"
           onclick="return confirm('Approve this project?')">Approve</a>
        
        <a href="reject_project.php?id=<?= $row['id'] ?>" 
           class="btn btn-danger btn-sm"
           onclick="return confirm('Reject this project?')">Reject</a>
    <?php elseif ($row['status'] == 'approved'): ?>
        <a href="<?= htmlspecialchars($row['file_path']) ?>" 
           class="btn btn-success btn-sm" download>
            <i class="fas fa-download"></i> Download
        </a>
    <?php endif; ?>
</td>
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