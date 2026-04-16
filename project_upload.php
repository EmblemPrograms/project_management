<?php
// project_upload.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
            $upload_dir = "uploads/projects/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $new_name = "proj_" . uniqid() . ".pdf";
            $full_path = $upload_dir . $new_name;

            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                // Save data in session
                $_SESSION['pending_submission'] = [
                    'title'      => $title,
                    'abstract'   => $abstract,
                    'supervisor' => $supervisor,
                    'file_path'  => $full_path
                ];

                header("Location: verify_otp_submission.php");
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
            </form>
        </div>
    </div>
</div>
</body>
</html>