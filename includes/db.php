<?php
// db.php - Simple database connection (if you prefer to use separately)

$host = 'localhost';
$db   = 'school37_project_repo';
$user = 'school37';
$pass = 'x6z4U;0ZPuB5e:';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>