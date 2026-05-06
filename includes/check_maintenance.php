<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lockFile = __DIR__ . '/../.maintenance_lock';

// Ensure admins can still log in and view the site to test updates
$isAdmin = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin']);

// Check if we are already on the maintenance page to prevent infinite redirects
$current_page = basename($_SERVER['PHP_SELF']);

if (file_exists($lockFile) && !$isAdmin && $current_page !== 'maintenance.php' && strpos($current_page, 'action') === false) {
    // If it's an action script hitting the DB during maintenance, kill it safely to prevent data corruption.
    if (strpos($_SERVER['REQUEST_URI'], '/actions/') !== false) {
        http_response_code(503);
        die("Service Unavailable: The platform is currently undergoing critical maintenance. Please try your request again shortly.");
    }

    header("Location: " . BASE_URL . "/maintenance.php");
    exit;
}

// Redirect away from maintenance page if the lock is removed
if (!file_exists($lockFile) && $current_page === 'maintenance.php') {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

