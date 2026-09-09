<?php
// password_reset.php - Shared OTP password reset logic.
//
// Both student/forgot_password.php and admin/forgot_password.php are thin
// controllers over this file. The rules that matter for security — hashed
// codes, expiry, attempt caps, single use — live here once so the two flows
// cannot drift apart.

require_once __DIR__ . '/mailer.php';

const RESET_OTP_TTL_MINUTES = 30;   // matches the registration OTP lifetime
const RESET_MAX_ATTEMPTS    = 5;    // wrong codes before the request is voided
const RESET_RESEND_COOLDOWN = 60;   // seconds between "send another code"

/**
 * The accounts that can be reset.
 *
 * Table and column names are interpolated into SQL below, so this whitelist is
 * the only thing allowed to supply them — never a request value.
 *
 *   by_matric: students are found by matric no OR email; admins by email only.
 */
const RESET_ACCOUNTS = [
    'student' => [
        'table'     => 'students',
        'name_col'  => 'name',
        'email_col' => 'email',
        'by_matric' => true,
        'login_url' => 'index.php',
        'noun'      => 'Matric No or Email',
    ],
    'admin' => [
        'table'     => 'admins',
        'name_col'  => 'full_name',
        'email_col' => 'email',
        'by_matric' => false,
        'login_url' => 'index.php',
        'noun'      => 'Email',
    ],
];

function reset_config(string $type): array {
    if (!isset(RESET_ACCOUNTS[$type])) {
        throw new InvalidArgumentException("Unknown reset account type: $type");
    }
    return RESET_ACCOUNTS[$type];
}

// ---------------------------------------------------------------------------
// Session state. Student and admin resets are namespaced separately so that
// one browser can hold both without either clobbering the other.
// ---------------------------------------------------------------------------

function reset_state(string $type): array {
    return $_SESSION['pwreset'][$type] ?? [];
}

function reset_state_set(string $type, array $values): void {
    $_SESSION['pwreset'][$type] = $values + ($_SESSION['pwreset'][$type] ?? []);
}

function reset_state_clear(string $type): void {
    unset($_SESSION['pwreset'][$type]);
}

/** Which of the three screens to render. */
function reset_step(string $type): string {
    $state = reset_state($type);
    if (!empty($state['verified'])) return 'reset';
    return isset($state['account_id']) ? 'verify' : 'request';
}

// ---------------------------------------------------------------------------
// CSRF. A token guards the POST screens that act on a live reset row.
// ---------------------------------------------------------------------------

function reset_csrf_token(string $type): string {
    $state = reset_state($type);
    if (empty($state['csrf'])) {
        reset_state_set($type, ['csrf' => bin2hex(random_bytes(32))]);
        $state = reset_state($type);
    }
    return $state['csrf'];
}

function reset_csrf_ok(string $type): bool {
    $state = reset_state($type);
    return !empty($state['csrf'])
        && isset($_POST['csrf'])
        && hash_equals($state['csrf'], (string) $_POST['csrf']);
}

// ---------------------------------------------------------------------------
// Account lookup and code issuing
// ---------------------------------------------------------------------------

/**
 * Finds the account a reset was requested for, or null.
 * Returns a normalised array: id, name, email.
 */
function reset_find_account(PDO $pdo, string $type, string $identifier): ?array {
    $cfg = reset_config($type);

    $sql = "SELECT id, {$cfg['name_col']} AS name, {$cfg['email_col']} AS email
            FROM {$cfg['table']}
            WHERE {$cfg['email_col']} = ?";
    $params = [$identifier];

    if ($cfg['by_matric']) {
        $sql .= " OR matric_no = ?";
        $params[] = strtoupper($identifier);
    }
    $sql .= " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Voids any outstanding codes for the account, issues a fresh one and emails
 * it. Returns false only when the email itself could not be sent.
 */
function reset_issue_otp(PDO $pdo, string $type, array $account): bool {
    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $pdo->prepare("DELETE FROM password_resets WHERE account_type = ? AND account_id = ?")
        ->execute([$type, $account['id']]);

    $stmt = $pdo->prepare(
        "INSERT INTO password_resets (account_type, account_id, otp_hash, expires_at)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))"
    );
    $stmt->execute([
        $type,
        $account['id'],
        password_hash($otp, PASSWORD_DEFAULT),
        RESET_OTP_TTL_MINUTES,
    ]);

    reset_state_set($type, [
        'account_id' => (int) $account['id'],
        'email'      => $account['email'],
        'name'       => $account['name'],
        'sent_at'    => time(),
    ]);

    return sendPasswordResetOTP($account['email'], $otp, $account['name']);
}

/**
 * Checks a submitted code.
 * Returns ['ok' => bool, 'error' => string].
 */
function reset_verify_otp(PDO $pdo, string $type, string $submitted): array {
    $state   = reset_state($type);
    $entered = preg_replace('/[^0-9]/', '', $submitted);

    $stmt = $pdo->prepare(
        "SELECT id, otp_hash, attempts FROM password_resets
         WHERE account_type = ? AND account_id = ? AND used = 0 AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([$type, $state['account_id']]);
    $row = $stmt->fetch();

    if (!$row) {
        return ['ok' => false, 'error' => "That code has expired. Request a new one below."];
    }
    if ($row['attempts'] >= RESET_MAX_ATTEMPTS) {
        return ['ok' => false, 'error' => "Too many incorrect attempts. Request a new code below."];
    }

    if (password_verify($entered, $row['otp_hash'])) {
        // Burn the code now: it must not survive to a second use.
        $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?")->execute([$row['id']]);
        reset_state_set($type, ['verified' => true]);
        return ['ok' => true, 'error' => ''];
    }

    $pdo->prepare("UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?")
        ->execute([$row['id']]);

    $left = RESET_MAX_ATTEMPTS - ($row['attempts'] + 1);
    return ['ok' => false, 'error' => $left > 0
        ? "Incorrect code. {$left} attempt(s) remaining."
        : "Incorrect code. Too many attempts - request a new code below."];
}

/** Writes the new password and retires every code for the account. */
function reset_set_password(PDO $pdo, string $type, string $password): void {
    $cfg   = reset_config($type);
    $state = reset_state($type);

    $pdo->prepare("UPDATE {$cfg['table']} SET password_hash = ? WHERE id = ?")
        ->execute([password_hash($password, PASSWORD_DEFAULT), $state['account_id']]);

    $pdo->prepare("DELETE FROM password_resets WHERE account_type = ? AND account_id = ?")
        ->execute([$type, $state['account_id']]);

    reset_state_clear($type);
    session_regenerate_id(true);
}

/** Seconds still to wait before another code may be requested; 0 when ready. */
function reset_resend_wait(string $type): int {
    $state = reset_state($type);
    $since = time() - (int) ($state['sent_at'] ?? 0);
    return max(0, RESET_RESEND_COOLDOWN - $since);
}

/** Masks an address for display: adebayo@gmail.com -> ad*****@gmail.com */
function mask_email(string $email): string {
    $at = strpos($email, '@');
    if ($at === false || $at < 2) return $email;
    return substr($email, 0, 2) . str_repeat('*', max(3, $at - 2)) . substr($email, $at);
}
