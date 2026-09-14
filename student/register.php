<?php
// ====================== LOAD CONFIG & OTP FUNCTION ======================
// config.php starts the session (guarded) and builds $pdo.
require_once '../includes/config.php';
require_once '../includes/fees.php';
require_once 'send_otp.php';

$message = "";
$errors  = [];

// Fees are set by the admin in admin/payment_settings.php. Read once here, on
// the server: process_payment.php compares what Paystack actually collected
// against pending_registrations.amount, so the button label and the stored
// amount must never drift apart, and neither may come from the browser.
$FEES = get_fees($pdo);

// Sessions a student may pick. The POSTed value is validated against this list
// so a crafted request can't store an arbitrary string.
$allowed_sessions = ['2023/2024', '2024/2025', '2025/2026'];

/** Render the session <option> list, defaulting to DEFAULT_SESSION. */
function session_options(array $allowed): string {
    $html = '<option value="">-- Select Academic Session --</option>';
    foreach ($allowed as $s) {
        $sel   = ($s === DEFAULT_SESSION) ? ' selected' : '';
        $esc   = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $html .= "<option value=\"{$esc}\"{$sel}>{$esc}</option>";
    }
    return $html;
}

// ====================== PASSPORT UPLOAD FUNCTION ======================
function uploadPassport($fileKey, $label = 'Passport photo') {
    global $errors;

    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "$label is required.";
        return false;
    }

    $file    = $_FILES[$fileKey];
    $allowed = ['jpg', 'jpeg', 'png'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        $errors[] = "$label must be a JPG, JPEG or PNG file.";
        return false;
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        $errors[] = "$label must be less than 5MB.";
        return false;
    }

    // Check the file really is an image, not just something named .jpg.
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
        $errors[] = "$label is not a valid image file.";
        return false;
    }

    $new_filename = "pass_" . uniqid('', true) . "." . $ext;
    $upload_dir   = UPLOAD_PASSPORT_DIR;   // absolute; see includes/config.php
    $upload_path  = $upload_dir . $new_filename;

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $new_filename;   // filename only — views resolve it via passport_url()
    }

    $errors[] = "Failed to upload $label.";
    return false;
}

/** Remove an already-uploaded passport when a later step in the same form fails. */
function discardPassport($filename) {
    if ($filename && is_file(UPLOAD_PASSPORT_DIR . $filename)) {
        @unlink(UPLOAD_PASSPORT_DIR . $filename);
    }
}

/**
 * A matric number that is already sitting in pending_registrations belongs to
 * someone mid-payment. students.matric_no is UNIQUE, so letting a second person
 * pay for the same matric means they get debited and then fail to register.
 */
function matricTaken(PDO $pdo, string $matric): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM students WHERE matric_no = ? LIMIT 1");
    $stmt->execute([$matric]);
    if ($stmt->fetchColumn()) {
        return true;
    }

    // JSON_EXTRACT needs MySQL 5.7+/MariaDB 10.2+. If the server can't run it,
    // fall back rather than blocking every registration on the site.
    try {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM pending_registrations
             WHERE (matric_no = ? OR JSON_UNQUOTE(JSON_EXTRACT(pair_data, '$.matric_no2')) = ?)
               AND status = 'pending_payment'
               AND created_at > (NOW() - INTERVAL 1 HOUR)
             LIMIT 1"
        );
        $stmt->execute([$matric, $matric]);
    } catch (Throwable $e) {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM pending_registrations
             WHERE matric_no = ? AND status = 'pending_payment'
               AND created_at > (NOW() - INTERVAL 1 HOUR)
             LIMIT 1"
        );
        $stmt->execute([$matric]);
    }

    return (bool) $stmt->fetchColumn();
}

/**
 * Confirm a department exists AND admits the level being registered.
 *
 * The form only lists the departments for the chosen tab, but the select is
 * client-side: a posted department_id still has to be checked here, or an ND
 * student could be filed into an HND-only department.
 */
