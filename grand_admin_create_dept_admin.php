<?php
// grand_admin_create_dept_admin.php

require_once 'config.php';

if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'grand_admin') {
    header("Location: grand_admin_login.php");
    exit;
}

$error = '';
$success = '';

// Fetch all departments
$stmt = $pdo->query("SELECT * FROM departments ORDER BY name");
$departments = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $dept_id = $_POST['department_id'];
    $password = $_POST['password'];

    if (empty($full_name) || empty($email) || empty($dept_id) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO admins (full_name, email, password_hash, role, department_id) 
                                   VALUES (?, ?, ?, 'department_admin', ?)");
            $stmt->execute([$full_name, $email, $hash, $dept_id]);
            $success = "Department Admin created successfully!";
        } catch (Exception $e) {
            $error = "Email already exists or error occurred.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Department Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5>Create Department Admin</h5>
        </div>
        <div class="card-body">
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Department</label>
                    <select name="department_id" class="form-select" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success">Create Department Admin</button>
                <a href="grand_admin_dashboard.php" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>