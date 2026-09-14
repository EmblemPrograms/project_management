<?php
// project_upload.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../includes/pair.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$error = '';

// An ND pair submits ONE project between them. If the other half has already
// uploaded, this student has nothing to do — show them what was submitted
// instead of letting the pair file a second project the department would then
// have to reconcile. A rejected project does not block a fresh attempt.
$existing_pair_project = null;
if (is_paired($pdo, (int) $_SESSION['user_id'])) {
    $existing_pair_project = pair_live_project($pdo, (int) $_SESSION['user_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $existing_pair_project) {
    // Re-checked on POST, not just on render: the partner may have submitted
    // while this form sat open.
    $error = "Your project has already been submitted by "
           . htmlspecialchars($existing_pair_project['uploaded_by']) . ".";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title      = trim($_POST['title'] ?? '');
    $abstract   = trim($_POST['abstract'] ?? '');
    $supervisor = trim($_POST['supervisor'] ?? '');
    $file       = $_FILES['softcopy'] ?? null;

    if (empty($title)) $error = "Title is required.";
    elseif (empty($abstract)) $error = "Abstract is required.";
    elseif (empty($supervisor)) $error = "Supervisor is required.";
    elseif (!$file || $file['error'] !== 0) $error = "Please upload PDF file.";

    else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            $error = "Only PDF files are allowed.";
        } elseif ($file['size'] > 10*1024*1024) {
            $error = "File size must be less than 10MB.";
        } else {
            $upload_dir = UPLOAD_PROJECT_DIR;   // absolute; see includes/config.php
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $new_name  = "proj_" . uniqid() . ".pdf";
            $full_path = $upload_dir . $new_name;

            // projects.file_path keeps the historical "uploads/projects/x.pdf"
            // shape so existing rows stay valid; the file itself now goes to
            // the one canonical directory. Views resolve it via project_url().
            $stored_path = 'uploads/projects/' . $new_name;

            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                // Save data in session
                $_SESSION['pending_submission'] = [
                    'title'      => $title,
                    'abstract'   => $abstract,
                    'supervisor' => $supervisor,
                    'file_path'  => $stored_path
                ];

                header("Location: submission.php");
                exit;
            } else {
                $error = "Failed to upload file. Check folder permissions.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Upload</title>
    <link rel="shortcut icon" href="https://ik.imagekit.io/emblem/NNL.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow mx-auto" style="max-width: 750px;">
        <div class="card-header bg-success text-white text-center">
            <h4>Project Submission</h4>
        </div>
        <div class="card-body p-5">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($existing_pair_project): ?>
                <!-- The pair has already submitted. Don't show a form the
                     student would fill in only to have it rejected. -->
                <div class="alert alert-success">
                    <h5 class="alert-heading">Your project is already submitted</h5>
                    <p class="mb-2">
                        <strong><?= htmlspecialchars($existing_pair_project['uploaded_by']) ?></strong>
                        (<?= htmlspecialchars($existing_pair_project['uploaded_by_matric']) ?>)
                        submitted it on behalf of your pair. You do not need to upload it again.
                    </p>
                    <hr>
                    <p class="mb-1"><strong>Title:</strong>
                        <?= htmlspecialchars($existing_pair_project['title']) ?></p>
                    <p class="mb-1"><strong>Supervisor:</strong>
                        <?= htmlspecialchars($existing_pair_project['supervisor'] ?? '') ?></p>
                    <p class="mb-0"><strong>Status:</strong>
                        <span class="badge bg-<?= $existing_pair_project['status'] === 'approved' ? 'success' : 'warning' ?>">
                            <?= htmlspecialchars(ucfirst($existing_pair_project['status'])) ?>
                        </span>
                    </p>
                </div>
                <a href="dashboard.php" class="btn btn-success w-100">Back to Dashboard</a>

            <?php else: ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label fw-bold">Project Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Abstract</label>
                    <textarea name="abstract" class="form-control" rows="5" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Supervisor</label>
                    <input type="text" name="supervisor" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Upload Softcopy (PDF)</label>
                    <input type="file" name="softcopy" class="form-control" accept=".pdf" required>
                </div>
                <button type="submit" class="btn btn-success btn-lg w-100">Continue</button>
                <a href="dashboard.php" class="btn btn-secondary w-100 mt-2">Back to Dashboard</a>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>