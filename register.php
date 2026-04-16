<?php
// ====================== SAFE SESSION START ======================
if (!isset($_SESSION)) {
    
}

// ====================== LOAD CONFIG & OTP FUNCTION ======================
require_once 'config.php';
require_once 'send_otp.php';

$message = "";

// ====================== PASSPORT UPLOAD FUNCTION ======================
function uploadPassport($fileKey) {
    global $errors;
    $target_dir = UPLOAD_PASSPORT_DIR;
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Passport photo is required.";
        return false;
    }

    $file = $_FILES[$fileKey];
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        $errors[] = "Invalid image file.";
        return false;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
        $errors[] = "Only JPG, JPEG and PNG passport photos allowed.";
        return false;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        $errors[] = "Passport photo must not exceed 2MB.";
        return false;
    }

    $new_name = "pass_" . time() . "_" . rand(10000, 99999) . "." . $ext;
    $target_file = $target_dir . $new_name;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    }
    $errors[] = "Failed to upload passport.";
    return false;
}

// ====================== HANDLE FORM SUBMISSION ======================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $errors = [];

    // ==================== HND REGISTRATION ====================
    if (isset($_POST['register_hnd'])) {
        $level         = 'HND';
        $nd_type       = null;
        $department_id = intval($_POST['department_id'] ?? 0);
        $session       = trim($_POST['session'] ?? DEFAULT_SESSION);
        $address       = trim($_POST['address'] ?? '');

        $matric_no = strtoupper(trim($_POST['matric_no'] ?? ''));
        $name      = trim($_POST['name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $contact   = trim($_POST['contact'] ?? '');
        $password  = $_POST['password'] ?? '';

        if (empty($matric_no) || empty($name) || empty($email) || empty($contact) || empty($password) || $department_id <= 0) {
            $errors[] = "All fields are required for HND registration.";
        }

        // Check duplicate matric
        $stmt = $pdo->prepare("SELECT id FROM students WHERE matric_no = ?");
        $stmt->execute([$matric_no]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "This Matriculation number already exists.";
        }

        if (empty($errors)) {
            $passport_path = uploadPassport('passport');

            if ($passport_path) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $temp_id = "HND_" . time() . rand(1000, 9999);

                $stmt = $pdo->prepare("INSERT INTO pending_registrations 
                    (temp_id, level, department_id, session, address, matric_no, name, email, contact, 
                     password_hash, passport, amount, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 2000.00, 'pending_payment')");

                if ($stmt->execute([$temp_id, $level, $department_id, $session, $address, 
                                   $matric_no, $name, $email, $contact, $hash, $passport_path])) {
                    
                    $_SESSION['pending_temp_id'] = $temp_id;
                    header("Location: initialize_payment.php?temp_id=" . urlencode($temp_id));
                    exit;
                } else {
                    $errors[] = "Failed to start registration process.";
                }
            }
        }
    }

    // ==================== ND PAIR REGISTRATION ====================
    elseif (isset($_POST['register_nd'])) {
        $level         = 'ND';
        $nd_type       = strtoupper(trim($_POST['nd_type'] ?? ''));
        $department_id = intval($_POST['department_id'] ?? 0);
        $session       = trim($_POST['session'] ?? DEFAULT_SESSION);
        $address       = trim($_POST['address'] ?? '');

        $matric_no1 = strtoupper(trim($_POST['matric_no1'] ?? ''));
        $name1      = trim($_POST['name1'] ?? '');
        $email1     = trim($_POST['email1'] ?? '');
        $contact1   = trim($_POST['contact1'] ?? '');
        $password1  = $_POST['password1'] ?? '';

        $matric_no2 = strtoupper(trim($_POST['matric_no2'] ?? ''));
        $name2      = trim($_POST['name2'] ?? '');
        $email2     = trim($_POST['email2'] ?? '');
        $contact2   = trim($_POST['contact2'] ?? '');
        $password2  = $_POST['password2'] ?? '';

        if ($matric_no1 === $matric_no2) {
            $errors[] = "Both students cannot have the same matric number.";
        }

        if ($department_id <= 0) {
            $errors[] = "Please select a department.";
        }

        // Check duplicates
        $stmt = $pdo->prepare("SELECT id FROM students WHERE matric_no = ?");
        $stmt->execute([$matric_no1]);
        if ($stmt->rowCount() > 0) $errors[] = "Matric number 1 already exists.";
        $stmt->execute([$matric_no2]);
        if ($stmt->rowCount() > 0) $errors[] = "Matric number 2 already exists.";

        if (empty($errors)) {
            $passport1 = uploadPassport('passport1');
            $passport2 = uploadPassport('passport2');

            if ($passport1 && $passport2) {
                $hash1 = password_hash($password1, PASSWORD_DEFAULT);
                $hash2 = password_hash($password2, PASSWORD_DEFAULT);

                $temp_id = "ND_" . time() . rand(1000, 9999);

                $pair_data = json_encode([
                    'matric_no2'      => $matric_no2,
                    'name2'           => $name2,
                    'email2'          => $email2,
                    'contact2'        => $contact2,
                    'password_hash2'  => $hash2,
                    'passport2'       => $passport2
                ]);

                $stmt = $pdo->prepare("INSERT INTO pending_registrations 
                    (temp_id, level, nd_type, department_id, session, address, matric_no, name, email, 
                     contact, password_hash, passport, pair_data, amount, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 4000.00, 'pending_payment')");

                if ($stmt->execute([$temp_id, $level, $nd_type, $department_id, $session, $address, 
                                   $matric_no1, $name1, $email1, $contact1, $hash1, $passport1, 
                                   $pair_data])) {
                    
                    $_SESSION['pending_temp_id'] = $temp_id;
                    header("Location: initialize_payment.php?temp_id=" . urlencode($temp_id));
                    exit;
                } else {
                    $errors[] = "Failed to start registration process.";
                }
            }
        }
    }

    // Display errors
    if (!empty($errors)) {
        $message = "<div class='alert alert-danger'><ul>";
        foreach ($errors as $err) {
            $message .= "<li>" . htmlspecialchars($err) . "</li>";
        }
        $message .= "</ul></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NACOS FPE - Student Registration & Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .form-control, .form-select { border-radius: 10px; }
        .btn-success { border-radius: 10px; padding: 12px 30px; font-weight: 600; }
        .nav-tabs .nav-link { border-radius: 10px 10px 0 0; font-weight: 600; }
        .section-title { font-size: 1.1rem; font-weight: 700; color:rgb(18, 194, 150); }
        .pair-card { border: 2px solidrgb(13, 253, 181); border-radius: 12px; background: #f8f9fa; }
    </style>
</head>
<body class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <div class="text-center mb-4">
                    <h1 class="display-5 fw-bold text-success">NACOS FPE Project Repository</h1>
                    <p class="lead text-muted">ND & HND Student Registration Portal</p>
                </div>

                <ul class="nav nav-tabs mb-4 justify-content-center" id="mainTabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" id="hnd-tab" data-bs-toggle="tab" data-bs-target="#hnd">HND Registration</button></li>
                    <li class="nav-item"><button class="nav-link" id="nd-tab" data-bs-toggle="tab" data-bs-target="#nd">ND Pair Registration</button></li>
                    
                </ul>

                <div class="tab-content">

                    <!-- HND FORM -->
                    <div class="tab-pane fade show active" id="hnd">
                        <div class="card">
                            <div class="card-body p-5">
                                <h4 class="card-title mb-4 text-center">HND Student Registration</h4>
                                <?= $message ?>

                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="register_hnd" value="1">

                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Department <span class="text-danger">*</span></label>
                                            <select class="form-select" name="department_id" required>
                                                <?php
                                                $stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
                                                while ($dept = $stmt->fetch()) {
                                                    echo "<option value='{$dept['id']}'>{$dept['name']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Session <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="session" value="<?= htmlspecialchars(DEFAULT_SESSION ?? '2024/2025') ?>" required>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Address <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="address" rows="2" required></textarea>
                                    </div>

                                    <h5 class="section-title border-bottom pb-2 mb-3">HND Student Information</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Matric No <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="matric_no" placeholder="e.g. CS20240101207" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="name" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" name="email" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone Contact <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="contact" placeholder="080xxxxxxxxx" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" name="password" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Passport Photo <span class="text-danger">*</span></label>
                                            <input type="file" class="form-control" name="passport" accept="image/jpeg,image/png" required>
                                        </div>
                                    </div>

                                    <div class="text-center mt-5">
                                        <button type="submit" class="btn btn-success btn-lg px-5">Pay & Register HND (₦2,000)</button>
                                    </div>
                                </form>
                                <div class="text-center ">
                                <p>Already Have An Account? 
                    <a href="login.php" class="text-success fw-bold">Login here</a>
                </p></div>
                            </div>
                        </div>
                    </div>

                    <!-- ND FORM -->
                    <div class="tab-pane fade" id="nd">
                        <div class="card">
                            <div class="card-body p-5">
                                <h4 class="card-title mb-4 text-center">ND Pair Registration</h4>
                                <?= $message ?>

                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="register_nd" value="1">

                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">ND Type <span class="text-danger">*</span></label>
                                            <select class="form-select" name="nd_type" required>
                                                <option value="FT">Full Time (FT)</option>
                                                <option value="DPT">Part Time (DPT)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Department <span class="text-danger">*</span></label>
                                            <select class="form-select" name="department_id" required>
                                                <?php
                                                $stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
                                                while ($dept = $stmt->fetch()) {
                                                    echo "<option value='{$dept['id']}'>{$dept['name']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label class="form-label fw-bold">Session <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="session" value="<?= htmlspecialchars(DEFAULT_SESSION ?? '2024/2025') ?>" required>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Address <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="address" rows="2" required></textarea>
                                    </div>

                                    <h5 class="section-title border-bottom pb-2 mb-3">ND Pair Registration</h5>
                                    <div class="row">
                                        <!-- Student 1 -->
                                        <div class="col-lg-6 mb-4">
                                            <div class="pair-card p-4">
                                                <h6 class="text-success mb-3">Student 1</h6>
                                                <div class="mb-3">
                                                    <label class="form-label">Matric No <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="matric_no1" placeholder="e.g. CS20240101207" required>
                                                </div>
                                                <div class="mb-3"><label class="form-label">Full Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="name1" required></div>
                                                <div class="mb-3"><label class="form-label">Email <span class="text-danger">*</span></label><input type="email" class="form-control" name="email1" required></div>
                                                <div class="mb-3"><label class="form-label">Phone Contact <span class="text-danger">*</span></label><input type="text" class="form-control" name="contact1" required></div>
                                                <div class="mb-3"><label class="form-label">Password <span class="text-danger">*</span></label><input type="password" class="form-control" name="password1" required></div>
                                                <div><label class="form-label">Passport Photo <span class="text-danger">*</span></label><input type="file" class="form-control" name="passport1" accept="image/jpeg,image/png" required></div>
                                            </div>
                                        </div>

                                        <!-- Student 2 -->
                                        <div class="col-lg-6 mb-4">
                                            <div class="pair-card p-4">
                                                <h6 class="text-success mb-3">Student 2</h6>
                                                <div class="mb-3">
                                                    <label class="form-label">Matric No <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="matric_no2" placeholder="e.g. OT20241010" required>
                                                </div>
                                                <div class="mb-3"><label class="form-label">Full Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="name2" required></div>
                                                <div class="mb-3"><label class="form-label">Email <span class="text-danger">*</span></label><input type="email" class="form-control" name="email2" required></div>
                                                <div class="mb-3"><label class="form-label">Phone Contact <span class="text-danger">*</span></label><input type="text" class="form-control" name="contact2" required></div>
                                                <div class="mb-3"><label class="form-label">Password <span class="text-danger">*</span></label><input type="password" class="form-control" name="password2" required></div>
                                                <div><label class="form-label">Passport Photo <span class="text-danger">*</span></label><input type="file" class="form-control" name="passport2" accept="image/jpeg,image/png" required></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center mt-5">
                                        <button type="submit" class="btn btn-success btn-lg px-5">Pay & Register ND Pair (₦4,000)</button>
                                    </div>
                                             <div class="text-center ">
                                <p>Already Have An Account? 
                    <a href="login.php" class="text-success fw-bold">Login here</a>
                </p></div>
                                </form>
                            </div>
                        </div>
                    </div>

                  
                
                

                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>