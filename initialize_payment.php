<?php
if (!isset($_SESSION)) ;
require_once 'config.php';

$temp_id = $_GET['temp_id'] ?? '';

if (empty($temp_id) || !isset($_SESSION['pending_temp_id']) || $_SESSION['pending_temp_id'] !== $temp_id) {
    die("Invalid session. Please start registration again.");
}

$stmt = $pdo->prepare("SELECT email, amount, level FROM pending_registrations WHERE temp_id = ? AND status = 'pending_payment'");
$stmt->execute([$temp_id]);
$pending = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pending) die("Registration not found.");

$email  = $pending['email'];
$amount = (int) ($pending['amount'] * 100);

$reference = "NACOS_" . strtoupper($pending['level']) . "_" . time() . rand(10000,99999);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'email' => $email,
        'amount' => $amount,
        'reference' => $reference,
        'callback_url' => "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/process_payment.php"
    ]),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
        "Content-Type: application/json"
    ],
    CURLOPT_SSL_VERIFYPEER => false,   // ← Temporary bypass
    CURLOPT_SSL_VERIFYHOST => false    // ← Temporary bypass
]);

$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response, true);

if (isset($result['status']) && $result['status'] === true) {
    $_SESSION['paystack_reference'] = $reference;
    header("Location: " . $result['data']['authorization_url']);
    exit;
} else {
    die("Payment init failed: " . ($result['message'] ?? 'Unknown error'));
}
?>