<?php
// config.php - Central Configuration for NACOS FPE CHAPTER Project Register

session_start();
date_default_timezone_set('Africa/Lagos');
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

// ===================== UPLOAD DIRECTORIES =====================
define('UPLOAD_PASSPORT_DIR', 'uploads/passports/');
define('UPLOAD_PROJECT_DIR',  'uploads/projects/');

if (!is_dir(UPLOAD_PASSPORT_DIR)) mkdir(UPLOAD_PASSPORT_DIR, 0755, true);
if (!is_dir(UPLOAD_PROJECT_DIR))  mkdir(UPLOAD_PROJECT_DIR, 0755, true);

// ===================== PHPMailer Settings =====================
define('SMTP_HOST', 'smtp.gmail.com');           // Change if using another provider
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'fawwasolajide@gmail.com');  // Your Gmail address
define('SMTP_PASSWORD', 'rytfhbmvckmaetlw');    // Gmail App Password (16 characters)
define('SMTP_FROM_EMAIL', 'fawwasolajide@gmail.com');
define('SMTP_FROM_NAME', 'NACOS FPE CHAPTER');

// System Constants
define('SYSTEM_NAME', 'NACOS FPE CHAPTER');
define('DEFAULT_SESSION', '2024/2025');

// Security
ini_set('display_errors', 0);   // Set to 1 only in development
error_reporting(E_ALL);

function safe_output($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>