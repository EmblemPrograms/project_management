<?php
// final_project_upload.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Security: Only allow student with verified OTP
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student' || !isset($_SESSION['otp_verified'])) {
    header("Location: student_dashboard.php");
    exit;
}

// Fetch student details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? $student['project_title']);
    $file = $_FILES['project_file'];

    if ($file['error'] !== 0) {
        $error = "Please select a project file to upload.";
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx'];

        if (!in_array($ext, $allowed)) {
            $error = "Only PDF, DOC, and DOCX files are allowed.";
        } elseif ($file['size'] > 20 * 1024 * 1024) { // 20MB limit
            $error = "File size must not exceed 20MB.";
        } else {
            // Generate unique filename
            $new_name = 'proj_' . uniqid() . '.' . $ext;
            $file_path = UPLOAD_PROJECT_DIR . $new_name;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Insert into projects table
                $stmt = $pdo->prepare("INSERT INTO projects (student_id, title, file_path, status) 
                                       VALUES (?, ?, ?, 'pending')");
                $stmt->execute([$_SESSION['user_id'], $title, $file_path]);

                // Clear session flags
                unset($_SESSION['pending_submission']);
                unset($_SESSION['otp_verified']);
                unset($_SESSION['project_title']);

                $success = "Project uploaded successfully! Your submission is now pending approval.";
            } else {
                $error = "Failed to upload file. Please try again.";
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
    <title>Final Project Upload - NACOS FPE CHAPTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        .upload-card { max-width: 600px; margin: 60px auto; }
    </style>
</head>
<body>
<div class="container">
    <div class="upload-card card shadow">
        <div class="card-header bg-success text-white text-center">
            <h4>Final Step - Upload Project Document</h4>
        </div>
        <div class="card-body p-5">

            <?php if ($success): ?>
                <div class="alert alert-success text-center">
                    <?php echo $success; ?>
                    <hr>
                    <a href="student_dashboard.php" class="btn btn-success">Go to Dashboard</a>
                </div>
            <?php else: ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label">Project Title</label>
                        <input type="text" name="title" class="form-control" 
                               value="<?php echo htmlspecialchars($student['project_title'] ?? ''); ?>" readonly>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Upload Project File 
                            <span class="text-danger">*</span><br>
                            <small class="text-muted">(PDF, DOC, DOCX - Maximum 20MB)</small>
                        </label>
                        <input type="file" name="project_file" class="form-control" 
                               accept=".pdf,.doc,.docx" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">Submit Project Now</button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <a href="student_dashboard.php" class="text-muted">Cancel & Return to Dashboard</a>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>