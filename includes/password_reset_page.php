<?php
/**
 * password_reset_page.php - Controller + view for the OTP reset flow.
 *
 * Include this from a thin page that has already required config.php and set:
 *
 *   $reset_type       'student' or 'admin' (a key of RESET_ACCOUNTS)
 *   $reset_title      <title> text
 *   $reset_heading    label for the account being reset, e.g. "Admin"
 *
 * The flow is three screens in one page: ask for the account, verify the
 * emailed code, then set the password.
 */

require_once __DIR__ . '/password_reset.php';

$cfg   = reset_config($reset_type);
$type  = $reset_type;
$error = '';
$success = '';

$step = reset_step($type);

// ====================== START OVER ======================
if (isset($_GET['restart'])) {
    reset_state_clear($type);
    header("Location: forgot_password.php");
    exit;
}

// ====================== STEP 1: REQUEST A CODE ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_otp'])) {
    $identifier = trim((string) ($_POST['identifier'] ?? ''));

    if ($identifier === '') {
        $error = "Enter your " . $cfg['noun'] . ".";
    } else {
        $account = reset_find_account($pdo, $type, $identifier);

        // A found account advances to the code screen; anything else stops here
        // with a neutral message that neither confirms nor denies the account.
        //
        // Note this is not full enumeration resistance: an attacker can still
        // tell the two apart by which screen they land on. Hiding that means
        // sending a typo'd identifier to a code screen for an email that will
        // never arrive, which is a bad trade for users who mistype. If you do
        // want it, issue a decoy session here instead of falling through.
        if ($account && !empty($account['email'])) {
            reset_issue_otp($pdo, $type, $account);
            header("Location: forgot_password.php");
            exit;
        }

        $success = "If that account exists, a 6-digit reset code is on its way to "
                 . "the email address on file. Check your spam folder too.";
    }
}

// ====================== RESEND ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp']) && $step === 'verify') {
    if (!reset_csrf_ok($type)) {
        $error = "Your session expired. Please start again.";
    } elseif (($wait = reset_resend_wait($type)) > 0) {
        $error = "Please wait {$wait} more second(s) before requesting another code.";
    } else {
        $state   = reset_state($type);
        $account = reset_find_account($pdo, $type, $state['email']);

        if ($account && reset_issue_otp($pdo, $type, $account)) {
            $success = "A new reset code has been sent to your email.";
        } else {
            $error = "Failed to resend the code. Please try again.";
        }
    }
}

// ====================== STEP 2: VERIFY THE CODE ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp']) && $step === 'verify') {
    if (!reset_csrf_ok($type)) {
        $error = "Your session expired. Please start again.";
    } else {
        $result = reset_verify_otp($pdo, $type, (string) ($_POST['otp'] ?? ''));
        if ($result['ok']) {
            $step = 'reset';
        } else {
            $error = $result['error'];
        }
    }
}

// ====================== STEP 3: SET THE NEW PASSWORD ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_password']) && $step === 'reset') {
    if (!reset_csrf_ok($type)) {
        $error = "Your session expired. Please start again.";
    } else {
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['confirm_password'] ?? '');

        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm) {
            $error = "The two passwords do not match.";
        } else {
            reset_set_password($pdo, $type, $password);
            $_SESSION['success'] = "Your password has been reset. Please log in.";
            header("Location: " . $cfg['login_url']);
            exit;
        }
    }
}

$state  = reset_state($type);
$masked = isset($state['email']) ? mask_email($state['email']) : '';
$csrf   = reset_csrf_token($type);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= safe_output($reset_title) ?></title>
    <link rel="shortcut icon" href="https://ik.imagekit.io/emblem/NNL.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .card { max-width: 500px; margin: 100px auto; }
    </style>
</head>
<body>
<div class="container">
    <div class="card shadow">
        <div class="card-header bg-success text-white text-center py-3">
            <h4 class="mb-0">
                <?php if ($step === 'request'): ?>Forgot Your Password?
                <?php elseif ($step === 'verify'): ?>Enter Reset Code
                <?php else: ?>Set a New Password<?php endif; ?>
            </h4>
            <small class="d-block mt-1"><?= safe_output($reset_heading) ?> Account</small>
        </div>
        <div class="card-body p-5">

            <?php if ($success): ?>
                <div class="alert alert-success"><?= safe_output($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= safe_output($error) ?></div>
            <?php endif; ?>

            <?php if ($step === 'request'): ?>

                <p class="text-muted text-center mb-4">
                    Enter your <?= safe_output($cfg['noun']) ?>. We will send a
                    6-digit reset code to the email address on your account.
                </p>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label"><?= safe_output($cfg['noun']) ?>
                            <span class="text-danger">*</span></label>
                        <input type="text" name="identifier" class="form-control form-control-lg"
                               autofocus required>
                    </div>
                    <button type="submit" name="request_otp" value="1"
                            class="btn btn-success btn-lg w-100 mb-3">Send Reset Code</button>
                </form>

                <div class="text-center mt-4">
                    <a href="<?= safe_output($cfg['login_url']) ?>" class="text-muted">Back to Login</a>
                </div>

            <?php elseif ($step === 'verify'): ?>

                <p class="text-center mb-4">
                    A 6-digit reset code was sent to:<br>
                    <strong><?= safe_output($masked) ?></strong>
                </p>

                <form method="POST">
                    <input type="hidden" name="csrf" value="<?= safe_output($csrf) ?>">
                    <div class="mb-4">
                        <input type="text" name="otp" id="otp"
                               class="form-control form-control-lg text-center fs-1 fw-bold"
                               maxlength="6" placeholder="000000" inputmode="numeric"
                               autocomplete="one-time-code" autofocus required>
                    </div>
                    <button type="submit" name="verify_otp" value="1"
                            class="btn btn-success btn-lg w-100 mb-3">Verify Code</button>
                </form>

                <form method="POST">
                    <input type="hidden" name="csrf" value="<?= safe_output($csrf) ?>">
                    <button type="submit" name="resend_otp" value="1"
                            class="btn btn-outline-secondary w-100">Resend Code</button>
                </form>

                <small class="text-muted d-block text-center mt-4">
                    The code expires in <?= RESET_OTP_TTL_MINUTES ?> minutes.
                    Check your spam folder if it has not arrived.
                </small>

                <div class="text-center mt-3">
                    <a href="forgot_password.php?restart=1" class="text-muted">Use a different account</a>
                </div>

            <?php else: ?>

                <p class="text-muted text-center mb-4">
                    Code confirmed for <strong><?= safe_output($masked) ?></strong>.
                    Choose your new password.
                </p>

                <form method="POST">
                    <input type="hidden" name="csrf" value="<?= safe_output($csrf) ?>">
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control"
                               minlength="6" autocomplete="new-password" required>
                        <div class="form-text">At least 6 characters.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control"
                               minlength="6" autocomplete="new-password" required>
                    </div>
                    <button type="submit" name="set_password" value="1"
                            class="btn btn-success btn-lg w-100">Reset Password</button>
                </form>

            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require __DIR__ . '/password_toggle.php'; ?>
</body>
</html>
