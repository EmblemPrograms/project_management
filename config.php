<?php
// config.php - Central Configuration for NACOS FPE CHAPTER Project Register

session_start();
date_default_timezone_set('Africa/Lagos');
// Add this for better debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);
// ===================== DATABASE CONFIGURATION =====================
$host = 'localhost';
$db   = 'project_repo';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// ====================== PAYSTACK CONFIG ======================
define('PAYSTACK_SECRET_KEY', 'sk_test_f73e9c6d028563c8c728bef172c1e7359b4a1ef8');

// ===================== UPLOAD DIRECTORIES =====================
define('UPLOAD_PASSPORT_DIR', 'uploads/passports/');
define('UPLOAD_PROJECT_DIR',  'uploads/projects/');

if (!is_dir(UPLOAD_PASSPORT_DIR)) mkdir(UPLOAD_PASSPORT_DIR, 0755, true);
if (!is_dir(UPLOAD_PROJECT_DIR))  mkdir(UPLOAD_PROJECT_DIR, 0755, true);

// ===================== PHPMailer Settings =====================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'fawwasolajide@gmail.com');
define('SMTP_PASSWORD', 'rytfhbmvckmaetlw');     // Keep your real App Password here
define('SMTP_FROM_EMAIL', 'fawwasolajide@gmail.com');
define('SMTP_FROM_NAME', 'NACOS FPE CHAPTER');

// System Constants
define('SYSTEM_NAME', 'NACOS FPE CHAPTER');
define('DEFAULT_SESSION', '2024/2025');

// ===================== SECURITY =====================
ini_set('display_errors', 1);        // ← Changed to 1 so we can see errors
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Helper function
function safe_output($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>