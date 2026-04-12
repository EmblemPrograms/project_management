<?php
// Enable error display temporarily for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: student_dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matric_no = strtoupper(trim($_POST['matric_no']));
    $password  = $_POST['password'];

    if (empty($matric_no) || empty($password)) {
        $error = "Matriculation Number and Password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE matric_no = ?");
        $stmt->execute([$matric_no]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['approved'] != 1) {
                $error = "Your email has not been verified yet. Please check your email and verify your account.";
            } else {
                $_SESSION['user_id']       = $user['id'];
                $_SESSION['matric_no']     = $user['matric_no'];
                $_SESSION['full_name']     = $user['name'];
                $_SESSION['role']          = $user['role'];
                $_SESSION['department_id'] = $user['department_id'];

                if ($user['role'] === 'student') {
                    header("Location: student_dashboard.php");
                } elseif ($user['role'] === 'dept_admin') {
                    header("Location: dept_admin.php");
                } else {
                    header("Location: grand_admin.php");
                }
                exit;
            }
        } else {
            $error = "Invalid Matriculation Number or Password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - NACOS FPE CHAPTER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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
                <h5>Final Year Project Register</h5>
            </div>

            <div class="card-body p-4">
                <h4 class="text-center mb-4 text-success">Login to Your Account</h4>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Matriculation Number <span class="text-danger">*</span></label>
                        <input type="text" name="matric_no" class="form-control" 
                               placeholder="e.g. SW20240113314" required autofocus>
                        <small class="text-muted">Enter your matric number exactly as registered</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-success btn-lg">Login</button>
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p>Don't have an account? 
                        <a href="register.php" class="text-success">Register here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordField  = document.getElementById('password');
        const icon           = document.getElementById('toggleIcon');

        togglePassword.addEventListener('click', function () {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>