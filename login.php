<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: student_dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matric_no = strtoupper(trim($_POST['matric_no']));
    $password  = $_POST['password'];

    if (empty($matric_no) || empty($password)) {
        $error = "Matriculation Number and Password are required.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, matric_no, password_hash, role, approved, level, passport 
                                   FROM students 
                                   WHERE matric_no = ? AND role = 'student'");
            $stmt->execute([$matric_no]);
            $user = $stmt->fetch();

           if ($user && password_verify($password, $user['password_hash'])) {
    
    if ($user['approved'] == 0) {
        $error = "Your account is not yet approved by the administrator.";
    } else {
        // Successful login
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['matric_no']  = $user['matric_no'];
        $_SESSION['name']       = $user['name'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['level']      = $user['level'];
        $_SESSION['passport']   = $user['passport'];
        $_SESSION['pair_id']    = $user['pair_id'] ?? null;

        // Improved Pair Handling
        if ($user['level'] === 'ND' && $user['pair_id']) {
            $stmt2 = $pdo->prepare("
                SELECT u.id, u.name, u.matric_no, u.passport 
                FROM nd_pairs p
                JOIN students u ON (u.id = p.student1_id OR u.id = p.student2_id)
                WHERE p.id = ? AND u.id != ?
            ");
            $stmt2->execute([$user['pair_id'], $user['id']]);
            $partner = $stmt2->fetch();

            if ($partner) {
                $_SESSION['partner_id']      = $partner['id'];
                $_SESSION['partner_name']    = $partner['name'];
                $_SESSION['partner_matric']  = $partner['matric_no'];
                $_SESSION['partner_passport'] = $partner['passport'];
            }
        }

        header("Location: student_dashboard.php");
        exit;
    }
} else {
                $error = "Invalid Matriculation Number or Password.";
            }
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOS FPE CHAPTER - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .login-card {
            max-width: 420px;
            margin: 80px auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .nacoss-header {
            background: linear-gradient(90deg, #28a745, #20c997);
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
        <div class="nacoss-header">
            <h2>NACOS FPE CHAPTER</h2>
            <h5>Final Year Project Portal</h5>
        </div>
        
        <div class="card-body p-4">
            <h4 class="text-center mb-4 text-success">Student Login</h4>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Matriculation Number <span class="text-danger">*</span></label>
                    <input type="text" 
                           name="matric_no" 
                           class="form-control" 
                           placeholder="e.g. SW20240123 or ND202512345" 
                           required 
                           style="text-transform: uppercase;">
                </div>

                <div class="mb-3">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-success btn-lg">Login as Student</button>
                </div>
            </form>

                     <div class="text-center mt-4">
                <p>Don't have an account? 
                    <a href="register.php" class="text-success fw-bold">Register here</a>
                </p>
                <p><a href="forgot_password.php" class="text-muted">Forgot Password?</a></p>
            </div>

            <!-- ADMIN LOGIN BUTTON -->
            <div class="text-center mt-4 pt-3 border-top">
                <a href="admin_login.php" class="btn btn-outline-dark btn-lg w-100">
                    🔑 Login as Admin
                </a>
            </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>