<?php
// final_project_upload.php - FIXED VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['pending_submission'])) {
    header("Location: student_dashboard.php");
    exit;
}

$submission = $_SESSION['pending_submission'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $pdo->prepare("INSERT INTO projects 
        (student_id, title, abstract, supervisor, file_path, status) 
        VALUES (?, ?, ?, ?, ?, 'pending')");

    $result = $stmt->execute([
        $_SESSION['user_id'],
        $submission['title'],
        $submission['abstract'],
        $submission['supervisor'],
        $submission['file_path']
    ]);

    if ($result) {
        $success = "Project submitted successfully! Waiting for admin approval.";
        unset($_SESSION['pending_submission']);
    } else {
        $error = "Failed to save project to database.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Project Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        .card { max-width: 650px; margin: 80px auto; }
    </style>
</head>
<body>
<div class="container">
    <div class="card shadow">
        <div class="card-header bg-success text-white text-center py-3">
            <h4>Final Project Submission</h4>
        </div>
        
        <div class="card-body p-5 text-center">

            <?php if ($success): ?>
                <div class="alert alert-success py-4">
                    <h5>✅ Success!</h5>
                    <p class="lead"><?= htmlspecialchars($success) ?></p>
                </div>
                <a href="student_dashboard.php" class="btn btn-success btn-lg">Go to Dashboard</a>

            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <h5 class="mb-4">Confirm Submission</h5>
                
                <div class="text-start border p-4 rounded mb-4 bg-light">
                    <p><strong>Title:</strong> <?= htmlspecialchars($submission['title']) ?></p>
                    <p><strong>Abstract:</strong> <?= nl2br(htmlspecialchars($submission['abstract'])) ?></p>
                    <p><strong>Supervisor:</strong> <?= htmlspecialchars($submission['supervisor']) ?></p>
                    <p><strong>File:</strong> <?= basename($submission['file_path']) ?></p>
                </div>

                <form method="POST">
                    <button type="submit" class="btn btn-success btn-lg w-100 py-3">
                        Confirm & Submit Project
                    </button>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>