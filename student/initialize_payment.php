<?php
declare(strict_types=1);

// config.php starts the session (guarded) and defines PAYSTACK_* + $pdo
require_once '../includes/config.php';

$temp_id = $_GET['temp_id'] ?? '';

if (empty($temp_id) || !isset($_SESSION['pending_temp_id']) || $_SESSION['pending_temp_id'] !== $temp_id) {
    die("Invalid session. Please start registration again.");
}

$stmt = $pdo->prepare("SELECT email, name, amount, level FROM pending_registrations WHERE temp_id = ? AND status = 'pending_payment'");
$stmt->execute([$temp_id]);
$pending = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pending) die("Registration not found.");

$email = $pending['email'];
$name  = $pending['name'];

// round(), not a bare cast: (int)(4999.99 * 100) truncates to 499998 kobo.
$amount = (int) round(((float) $pending['amount']) * 100);

if ($amount < 100) die("Invalid registration amount.");

$reference = "NACOS_" . strtoupper($pending['level']) . "_" . time() . rand(10000, 99999);

// Paystack sends the payer's own browser here after checkout, so this must be
// a URL the payer can reach — follow the real request scheme, never hardcode http.
$scheme       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$callback_url = $scheme . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/process_payment.php";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_POSTFIELDS => json_encode([
        'email'        => $email,
        'amount'       => $amount,
        'reference'    => $reference,
        'callback_url' => $callback_url,
        // 'name' is not a field Paystack accepts on initialize; it belongs in metadata.
        'metadata'     => [
            'name'  => $name,
            'level' => $pending['level'],
            'custom_fields' => [
                ['display_name' => 'Full Name', 'variable_name' => 'full_name', 'value' => $name],
            ],
        ],
    ]),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
        "Content-Type: application/json",
    ],
] + paystack_ssl_opts());

$response  = curl_exec($curl);
$curl_err  = curl_error($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// Without this, a TLS/network failure ($response === false) shows up as the
// misleading "Unknown error" instead of the actual cURL message.
if ($response === false) {
    die("Payment init failed (connection): " . htmlspecialchars($curl_err, ENT_QUOTES, 'UTF-8'));
}

$result = json_decode($response, true);

if (isset($result['status']) && $result['status'] === true) {
    $_SESSION['paystack_reference'] = $reference;
    header("Location: " . $result['data']['authorization_url']);
    exit;
}

die("Payment init failed (HTTP {$http_code}): "
    . htmlspecialchars($result['message'] ?? 'Unknown error', ENT_QUOTES, 'UTF-8'));