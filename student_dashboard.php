<?php
// student_dashboard.php

require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch student details with Department Name
$stmt = $pdo->prepare("
    SELECT s.*, d.name AS department_name 
    FROM students s 
    LEFT JOIN departments d ON s.department_id = d.id 
    WHERE s.id = ?
");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

// Fetch student's uploaded projects
$stmt = $pdo->prepare("SELECT * FROM projects WHERE student_id = ? ORDER BY upload_date DESC");
$stmt->execute([$user_id]);
$projects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - NACOS FPE Chapter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .welcome-card { 
            background: linear-gradient(135deg, #28a745, #20c997); 
            color: white; 
        }
        .passport-img { 
            width: 150px; 
            height: 150px; 
            object-fit: cover; 
            border-radius: 50%; 
            border: 5px solid white; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
<div class="container mt-5">

    <!-- Welcome Header -->
    <div class="card welcome-card shadow mb-4">
        <div class="card-body d-flex align-items-center gap-4">
            
            <!-- Passport -->
            <div class="text-center">
                <?php if (!empty($student['passport'])): ?>
                    <img src="uploads/passports/<?= htmlspecialchars($student['passport']) ?>" 
                         class="passport-img" 
                         alt="Passport"
                         onerror="this.onerror=null; this.src='https://via.placeholder.com/150?text=No+Photo';">
                <?php else: ?>
                    <div class="passport-img bg-secondary d-flex align-items-center justify-content-center text-white fs-1">
                        👤
                    </div>
                <?php endif; ?>
            </div>

            <div  >
             
                <h2>Welcome back, <?= htmlspecialchars($student['name'] ?? 'Student') ?>! 👋</h2>
                 
                <p class="mb-1 fs-5">
                    Matric No: <strong><?= htmlspecialchars($student['matric_no'] ?? 'N/A') ?></strong>
                </p>
                <p>
                    Department: <strong><?= htmlspecialchars($student['department_name'] ?? 'Not Assigned') ?></strong><br>
                    Level: <strong><?= htmlspecialchars($student['level'] ?? 'N/A') ?></strong> 
                    • Status: <span class="badge bg-success">Active</span>
                </p>
                   <div class=' bg-green text-white d-flex justify-content-right align-items-right'>
                 <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Quick Actions -->
        <div class="col-md-4">
            <a href="edit_profile.php" class="text-decoration-none">
                <div class="card h-100 shadow text-center">
                    <div class="card-body py-4">
                        <h5>✏️ Edit Profile</h5>
                        <p class="text-muted small">Update your information</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="project_upload.php" class="text-decoration-none">
                <div class="card h-100 shadow text-center">
                    <div class="card-body py-4">
                        <h5>📤 Upload Project</h5>
                        <p class="text-muted small">Submit your final year project</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <div class="card h-100 shadow text-center">
                <div class="card-body py-4">
                    <h5>📊 Projects Uploaded</h5>
                    <h2 class="text-success"><?= count($projects) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- My Projects Section -->
   <!-- MY UPLOADED PROJECTS -->
<!-- MY UPLOADED PROJECTS -->
<h4 class="mt-5">My Uploaded Projects</h4>

<table class="table table-bordered">
    <thead class="table-dark">
        <tr>
            <th>Title</th>
            <th>Supervisor</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM projects 
                               WHERE student_id = ? 
                               ORDER BY uploaded_at DESC");
        $stmt->execute([$_SESSION['user_id']]);

        while ($row = $stmt->fetch()) {
            $download = '';
            if ($row['status'] == 'approved') {
                $download = "<a href='{$row['file_path']}' class='btn btn-primary btn-sm' download>Download</a>";
            }

            echo "<tr>
                    <td>{$row['title']}</td>
                    <td>{$row['supervisor']}</td>
                    <td>
                        <span class='badge bg-" . ($row['status'] == 'approved' ? 'success' : ($row['status'] == 'rejected' ? 'danger' : 'warning')) . "'>
                            " . ucfirst($row['status']) . "
                        </span>
                    </td>
                    <td>{$download}</td>
                  </tr>";
        }
        ?>
    </tbody>
</table>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>