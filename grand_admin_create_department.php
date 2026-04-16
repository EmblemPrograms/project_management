<?php
// grand_admin_create_department.php

require_once 'config.php';

if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'grand_admin') {
    header("Location: grand_admin_login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $code = strtoupper(trim($_POST['code']));

    if (empty($name) || empty($code)) {
        $error = "All fields are required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO departments (name, code) VALUES (?, ?)");
            $stmt->execute([$name, $code]);
            $success = "Department '$name' created successfully!";
        } catch (Exception $e) {
            $error = "Department code already exists or error occurred.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Department</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5>Create New Department</h5>
        </div>
        <div class="card-body">
            <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label>Department Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Department Code (e.g. CSC, MTH)</label>
                    <input type="text" name="code" class="form-control text-uppercase" maxlength="10" required>
                </div>
                <button type="submit" class="btn btn-success">Create Department</button>
                <a href="grand_admin_dashboard.php" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>