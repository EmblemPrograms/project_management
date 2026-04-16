<?php
// ====================== SAFE SESSION START + OUTPUT BUFFERING ======================
if (!isset($_SESSION)) {
    
}

// Start output buffering to prevent "headers already sent" issues
ob_start();

require_once 'config.php'; 
require_once 'db.php';   // Make sure this has $pdo and PAYSTACK_SECRET_KEY
require_once 'send_otp.php';

$reference = $_GET['reference'] ?? '';

if (empty($reference)) {
    die("No transaction reference found.");
}

// ====================== VERIFY PAYMENT ======================
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . PAYSTACK_SECRET_KEY
    ],
    CURLOPT_SSL_VERIFYPEER => false,   // Remove after fixing SSL with cacert.pem
    CURLOPT_SSL_VERIFYHOST => false
]);

$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response, true);

if (!isset($result['status']) || $result['status'] !== true || 
    !isset($result['data']['status']) || $result['data']['status'] !== 'success') {
    
    ob_end_clean(); // Clear any output
    die("Payment verification failed. Please contact admin.<br><a href='index.php'>← Back to Registration</a>");
}

// ====================== GET PENDING REGISTRATION ======================
$temp_id = $_SESSION['pending_temp_id'] ?? '';

if (empty($temp_id)) {
    ob_end_clean();
    die("Session expired. Please register again.<br><a href='index.php'>← Back</a>");
}

$stmt = $pdo->prepare("SELECT * FROM pending_registrations WHERE temp_id = ? AND status = 'pending_payment'");
$stmt->execute([$temp_id]);
$pending = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pending) {
    ob_end_clean();
    die("Registration record not found.<br><a href='index.php'>← Back</a>");
}

// ====================== PROCESS REGISTRATION ======================
try {
    $pdo->beginTransaction();

    if ($pending['level'] === 'HND') {
        // HND Single
        $stmt = $pdo->prepare("INSERT INTO students 
            (matric_no, name, email, contact, session, address, department_id, password_hash, 
             role, passport, level, nd_type, approved) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'student', ?, ?, ?, 0)");

        $stmt->execute([
            $pending['matric_no'], $pending['name'], $pending['email'], $pending['contact'],
            $pending['session'], $pending['address'], $pending['department_id'],
            $pending['password_hash'], $pending['passport'], $pending['level'], $pending['nd_type']
        ]);
        $student_id = $pdo->lastInsertId();

    } else {
    // ND Pair Registration
    $pair_data = json_decode($pending['pair_data'], true);

    // Insert Student 1
    $stmt = $pdo->prepare("INSERT INTO students 
        (matric_no, name, email, contact, session, address, department_id, password_hash, 
         role, passport, level, nd_type, approved) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'student', ?, ?, ?, 1)");

    $stmt->execute([
        $pending['matric_no'], $pending['name'], $pending['email'], $pending['contact'],
        $pending['session'], $pending['address'], $pending['department_id'],
        $pending['password_hash'], $pending['passport'], $pending['level'], $pending['nd_type']
    ]);
    $student1_id = $pdo->lastInsertId();

    // Insert Student 2
    $stmt->execute([
        $pair_data['matric_no2'], $pair_data['name2'], $pair_data['email2'], $pair_data['contact2'],
        $pending['session'], $pending['address'], $pending['department_id'],
        $pair_data['password_hash2'], $pair_data['passport2'], $pending['level'], $pending['nd_type']
    ]);
    $student2_id = $pdo->lastInsertId();

    // Create Pair Record
    $stmt = $pdo->prepare("INSERT INTO nd_pairs (student1_id, student2_id) VALUES (?, ?)");
    $stmt->execute([$student1_id, $student2_id]);
    $pair_id = $pdo->lastInsertId();

    // Link both students to the pair
    $pdo->prepare("UPDATE students SET pair_id = ? WHERE id IN (?, ?)")
        ->execute([$pair_id, $student1_id, $student2_id]);

    // Mark pending registration as paid
    $pdo->prepare("UPDATE pending_registrations SET status = 'paid' WHERE temp_id = ?")
        ->execute([$temp_id]);

    // IMPORTANT: Use Student 1 as the main logged-in user for now
    $student_id = $student1_id;
}

    // Mark as paid
    $pdo->prepare("UPDATE pending_registrations SET status = 'paid' WHERE temp_id = ?")
        ->execute([$temp_id]);

    $pdo->commit();

    // ====================== SEND OTP ======================
    $otp = rand(100000, 999999);

    $_SESSION['reg_otp']        = $otp;
    $_SESSION['reg_email']      = $pending['email'];
    $_SESSION['reg_student_id'] = $student_id;
    $_SESSION['reg_name']       = $pending['name'];

    $sent = sendVerificationOTP($pending['email'], $otp, $pending['name']);

    // Clear buffer and redirect cleanly
    ob_end_clean();

    if ($sent) {
        header("Location: otp_verification.php");
        exit;
    } else {
        die("Payment successful, but OTP could not be sent. Contact admin.");
    }

} catch (Exception $e) {
    $pdo->rollBack();
    ob_end_clean();
    die("Error processing registration: " . htmlspecialchars($e->getMessage()));
}
?>