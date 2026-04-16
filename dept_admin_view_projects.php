<?php
// dept_admin_view_projects.php
require_once 'config.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'department_admin') {
    header("Location: grand_admin_login.php");
    exit;
}

// Get department ID
$department_id = $_SESSION['department_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Projects - Department Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Projects in <?= htmlspecialchars($_SESSION['department_name'] ?? 'Your Department') ?></h3>
        <a href="department_admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>Student Name</th>
                <th>Project Title</th>
                <th>Supervisor</th>
                <th>Status</th>
                <th>Uploaded</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
    <?php
    $stmt = $pdo->prepare("
        SELECT p.*, s.name AS student_name 
        FROM projects p 
        JOIN students s ON p.student_id = s.id 
        WHERE s.department_id = ? 
        ORDER BY p.uploaded_at DESC
    ");
    $stmt->execute([$department_id]);

    while ($row = $stmt->fetch()) {
        $status_class = $row['status'] == 'approved' ? 'success' : ($row['status'] == 'rejected' ? 'danger' : 'warning');
        $status_text  = ucfirst($row['status']);

        echo "<tr>
                <td>{$row['student_name']}</td>
                <td>{$row['title']}</td>
                <td>{$row['supervisor']}</td>
                <td><span class='badge bg-{$status_class}'>{$status_text}</span></td>
                <td>{$row['uploaded_at']}</td>
                <td>";

        if ($row['status'] == 'pending') {
            echo "
                <a href='approve_project.php?id={$row['id']}' 
                   class='btn btn-success btn-sm me-2'
                   onclick=\"return confirm('Approve this project?');\">Approve</a>
                
                <button class='btn btn-danger btn-sm' 
                        data-bs-toggle='modal' 
                        data-bs-target='#rejectModal'
                        data-id='{$row['id']}'>
                    Reject
                </button>";
        } elseif ($row['status'] == 'approved') {
            echo "<a href='{$row['file_path']}' class='btn btn-success btn-sm' download>Download</a>";
        }

        echo "</td></tr>";
    }
    ?>
</tbody>
    </table>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="reject_project.php">
                <div class="modal-body">
                    <input type="hidden" name="project_id" id="reject_project_id">
                    <label class="form-label">Remark (Optional)</label>
                    <textarea name="remark" class="form-control" rows="3" placeholder="Why are you rejecting this project?"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Pass project ID to modal
    const rejectModal = document.getElementById('rejectModal');
    rejectModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const projectId = button.getAttribute('data-id');
        document.getElementById('reject_project_id').value = projectId;
    });
</script>
</body>
</html>