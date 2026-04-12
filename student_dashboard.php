<?php
// === DEBUG MODE - REMOVE LATER ===
error_reporting(E_ALL);
ini_set('display_errors', 1);
// =================================

require_once 'config.php';

// Only allow logged-in students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

// Fetch current student details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Fetch student's previous project submissions (Fixed Query)
$stmt = $pdo->prepare("SELECT p.* 
                       FROM projects p 
                       WHERE p.student_id = ? 
                       ORDER BY p.upload_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$submissions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - NACOS FPE CHAPTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        .dashboard-header { background: linear-gradient(90deg, #28a745, #20c997); color: white; padding: 25px 0; }
        .card { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
    </style>
</head>
<body>

    <div class="dashboard-header text-center">
        <div class="container">
            <h2>NACOS FPE CHAPTER</h2>
            <h4>Final Year Project Management System</h4>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <!-- Student Info Card -->
            <div class="col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white text-center">
                        <h5>Student Information</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if (!empty($student['passport'])): ?>
                            <img src="<?php echo htmlspecialchars($student['passport']); ?>" 
                                 class="rounded-circle mb-3" width="120" height="120" style="object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                                 style="width:120px;height:120px;">
                                <span class="text-muted">No Photo</span>
                            </div>
                        <?php endif; ?>

                        <table class="table table-borderless small">
                            <tr><td><strong>Matric No:</strong></td><td><?php echo htmlspecialchars($student['matric_no'] ?? ''); ?></td></tr>
                            <tr><td><strong>Name:</strong></td><td><?php echo htmlspecialchars($student['name']); ?></td></tr>
                            <tr><td><strong>Email:</strong></td><td><?php echo htmlspecialchars($student['email'] ?? ''); ?></td></tr>
                            <tr><td><strong>Contact:</strong></td><td><?php echo htmlspecialchars($student['contact'] ?? ''); ?></td></tr>
                            <tr><td><strong>Session:</strong></td><td><?php echo htmlspecialchars($student['session']); ?></td></tr>
                        </table>
                    </div>
                    <div class="card-footer text-center">
                        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5>Welcome, <?php echo htmlspecialchars($student['name']); ?>!</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Important:</strong> You can submit your final year project only once.
                        </div>

                        <div class="text-center my-5">
                            <a href="upload_project.php" class="btn btn-success btn-lg px-5 py-3">
                                <i class="bi bi-upload"></i> Submit Final Year Project
                            </a>
                        </div>

                        <h5 class="mt-5 mb-3">Your Project Submissions</h5>

                        <?php if (empty($submissions)): ?>
                            <div class="alert alert-warning text-center">
                                You have not submitted any project yet.<br>
                                Click the button above to begin submission.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Title</th>
                                            <th>Upload Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($submissions as $sub): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sub['title'] ?? ''); ?></td>
                                            <td><?php echo date('d M Y H:i', strtotime($sub['upload_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($sub['status'] === 'approved') ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($sub['status']); ?>
                                                </span>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>