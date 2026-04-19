<?php
// grand_admin_dashboard.php

require_once 'config.php';

// PHPMailer (install via composer: composer require phpmailer/phpmailer)
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'grand_admin') {
    header("Location: grand_admin_login.php");
    exit;
}

// Handle submission settings update + notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_submission'])) {
    $start = $_POST['submission_start'];
    $end = $_POST['submission_end'];

    // Save dates
    $stmt = $pdo->prepare("INSERT INTO submission_settings (id, submission_start, submission_end) 
                           VALUES (1, ?, ?) 
                           ON DUPLICATE KEY UPDATE submission_start = ?, submission_end = ?");
    $stmt->execute([$start, $end, $start, $end]);

    // Send email notification to ALL students
    $stmt = $pdo->query("SELECT name, email FROM students WHERE email IS NOT NULL AND email != ''");
    $students = $stmt->fetchAll();

    if (!empty($students)) {
        $mail = new PHPMailer(true);
        try {
            // ============== SMTP CONFIGURATION ==============
            // Change these to your actual SMTP details (recommended: use Gmail, Brevo, Mailgun, etc.)
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;

            $mail->setFrom('noreply@nacosfpe.edu.ng', 'NACOS FPE Admin');
            $mail->isHTML(true);

            $subject = "Project Submission Portal is Now Open";
            $body = "
                <h2>Dear Student,</h2>
                <p>Project submission has officially begun.</p>
                <p><strong>Submission Start Date:</strong> " . date('d F, Y', strtotime($start)) . "<br>
                   <strong>Submission End Date:</strong> " . date('d F, Y', strtotime($end)) . "</p>
                <p>Please ensure you upload your project before the deadline.</p>
                <p>Best regards,<br>NACOS FPE Admin</p>
            ";

            foreach ($students as $student) {
                $mail->clearAddresses();
                $mail->addAddress($student['email'], $student['name']);
                $mail->Subject = $subject;
                $mail->Body = $body;
                $mail->send();
            }

            $success_msg = "Dates updated and notification sent successfully to " . count($students) . " students.";
        } catch (Exception $e) {
            $error_msg = "Dates saved, but email sending failed: " . $mail->ErrorInfo;
        }
    } else {
        $error_msg = "Dates updated, but no student emails found.";
    }
}

// Get current submission dates
$stmt = $pdo->query("SELECT submission_start, submission_end FROM submission_settings WHERE id = 1");
$settings = $stmt->fetch();
$submission_start = $settings['submission_start'] ?? '';
$submission_end = $settings['submission_end'] ?? '';

// Rest of your existing code for fetching projects (unchanged)
$search = $_GET['search'] ?? '';
$session = $_GET['session'] ?? '';
$supervisor = $_GET['supervisor'] ?? '';
$status = $_GET['status'] ?? '';

// Build query (your original query remains exactly the same)
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
        .filter-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .card-header {
            background-color: #2563eb !important;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-5">

        <div class="card shadow">
            <div class="card-header text-white d-flex justify-content-between">
                <h4>Grand Admin Dashboard</h4>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>

            <div class="card-body">

                <!-- Success / Error Message -->
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
                <?php endif; ?>
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_msg) ?></div>
                <?php endif; ?>

                <!-- Submission Dates & Notification Form -->
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-calendar-alt"></i> Project Submission Period & Notification</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">Submission Start Date</label>
                                <input type="date" name="submission_start" class="form-control"
                                    value="<?= htmlspecialchars($submission_start) ?>" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Submission End Date</label>
                                <input type="date" name="submission_end" class="form-control"
                                    value="<?= htmlspecialchars($submission_end) ?>" required>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" name="update_submission" class="btn btn-success w-100">
                                    <i class="fas fa-paper-plane"></i> Update & Notify All Students
                                </button>
                            </div>
                        </form>
                        <small class="text-muted mt-2 d-block">
                            This will save the dates and immediately email every registered student.
                        </small>
                    </div>
                </div>

                <!-- Your existing Quick Action Cards -->
                <div class="row g-4 mb-4">
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

                <!-- Your existing Filter and Projects Table remain exactly the same -->
                <!-- (I kept them unchanged for brevity - copy from your original code) -->

                <div class="filter-box p-4 mb-4">
                    <form method="GET" class="row g-3">
                        <!-- Your filter fields here - unchanged -->
                        <div class="col-md-3">
                            <label class="form-label">Search (Matric No / Topic / Student)</label>
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
                                <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="approved" <?= $status == 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="rejected" <?= $status == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">Apply Filter</button>
                        </div>
                    </form>
                </div>

                <!-- Projects Table (your original table code) -->
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
                                        <tr>
                                            <td colspan="7" class="text-center py-4">No projects found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($projects as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['matric_no']) ?></td>
                                                <td><?= htmlspecialchars($row['student_name']) ?></td>
                                                <td><?= htmlspecialchars($row['title']) ?></td>
                                                <td><?= htmlspecialchars($row['supervisor']) ?></td>
                                                <td><?= htmlspecialchars($row['department_name']) ?></td>
                                                <td>
                                                    <span
                                                        class="badge bg-<?= $row['status'] == 'approved' ? 'success' : ($row['status'] == 'rejected' ? 'danger' : 'warning') ?>">
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>