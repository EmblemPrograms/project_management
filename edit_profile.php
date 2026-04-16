<?php
// edit_profile.php

require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: register.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current student data
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $contact  = trim($_POST['contact']);
    $address  = trim($_POST['address']);

    try {
        $update_fields = "name = ?, email = ?, contact = ?, address = ?";
        $params = [$name, $email, $contact, $address];

        // Handle Passport Photo Upload
        if (isset($_FILES['passport']) && $_FILES['passport']['error'] === 0) {
            $file = $_FILES['passport'];
            $allowed = ['jpg', 'jpeg', 'png'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                    $error = "Passport photo must be less than 5MB.";
                } else {
                    $new_filename = "pass_" . uniqid() . "." . $ext;
                    $upload_path = "uploads/passports/" . $new_filename;

                    if (!is_dir("uploads/passports")) {
                        mkdir("uploads/passports", 0777, true);
                    }

                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        // Delete old photo if exists
                        if (!empty($student['passport']) && file_exists("uploads/passports/" . $student['passport'])) {
                            unlink("uploads/passports/" . $student['passport']);
                        }
                        $update_fields .= ", passport = ?";
                        $params[] = $new_filename;
                    }
                }
            } else {
                $error = "Only JPG, JPEG & PNG files are allowed for passport.";
            }
        }

        if (empty($error)) {
            $stmt = $pdo->prepare("UPDATE students SET $update_fields WHERE id = ?");
            $params[] = $user_id;
            $stmt->execute($params);

            $success = "Profile updated successfully!";

            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$user_id]);
            $student = $stmt->fetch();
        }

    } catch (Exception $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - NACOS FPE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .passport-preview { width: 180px; height: 180px; object-fit: cover; border-radius: 50%; border: 4px solid #28a745; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-success text-white">
            <h5>Edit Your Profile</h5>
        </div>
        <div class="card-body p-4">

            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                
                <!-- Current Passport -->
                <div class="text-center mb-4">
                    <?php if (!empty($student['passport'])): ?>
                        <img src="uploads/passports/<?= htmlspecialchars($student['passport']) ?>" 
                             class="passport-preview mb-2" alt="Current Photo">
                    <?php else: ?>
                        <div class="passport-preview bg-secondary d-flex align-items-center justify-content-center text-white fs-1 mb-2">
                            👤
                        </div>
                    <?php endif; ?>
                    <p class="small text-muted">Change Profile Picture</p>
                </div>

                <div class="mb-3">
                    <input type="file" name="passport" class="form-control" accept="image/jpeg,image/png">
                    <small class="text-muted">JPG or PNG only (Max 5MB)</small>
                </div>

                <div class="mb-3">
                    <label>Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($student['name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label>Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label>Contact Number</label>
                    <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($student['contact'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($student['address'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100">Save Changes</button>
                <a href="student_dashboard.php" class="btn btn-secondary w-100 mt-2">Back to Dashboard</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>