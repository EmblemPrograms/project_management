<?php
// upload_project.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Only allow logged-in students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

// Fetch student details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Step 1: Handle Project Details + Passport + Trigger OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['otp'])) {
    
    $project_title = trim($_POST['project_title']);
    $supervisor    = trim($_POST['supervisor']);
    $abstract      = trim($_POST['abstract']);

    // Handle Passport Upload (if provided)
    $passport_path = $student['passport']; // Keep existing if any
    if (isset($_FILES['passport']) && $_FILES['passport']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['passport']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png'])) {
            $passport_path = UPLOAD_PASSPORT_DIR . 'pass_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['passport']['tmp_name'], $passport_path);
        }
    }

    // Update user with project details
    $stmt = $pdo->prepare("UPDATE users SET supervisor = ?, project_title = ?, passport = ? WHERE id = ?");
    $stmt->execute([$supervisor, $project_title, $passport_path, $_SESSION['user_id']]);

    // Generate OTP
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    $pdo->prepare("INSERT INTO email_verification (user_id, otp, expires_at, used) VALUES (?, ?, ?, 0)")
        ->execute([$_SESSION['user_id'], $otp, $expires_at]);

    // Send OTP via email
    require_once 'send_otp.php';
    $sent = sendVerificationOTP($student['email'], $otp, $student['name']);

    if ($sent) {
        $_SESSION['pending_submission'] = true;
        $_SESSION['project_title']      = $project_title;
        header("Location: verify_otp_submission.php");
        exit;
    } else {
        $error = "Failed to send OTP. Please try again.";
    }
}
// Change path if your header is elsewhere
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - NACOS FPE CHAPTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white text-center">
                    <h4>Final Year Project Submission - Step 1</h4>
                </div>
                <div class="card-body p-4">

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Project Title <span class="text-danger">*</span></label>
                            <input type="text" name="project_title" class="form-control" required 
                                   value="<?php echo htmlspecialchars($student['project_title'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Supervisor Name <span class="text-danger">*</span></label>
                            <input type="text" name="supervisor" class="form-control" required 
                                   value="<?php echo htmlspecialchars($student['supervisor'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Abstract <span class="text-danger">*</span></label>
                            <textarea name="abstract" class="form-control" rows="5" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Passport Photograph 
                                <?php if (!empty($student['passport'])): ?>(Current photo exists)<?php endif; ?>
                            </label>
                            <input type="file" name="passport" class="form-control" accept="image/*">
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                Generate Reference & Send OTP
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>