<?php
// config.php - Central Configuration for NACOS FPE CHAPTER Project Register

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}
date_default_timezone_set('Africa/Lagos');

// ===================== LOCAL CREDENTIALS =====================
//
// Secrets live in includes/config.local.php, which is NOT tracked in git.
// The repository is public, so a database password, a Paystack secret key or
// an SMTP password in this file would be published the moment it is pushed.
//
// On a new machine or server:  cp config.local.example.php config.local.php
// and fill in the real values.

$__local_file = __DIR__ . '/config.local.php';
if (!is_file($__local_file)) {
    error_log('config.local.php is missing. Copy includes/config.local.example.php to includes/config.local.php and fill in the real values.');
    http_response_code(503);
    die('The service is not configured. Please contact the administrator.');
}
$LOCAL = require $__local_file;
unset($__local_file);

if (!is_array($LOCAL)) {
    error_log('config.local.php must return an array.');
    http_response_code(503);
    die('The service is not configured. Please contact the administrator.');
}

// false anywhere the public can reach: visitors must never see PHP errors.
define('DEBUG_MODE', (bool) ($LOCAL['debug'] ?? false));
ini_set('display_errors', DEBUG_MODE ? '1' : '0');
ini_set('display_startup_errors', DEBUG_MODE ? '1' : '0');
// Errors are still recorded either way — they go to the error_log file
// instead of the page, so nothing is lost by hiding them from visitors.
ini_set('log_errors', '1');
error_reporting(E_ALL);

// ===================== DATABASE CONFIGURATION =====================
$host = $LOCAL['db']['host'] ?? 'localhost';
$db   = $LOCAL['db']['name'] ?? '';
$user = $LOCAL['db']['user'] ?? '';
$pass = $LOCAL['db']['pass'] ?? '';
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
    // die() writes straight to the page, so display_errors does NOT suppress
    // it. Without this guard, turning DEBUG_MODE off still leaked the database
    // name and user to any visitor during an outage.
    error_log("Database connection failed: " . $e->getMessage());
    if (DEBUG_MODE) {
        die("Database Connection Failed: " . $e->getMessage());
    }
    http_response_code(503);
    die("The service is temporarily unavailable. Please try again shortly.");
}

// ====================== PAYSTACK CONFIG ======================
// The key itself is in config.local.php. sk_live_ is followed by EXACTLY 40
// hex characters; a key one character too long is what produces Paystack's
// "Invalid key".
define('PAYSTACK_SECRET_KEY', $LOCAL['paystack_secret'] ?? '');

// Optional CA bundle. On a Linux host the system CA store normally works, so
// this file usually will not exist and cURL falls back correctly. Only upload
// one (from https://curl.se/ca/cacert.pem) if you hit
// "SSL certificate problem: unable to get local issuer certificate".
define('PAYSTACK_CA_BUNDLE', __DIR__ . '/cacert.pem');

/**
 * Returns cURL SSL options that securely verify the remote certificate.
 * Verification is always ON; a bundled CA file is used when present.
 */
function paystack_ssl_opts() {
    $opts = [
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];
    if (defined('PAYSTACK_CA_BUNDLE') && is_file(PAYSTACK_CA_BUNDLE)) {
        $opts[CURLOPT_CAINFO] = PAYSTACK_CA_BUNDLE;
    }
    return $opts;
}

// ===================== UPLOAD DIRECTORIES =====================
//
// These used to be relative ('uploads/passports/'), which PHP resolves against
// the RUNNING SCRIPT's directory, not the project root. Registration runs from
// student/, so passports were written to student/uploads/passports/ while the
// admin pages rendered <img src="uploads/passports/..."> — resolved by the
// browser against /admin/ — and every passport 404'd. It also scattered empty
// uploads/ folders through admin/, department/ and includes/.
//
// Filesystem paths are now absolute, and there is a matching URL prefix so a
// page in any directory links to the same one place.

define('PROJECT_ROOT', dirname(__DIR__));

define('UPLOAD_PASSPORT_DIR', PROJECT_ROOT . '/uploads/passports/');
define('UPLOAD_PROJECT_DIR',  PROJECT_ROOT . '/uploads/projects/');

if (!is_dir(UPLOAD_PASSPORT_DIR)) mkdir(UPLOAD_PASSPORT_DIR, 0755, true);
if (!is_dir(UPLOAD_PROJECT_DIR))  mkdir(UPLOAD_PROJECT_DIR, 0755, true);

/**
 * Web path to the project root, worked out from where this file sits inside
 * the document root. Locally that yields "/project_management/"; on a host
 * where the project IS the document root it yields "/". Both without needing
 * a hardcoded domain or path.
 */
$__doc_root  = str_replace('\\', '/', rtrim((string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\'));
$__proj_root = str_replace('\\', '/', (string) realpath(PROJECT_ROOT));
$__base      = ($__doc_root !== '' && strpos($__proj_root, $__doc_root) === 0)
    ? substr($__proj_root, strlen($__doc_root))
    : '';
define('BASE_URL', rtrim($__base, '/') . '/');
unset($__doc_root, $__proj_root, $__base);

define('UPLOAD_PASSPORT_URL', BASE_URL . 'uploads/passports/');
define('UPLOAD_PROJECT_URL',  BASE_URL . 'uploads/projects/');

/**
 * URL for a student's passport photo. Returns '' when there is none, so
 * callers can fall back to a placeholder.
 */
function passport_url($filename) {
    $filename = trim((string) $filename);
    // basename() so a legacy row holding "uploads/passports/x.jpg" resolves
    // the same as a modern one holding just "x.jpg".
    return $filename === '' ? '' : UPLOAD_PASSPORT_URL . rawurlencode(basename($filename));
}

/** URL for a submitted project file. projects.file_path stores a path, not a name. */
function project_url($stored_path) {
    $stored_path = trim((string) $stored_path);
    return $stored_path === '' ? '' : UPLOAD_PROJECT_URL . rawurlencode(basename($stored_path));
}

// ===================== PHPMailer Settings =====================
define('SMTP_HOST',       $LOCAL['smtp']['host']       ?? 'smtp.gmail.com');
define('SMTP_PORT',       $LOCAL['smtp']['port']       ?? 587);
define('SMTP_USERNAME',   $LOCAL['smtp']['username']   ?? '');
define('SMTP_PASSWORD',   $LOCAL['smtp']['password']   ?? '');
define('SMTP_FROM_EMAIL', $LOCAL['smtp']['from_email'] ?? '');
define('SMTP_FROM_NAME',  $LOCAL['smtp']['from_name']  ?? 'NACOS FPE CHAPTER');

// System Constants
define('SYSTEM_NAME', 'NACOS FPE CHAPTER');
define('DEFAULT_SESSION', '2025/2026');

// Helper function
function safe_output($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}