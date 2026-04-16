<?php
// admin_login.php

require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email and Password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            
            // Set session for both types of admins
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_name'] = $admin['full_name'];

            if ($admin['role'] === 'department_admin') {
                $_SESSION['department_id'] = $admin['department_id'];
            }

            // Redirect based on role
            if ($admin['role'] === 'grand_admin') {
                header("Location: grand_admin_dashboard.php");
            } else {
                header("Location: department_admin_dashboard.php");
            }
            exit;

        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - NACOS FPE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .login-card {
            max-width: 420px;
            margin: 100px auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(90deg,rgb(18, 165, 116),rgb(9, 148, 83));
            color: white;
            padding: 25px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="login-card card">
        <div class="header">
            <h4>Admin Login</h4>
            <h6>NACOS FPE Chapter</h6>
        </div>
        
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success btn-lg w-100">Login as Admin</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>