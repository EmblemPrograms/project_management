<?php
// ====================== PAYMENT CALLBACK ======================
// Paystack redirects the payer's browser here after checkout.
// config.php starts the session (guarded), defines PAYSTACK_* and builds $pdo.
// Do NOT also require db.php here — it overwrites $pdo with a second, weaker
// connection (emulated prepares) for no benefit.

ob_start(); // buffer so a stray notice can't break the redirect below

require_once '../includes/config.php';
require_once 'send_otp.php';

/** Show a message to the payer and stop. */
function payment_fail(string $msg): void {
    ob_end_clean();
    die($msg . "<br><a href='register.php'>← Back to Registration</a>");
}

$reference = $_GET['reference'] ?? '';

if ($reference === '') {
    payment_fail("No transaction reference found.");
}

// The reference we generated in initialize_payment.php. Without this check any
// visitor could paste a different successful reference from this merchant
// account into the URL and register without paying for THIS registration.
$expected_reference = $_SESSION['paystack_reference'] ?? '';

if ($expected_reference === '' || !hash_equals($expected_reference, $reference)) {
    payment_fail(
        "This payment could not be matched to your registration session.<br>"
        . "If you were debited, contact the admin with reference: "
        . htmlspecialchars($reference, ENT_QUOTES, 'UTF-8')
    );
}

$temp_id = $_SESSION['pending_temp_id'] ?? '';

if ($temp_id === '') {
    payment_fail("Session expired. Please register again.");
}

// ====================== VERIFY PAYMENT WITH PAYSTACK ======================
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
    ],
] + paystack_ssl_opts());

$response = curl_exec($curl);
$curl_err = curl_error($curl);
curl_close($curl);

// A network/TLS failure is NOT a failed payment. Telling a student who was just
// debited that their payment "failed" is the worst possible outcome here.
if ($response === false) {
    payment_fail(
        "We could not reach Paystack to confirm your payment.<br>"
        . "Do NOT pay again. Contact the admin with reference: "
        . htmlspecialchars($reference, ENT_QUOTES, 'UTF-8')
        . (defined('DEBUG_MODE') && DEBUG_MODE ? "<br><small>" . htmlspecialchars($curl_err, ENT_QUOTES, 'UTF-8') . "</small>" : "")
    );
}

$result = json_decode($response, true);

if (!isset($result['status']) || $result['status'] !== true
    || !isset($result['data']['status']) || $result['data']['status'] !== 'success') {

    payment_fail(
        "Payment verification failed. Please contact admin.<br>Reference: "
        . htmlspecialchars($reference, ENT_QUOTES, 'UTF-8')
    );
}

// ====================== GET PENDING REGISTRATION ======================
// Fetch without the status filter so we can tell "already processed" (a browser
// refresh of this page) apart from "no such record".
$stmt = $pdo->prepare("SELECT * FROM pending_registrations WHERE temp_id = ?");
$stmt->execute([$temp_id]);
$pending = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pending) {
    payment_fail("Registration record not found.");
}

// Refreshing this page must not create a second set of student rows.
if ($pending['status'] === 'paid') {
    ob_end_clean();
    header("Location: otp_verification.php");
    exit;
}

// Confirm the money actually received matches what this registration costs.
$expected_amount = (int) round(((float) $pending['amount']) * 100); // kobo
$paid_amount     = (int) ($result['data']['amount'] ?? 0);
$paid_currency   = $result['data']['currency'] ?? '';

if ($paid_currency !== 'NGN' || $paid_amount < $expected_amount) {
    payment_fail(
        "The amount received does not match the registration fee. Please contact admin.<br>Reference: "
        . htmlspecialchars($reference, ENT_QUOTES, 'UTF-8')
    );
}

// Record what Paystack actually collected, so the admin payments page can
// reconcile a student against a real transaction instead of guessing.
$paid_naira = $paid_amount / 100;
$paid_at    = !empty($result['data']['paid_at'])
    ? date('Y-m-d H:i:s', strtotime($result['data']['paid_at']))
    : date('Y-m-d H:i:s');

