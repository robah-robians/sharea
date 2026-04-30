<?php
// includes/db.php
require_once __DIR__ . '/critical_write_guard.php';
enforce_critical_write_lock(['/actions/toggle_maintenance.php','/actions/admin_operations.php']);

$host = '127.0.0.1';
$db   = 'share_hope';
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
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        die("Error: The database 'share_hope' does not exist. Please import database.sql into your MySQL server.");
    }
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Essential functions only
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>