<?php
// includes/db.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/critical_write_guard.php';
enforce_critical_write_lock(['/actions/toggle_maintenance.php', '/actions/admin_operations.php']);

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'Unknown database') !== false) {
        die("Error: The database '" . DB_NAME . "' does not exist. Please import database/database.sql into your MySQL server.");
    }
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

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

// Convenience: build a full URL from a relative path
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}