// ====================== PROCESS REGISTRATION ======================
try {
    $pdo->beginTransaction();

    if ($pending['level'] === 'HND') {
        // HND Single
                $stmt = $pdo->prepare("INSERT INTO students
            (matric_no, name, email, contact, session, address, department_id, password_hash,
             role, passport, level, nd_type, approved,
             payment_reference, payment_status, payment_amount, payment_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'student', ?, ?, ?, 0, ?, 'paid', ?, ?)");

        $stmt->execute([
            $pending['matric_no'], $pending['name'], $pending['email'], $pending['contact'],
            $pending['session'], $pending['address'], $pending['department_id'],
            $pending['password_hash'], $pending['passport'], $pending['level'], $pending['nd_type'],
            $reference, $paid_naira, $paid_at
        ]);
        $student_id = $pdo->lastInsertId();

    } else {
        // ND Pair Registration
        $pair_data = json_decode($pending['pair_data'], true);

        if (!is_array($pair_data)) {
            throw new RuntimeException('Pair registration data is missing or corrupt.');
        }

               // Insert Student 1
        // Both students share one reference — an ND pair is a single payment.
        $stmt = $pdo->prepare("INSERT INTO students
            (matric_no, name, email, contact, session, address, department_id, password_hash,
             role, passport, level, nd_type, approved,
             payment_reference, payment_status, payment_amount, payment_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'student', ?, ?, ?, 1, ?, 'paid', ?, ?)");

        $stmt->execute([
            $pending['matric_no'], $pending['name'], $pending['email'], $pending['contact'],
            $pending['session'], $pending['address'], $pending['department_id'],
            $pending['password_hash'], $pending['passport'], $pending['level'], $pending['nd_type'],
            $reference, $paid_naira, $paid_at
        ]);
        $student1_id = $pdo->lastInsertId();

        // Insert Student 2
        $stmt->execute([
            $pair_data['matric_no2'], $pair_data['name2'], $pair_data['email2'], $pair_data['contact2'],
            $pending['session'], $pending['address'], $pending['department_id'],
            $pair_data['password_hash2'], $pair_data['passport2'], $pending['level'], $pending['nd_type'],
            $reference, $paid_naira, $paid_at
        ]);
        $student2_id = $pdo->lastInsertId();

        // Create Pair Record
        $pdo->prepare("INSERT INTO nd_pairs (student1_id, student2_id) VALUES (?, ?)")
            ->execute([$student1_id, $student2_id]);
        $pair_id = $pdo->lastInsertId();

        // Link both students to the pair
        $pdo->prepare("UPDATE students SET pair_id = ? WHERE id IN (?, ?)")
            ->execute([$pair_id, $student1_id, $student2_id]);

        // IMPORTANT: Use Student 1 as the main logged-in user for now
        $student_id = $student1_id;
    }

    // Mark as paid (this was previously run twice in the ND branch)
    $pdo->prepare("UPDATE pending_registrations SET status = 'paid' WHERE temp_id = ?")
        ->execute([$temp_id]);

    $pdo->commit();

} catch (Throwable $e) {
    // rollBack() on an inactive transaction throws its own exception and turns a
    // handled error into a fatal one.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    payment_fail(
        "Your payment was received but registration could not be completed.<br>"
        . "Do NOT pay again. Contact the admin with reference: "
        . htmlspecialchars($reference, ENT_QUOTES, 'UTF-8')
        . (defined('DEBUG_MODE') && DEBUG_MODE ? "<br><small>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</small>" : "")
    );
}

// ====================== SEND OTP ======================
// Outside the try/commit block: the registration is already committed, so an
// SMTP failure must not trigger a rollback of work that succeeded.
$otp = rand(100000, 999999);

$_SESSION['reg_otp']        = $otp;
$_SESSION['reg_email']      = $pending['email'];
$_SESSION['reg_student_id'] = $student_id;
$_SESSION['reg_name']       = $pending['name'];

// This registration is complete — stop the reference being reusable.
unset($_SESSION['paystack_reference']);

$sent = false;
try {
    $sent = sendVerificationOTP($pending['email'], $otp, $pending['name']);
} catch (Throwable $e) {
    $sent = false;
}

ob_end_clean();

if ($sent) {
    header("Location: otp_verification.php");
    exit;
}

die("Payment successful and your registration was saved, but the OTP email could not be sent.<br>"
    . "Please contact the admin to have your account verified.");