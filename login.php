<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: student_panel.php");
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
            
            // Check if email is verified (approved = 1)
            if ($user['approved'] != 1) {
                $error = "Your email has not been verified yet. Please check your email and verify your account.";
            } else {
                // Successful login - Set session variables
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['matric_no']  = $user['matric_no'];
                $_SESSION['full_name']  = $user['name'];
                $_SESSION['role']       = $user['role'];
                $_SESSION['department_id'] = $user['department_id'];

                // Redirect based on role
                if ($user['role'] === 'student') {
                    header("Location: student_panel.php");
                } elseif ($user['role'] === 'dept_admin') {
                    header("Location: dept_admin.php");
                } else { // grand_admin
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
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Matriculation Number <span class="text-danger">*</span></label>
                    <input type="text" name="matric_no" class="form-control" 
                           placeholder="e.g. SW20240110000" required autofocus>
                    <small class="text-muted">Enter your matric number exactly as registered</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="password" class="form-control" id="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <span><i class="bi bi-eye"></i></span>
                        </button>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-success btn-lg">Login</button>
                </div>
            </form>

            <div class="text-center mt-4">
                <p>Don't have an account? 
                    <a href="index.php" class="text-success">Register here</a>
                </p>
                <p><a href="forgot_password.php" class="text-muted small">Forgot Password?</a></p>
            </div>
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i> ' : '<i class="bi bi-eye-slash"></i> Hide';
        });
    </script>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>