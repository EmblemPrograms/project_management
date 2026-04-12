<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matric_no   = strtoupper(trim($_POST['matric_no']));
    $name        = trim($_POST['name']);
    $email       = trim($_POST['email']);
    $contact     = trim($_POST['contact']);
    $session     = trim($_POST['session']);
    $address     = trim($_POST['address']);
    $department_id = (int)$_POST['department_id'];
    $password    = $_POST['password'];
    $confirm     = $_POST['confirm_password'];

    if (empty($matric_no) || empty($name) || empty($email) || empty($contact) || empty($session) || empty($address) || $department_id <= 0) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!preg_match('/^SW2024\d{7,}$/', $matric_no)) {
        $error = "Matric No must start with SW2024 followed by at least 7 digits (e.g. SW20240113314)";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users 
            (matric_no, name, email, contact, session, address, department_id, password_hash, role, approved) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'student', 0)");

        try {
            $stmt->execute([$matric_no, $name, $email, $contact, $session, $address, $department_id, $password_hash]);
            $user_id = $pdo->lastInsertId();

            // Generate OTP for email verification
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires_at = date('Y-m-d H:i:s', strtotime('+ 2 hours')); // for testing

            $pdo->prepare("CREATE TABLE IF NOT EXISTS email_verification (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                otp VARCHAR(6) NOT NULL,
                expires_at DATETIME NOT NULL,
                used TINYINT(1) DEFAULT 0
            )")->execute();

            $stmt = $pdo->prepare("INSERT INTO email_verification (user_id, otp, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $otp, $expires_at]);

            // Send OTP via PHPMailer
            require_once 'send_otp.php';
            $send_result = sendVerificationOTP($email, $otp, $name);

            if ($send_result) {
                $_SESSION['verify_user_id'] = $user_id;
                $_SESSION['verify_email'] = $email;
                $_SESSION['verify_name'] = $name;
                header("Location: verify_email.php");
                exit;
            } else {
                $error = "Registration successful but failed to send verification email. Contact administrator.";
            }
        } catch (PDOException $e) {
            $error = "Matric No or Email already exists. Please use a different one.";
        }
    }
}

// Fetch departments
$stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOS FPE CHAPTER - Student Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        .register-card { max-width: 560px; margin: 40px auto; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .nacoss-header { background: linear-gradient(90deg, #28a745, #20c997); color: white; padding: 25px; border-radius: 15px 15px 0 0; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <div class="register-card card">
        <div class="nacoss-header">
            <h2>NACOS FPE CHAPTER</h2>
            <h5>Final Year Project Model Register</h5>
        </div>
        
        <div class="card-body p-4">
            <h4 class="text-center mb-4 text-success">Student Registration</h4>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php else: ?>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Matriculation Number <span class="text-danger">*</span></label>
                    <input type="text" name="matric_no" class="form-control" placeholder="e.g; SW20240110000" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Phone <span class="text-danger">*</span></label>
                        <input type="tel" name="contact" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Academic Session <span class="text-danger">*</span></label>
                        <select name="session" class="form-select" required>
                            <option value="">-- Select Session --</option>
                            <option value="2020/2021">2020/2021</option>
                            <option value="2021/2022">2021/2022</option>
                            <option value="2022/2023">2022/2023</option>
                            <option value="2023/2024">2023/2024</option>
                            <option value="2024/2025" selected>2024/2025</option>
                            <option value="2025/2026">2025/2026</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Department <span class="text-danger">*</span></label>
                    <select name="department_id" class="form-select" required>
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>"><?php echo safe_output($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Address <span class="text-danger">*</span></label>
                    <textarea name="address" class="form-control" rows="2" required></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-success btn-lg">Register & Verify Email</button>
                </div>
            </form>

            <div class="text-center mt-4">
                <p>Already have an account? <a href="login.php" class="text-success">Login here</a></p>
            </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>