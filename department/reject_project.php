<?php
// reject_project.php
require_once '../includes/config.php';

// Authentication Check
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'department_admin') {
    header("Location: ../admin/");
    exit;
}

// Get project ID from URL (GET) or form submission
$project_id = $_GET['id'] ?? $_POST['project_id'] ?? null;

if (!$project_id || !is_numeric($project_id)) {
    $_SESSION['error'] = "Invalid Project ID.";
    header("Location: view_projects.php");
    exit;
}

$error = '';
$success = '';

// Process Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $remark = trim($_POST['remark'] ?? '');

    if (empty($remark)) {
        $error = "Please provide a reason for rejecting this project.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE projects 
                                  SET status = 'rejected', 
                                      remark = ?, 
                                      approved_at = NOW() 
                                  WHERE id = ?");
            $stmt->execute([$remark, $project_id]);

            $_SESSION['success'] = "Project has been successfully rejected.";
            header("Location: view_projects.php");
            exit;

        } catch (PDOException $e) {
            $error = "Database error: Unable to reject project. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reject Project - NACOS FPE Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="https://ik.imagekit.io/emblem/NNL.png" type="image/x-icon">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">Reject Project</h4>
                    </div>
                    <div class="card-body">

                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="project_id" value="<?= htmlspecialchars($project_id) ?>">

                            <div class="mb-3">
                                <label for="remark" class="form-label fw-bold">
                                    Rejection Reason <span class="text-danger">*</span>
                                </label>
                                <textarea 
                                    class="form-control" 
                                    id="remark" 
                                    name="remark" 
                                    rows="6" 
                                    placeholder="Enter detailed reason for rejecting this project..."
                                    required><?= isset($_POST['remark']) ? htmlspecialchars($_POST['remark']) : '' ?></textarea>
                                <small class="text-muted">This remark will be visible to the student.</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    Confirm Rejection
                                </button>
                                <a href="view_projects.php" class="btn btn-secondary">
                                    Cancel
                                </a>
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