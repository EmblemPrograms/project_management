<?php
// dept_admin_view_students.php
require_once 'config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'department_admin') {
    header("Location: grand_admin_login.php");
    exit;
}

$department_id = $_SESSION['department_id'];

// Get filter values
$search  = $_GET['search'] ?? '';
$session = $_GET['session'] ?? '';

// Build query with filters
$query = "
    SELECT s.*, d.name AS department_name 
    FROM students s 
    JOIN departments d ON s.department_id = d.id 
    WHERE s.department_id = ?
";

$params = [$department_id];

if (!empty($search)) {
    $like = "%$search%";
    $query .= " AND (s.matric_no LIKE ? OR s.name LIKE ?)";
    $params[] = $like;
    $params[] = $like;
}

if (!empty($session)) {
    $query .= " AND s.session = ?";
    $params[] = $session;
}

$query .= " ORDER BY s.matric_no ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Students - Department Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>All Students in <?= htmlspecialchars($_SESSION['department_name'] ?? 'Your Department') ?></h3>
        <a href="department_admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search (Matric No or Student Name)</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Enter matric number or name">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Session</label>
                    <input type="text" name="session" class="form-control" 
                           value="<?= htmlspecialchars($session) ?>" 
                           placeholder="e.g. 2023/2024">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">Apply Filter</button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="dept_admin_view_students.php" class="btn btn-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Students Table -->
    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5>Students Found (<?= count($students) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Matric No</th>
                            <th>Student Name</th>
                            <th>Session</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No students found matching your filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['matric_no']) ?></td>
                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                    <td><?= htmlspecialchars($student['session']) ?></td>
                                    <td><?= htmlspecialchars($student['email']) ?></td>
                                    <td><?= htmlspecialchars($student['phone'] ?? 'N/A') ?></td>
                                    <td><?= $student['created_at'] ?? 'N/A' ?></td>
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