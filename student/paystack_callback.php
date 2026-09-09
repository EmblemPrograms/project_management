<?php
// paystack_callback.php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if (isset($_GET['reference'])) {
    $reference = $_GET['reference'];
    $user_id = $_SESSION['user_id'];

    // ---------- Verify payment with Paystack before trusting it ----------
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY
        ],
    ] + paystack_ssl_opts());

    $response = curl_exec($curl);
    $curl_err = curl_error($curl);
    curl_close($curl);

    $result = json_decode($response, true);

    $verified = is_array($result)
        && !empty($result['status']) && $result['status'] === true
        && isset($result['data']['status']) && $result['data']['status'] === 'success';

    if (!$verified) {
        $error = $curl_err
            ? "Could not reach Paystack to verify payment. Please try again."
            : "Payment could not be verified. If you were debited, contact admin with reference: " . htmlspecialchars($reference);
    } else {
        try {
            // Update student payment status only after successful verification
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
            header("Location: submission.php");
            exit;

        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
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
    <link rel="shortcut icon" href="https://ik.imagekit.io/emblem/NNL.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body text-center p-5">
            <h3 class="text-danger">Payment Processing Error</h3>
            <p><?= htmlspecialchars($error) ?></p>
            <a href="dashboard.php" class="btn btn-success">Go to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>