function departmentAllowsLevel(PDO $pdo, int $id, string $level): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM departments WHERE id = ? AND level = ? LIMIT 1");
    $stmt->execute([$id, $level]);
    return (bool) $stmt->fetchColumn();
}

// ====================== HANDLE FORM SUBMISSION ======================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $errors = [];

    // ==================== HND REGISTRATION ====================
    if (isset($_POST['register_hnd'])) {
        $level         = 'HND';
        $department_id = intval($_POST['department_id'] ?? 0);
        $session       = trim($_POST['session'] ?? '');
        $address       = trim($_POST['address'] ?? '');

        $matric_no = strtoupper(trim($_POST['matric_no'] ?? ''));
        $name      = trim($_POST['name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $contact   = trim($_POST['contact'] ?? '');
        $password  = $_POST['password'] ?? '';

        if ($matric_no === '' || $name === '' || $email === '' || $contact === '' || $password === '' || $address === '') {
            $errors[] = "All fields are required for HND registration.";
        }

        if (!in_array($session, $allowed_sessions, true)) {
            $errors[] = "Please select a valid academic session.";
        }

        if ($department_id <= 0 || !departmentAllowsLevel($pdo, $department_id, $level)) {
            $errors[] = "Please select a department that offers $level.";
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }

        if ($password !== '' && strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }

        if ($matric_no !== '' && matricTaken($pdo, $matric_no)) {
            $errors[] = "This Matriculation number is already registered or awaiting payment.";
        }

        if (empty($errors)) {
            $passport_path = uploadPassport('passport', 'Passport photo');

            if ($passport_path) {
                $hash    = password_hash($password, PASSWORD_DEFAULT);
                $temp_id = "HND_" . time() . rand(1000, 9999);

                $stmt = $pdo->prepare("INSERT INTO pending_registrations
                    (temp_id, level, department_id, session, address, matric_no, name, email, contact,
                     password_hash, passport, amount, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_payment')");

                try {
                    $stmt->execute([$temp_id, $level, $department_id, $session, $address,
                                    $matric_no, $name, $email, $contact, $hash, $passport_path, $FEES['HND']]);

                    $_SESSION['pending_temp_id'] = $temp_id;
                    header("Location: initialize_payment.php?temp_id=" . urlencode($temp_id));
                    exit;
                } catch (Throwable $e) {
                    discardPassport($passport_path);
                    $errors[] = "Failed to start registration process. Please try again.";
                }
            }
        }
    }

    // ==================== ND PAIR REGISTRATION ====================
    elseif (isset($_POST['register_nd'])) {
        $level         = 'ND';
        $nd_type       = strtoupper(trim($_POST['nd_type'] ?? ''));
        $department_id = intval($_POST['department_id'] ?? 0);
        $session       = trim($_POST['session'] ?? '');
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

        // The ND branch previously validated almost nothing, so a half-empty pair
        // could reach Paystack and only fail after the students had paid.
        if ($matric_no1 === '' || $name1 === '' || $email1 === '' || $contact1 === '' || $password1 === '') {
            $errors[] = "All fields are required for Student 1.";
        }

        if ($matric_no2 === '' || $name2 === '' || $email2 === '' || $contact2 === '' || $password2 === '') {
            $errors[] = "All fields are required for Student 2.";
        }

        if ($address === '') {
            $errors[] = "Address is required.";
        }

        if (!in_array($nd_type, ['FT', 'DPT'], true)) {
            $errors[] = "Please select a valid ND type.";
        }

        if (!in_array($session, $allowed_sessions, true)) {
            $errors[] = "Please select a valid academic session.";
        }

        if ($department_id <= 0 || !departmentAllowsLevel($pdo, $department_id, $level)) {
            $errors[] = "Please select a department that offers $level.";
        }

        if ($email1 !== '' && !filter_var($email1, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Student 1's email address is not valid.";
        }

        if ($email2 !== '' && !filter_var($email2, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Student 2's email address is not valid.";
        }

        if ($password1 !== '' && strlen($password1) < 6) {
            $errors[] = "Student 1's password must be at least 6 characters.";
        }

        if ($password2 !== '' && strlen($password2) < 6) {
            $errors[] = "Student 2's password must be at least 6 characters.";
        }

        if ($matric_no1 !== '' && $matric_no1 === $matric_no2) {
            $errors[] = "Both students cannot have the same matric number.";
        }

        if ($matric_no1 !== '' && matricTaken($pdo, $matric_no1)) {
            $errors[] = "Matric number 1 is already registered or awaiting payment.";
        }

        if ($matric_no2 !== '' && $matric_no2 !== $matric_no1 && matricTaken($pdo, $matric_no2)) {
            $errors[] = "Matric number 2 is already registered or awaiting payment.";
        }

        if (empty($errors)) {
            $passport1 = uploadPassport('passport1', "Student 1's passport photo");
            $passport2 = uploadPassport('passport2', "Student 2's passport photo");

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
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_payment')");

                try {
                    $stmt->execute([$temp_id, $level, $nd_type, $department_id, $session, $address,
                                    $matric_no1, $name1, $email1, $contact1, $hash1, $passport1,
                                    $pair_data, $FEES['ND']]);

                    $_SESSION['pending_temp_id'] = $temp_id;
                    header("Location: initialize_payment.php?temp_id=" . urlencode($temp_id));
                    exit;
                } catch (Throwable $e) {
                    discardPassport($passport1);
                    discardPassport($passport2);
                    $errors[] = "Failed to start registration process. Please try again.";
                }
            } else {
                // One upload succeeded and the other didn't — don't leave the
                // orphan behind for every retry.
                discardPassport($passport1);
                discardPassport($passport2);
            }
        }
    }

    // Display errors
    if (!empty($errors)) {
        $message = "<div class='alert alert-danger'><ul>";
        foreach ($errors as $err) {
            $message .= "<li>" . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . "</li>";
        }
        $message .= "</ul></div>";
    }
}

// Load departments once, split by level: each tab only offers the departments
// that admit that level (Computer Science is ND only, the rest are HND only).
$departments_by_level = ['ND' => [], 'HND' => []];
foreach ($pdo->query("SELECT id, name, level FROM departments ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) as $d) {
    if (isset($departments_by_level[$d['level']])) {
        $departments_by_level[$d['level']][] = $d;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School of Computing - Student Registration &amp; Login</title>
    <link rel="shortcut icon" href="https://ik.imagekit.io/emblem/NNL.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .form-control, .form-select { border-radius: 10px; }
        .btn-success { border-radius: 10px; padding: 12px 30px; font-weight: 600; }
        .nav-tabs .nav-link { border-radius: 10px 10px 0 0; font-weight: 600; }
        .section-title { font-size: 1.1rem; font-weight: 700; color: rgb(18, 194, 150); }
        .pair-card { border: 2px solid rgb(13, 253, 181); border-radius: 12px; background: #f8f9fa; }
    </style>
</head>
<body class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <div class="text-center mb-4">
                    <h1 class="display-5 fw-bold text-success">School of Computing Project Repository</h1>
                    <p class="lead text-muted">ND &amp; HND Student Registration Portal</p>
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
                                            <?php if (empty($departments_by_level['HND'])): ?>
                                                <select class="form-select" disabled>
                                                    <option>No HND department available - contact admin</option>
                                                </select>
                                            <?php else: ?>
                                            <select class="form-select" name="department_id" required>
                                                <option value="">-- Select Department --</option>
                                                <?php foreach ($departments_by_level['HND'] as $dept): ?>
                                                    <option value="<?= (int) $dept['id'] ?>"><?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Session <span class="text-danger">*</span></label>
                                            <select class="form-select" name="session" required>
                                                <?= session_options($allowed_sessions) ?>
                                            </select>
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
                                            <input type="text" class="form-control" name="matric_no" placeholder="e.g. CS20240101207" required minlength="10" maxlength="15">
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
                                            <input type="tel" class="form-control" name="contact" placeholder="080xxxxxxxxx" required maxlength="11" pattern="\d{11}" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')" >
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Password <span class="text-danger">*</span></label>
                                            <input type="password" class="form-control" name="password" minlength="6" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Passport Photo <span class="text-danger">*</span></label>
                                            <input type="file" class="form-control" name="passport" accept="image/jpeg,image/png" required>
                                        </div>
                                    </div>

                                    <div class="text-center mt-5">
                                        <button type="submit" class="btn btn-success btn-lg px-5">Pay &amp; Register (&#8358;<?= number_format($FEES['HND']) ?>)</button>
                                    </div>
                                </form>
                                <div class="text-center">
                                    <p>Already Have An Account?
                                        <a href="index.php" class="text-success fw-bold">Login here</a>
                                    </p>
                                </div>
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
                                            <?php if (empty($departments_by_level['ND'])): ?>
                                                <select class="form-select" disabled>
                                                    <option>No ND department available - contact admin</option>
                                                </select>
                                            <?php else: ?>
                                            <select class="form-select" name="department_id" required>
                                                <option value="">-- Select Department --</option>
                                                <?php foreach ($departments_by_level['ND'] as $dept): ?>
                                                    <option value="<?= (int) $dept['id'] ?>"><?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-12">
                                            <label class="form-label fw-bold">Session <span class="text-danger">*</span></label>
                                            <select class="form-select" name="session" required>
                                                <?= session_options($allowed_sessions) ?>
                                            </select>
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
                                                    <input type="text" class="form-control" name="matric_no1" placeholder="e.g. CS20240101207" required minlength="10" maxlength="15">
                                                </div>
                                                <div class="mb-3"><label class="form-label">Full Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="name1" required></div>
                                                <div class="mb-3"><label class="form-label">Email <span class="text-danger">*</span></label><input type="email" class="form-control" name="email1" required></div>
                                                <div class="mb-3"><label class="form-label">Phone Contact <span class="text-danger">*</span></label><input type="tel" class="form-control" name="contact1" required maxlength="11" pattern="\d{11}" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')"></div>
                                                <div class="mb-3"><label class="form-label">Password <span class="text-danger">*</span></label><input type="password" class="form-control" name="password1" minlength="6" required></div>
                                                <div><label class="form-label">Passport Photo <span class="text-danger">*</span></label><input type="file" class="form-control" name="passport1" accept="image/jpeg,image/png" required></div>
                                            </div>
                                        </div>

                                        <!-- Student 2 -->
                                        <div class="col-lg-6 mb-4">
                                            <div class="pair-card p-4">
                                                <h6 class="text-success mb-3">Student 2</h6>
                                                <div class="mb-3">
                                                    <label class="form-label">Matric No <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="matric_no2" placeholder="e.g. OT20241010" required minlength="10" maxlength="15">
                                                </div>
                                                <div class="mb-3"><label class="form-label">Full Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="name2" required></div>
                                                <div class="mb-3"><label class="form-label">Email <span class="text-danger">*</span></label><input type="email" class="form-control" name="email2" required></div>
                                                <div class="mb-3"><label class="form-label">Phone Contact <span class="text-danger">*</span></label><input type="tel" class="form-control" name="contact2" required maxlength="11" pattern="\d{11}" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')"></div>
                                                <div class="mb-3"><label class="form-label">Password <span class="text-danger">*</span></label><input type="password" class="form-control" name="password2" minlength="6" required></div>
                                                <div><label class="form-label">Passport Photo <span class="text-danger">*</span></label><input type="file" class="form-control" name="passport2" accept="image/jpeg,image/png" required></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center mt-5">
                                        <button type="submit" class="btn btn-success btn-lg px-5">Pay &amp; Register (&#8358;<?= number_format($FEES['ND']) ?>)</button>
                                    </div>
                                </form>
                                <div class="text-center">
                                    <p>Already Have An Account?
                                        <a href="index.php" class="text-success fw-bold">Login here</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/../includes/password_toggle.php'; ?>
</body>
</html>