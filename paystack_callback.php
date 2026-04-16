<?php
// paystack_callback.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if (isset($_GET['reference'])) {
    $reference = $_GET['reference'];
    $user_id = $_SESSION['user_id'];

    // TODO: (Strongly Recommended) Verify payment with Paystack API here

    try {
        // Update student payment status
        $stmt = $pdo->prepare("UPDATE students SET 
            payment_status = 'paid', 
            payment_reference = ?, 
            payment_date = NOW() 
            WHERE id = ?");
        $stmt->execute([$reference, $user_id]);

        // Set session for OTP flow
        $_SESSION['pending_submission'] = true;
        $_SESSION['payment_reference'] = $reference;

        // Redirect to OTP verification
        header("Location: verify_otp_submission.php");
        exit;

    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
} else {
    $error = "No payment reference received from Paystack.";
}

// If something went wrong
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body text-center p-5">
            <h3 class="text-danger">Payment Processing Error</h3>
            <p><?= htmlspecialchars($error) ?></p>
            <a href="student_dashboard.php" class="btn btn-success">Go to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>