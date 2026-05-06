<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// RBAC Enforcement: Assistant Admins (Level 1) cannot perform write actions
if (($_SESSION['role_level'] ?? 1) < 2) {
    $_SESSION['error'] = 'Unauthorized action. Assistant Admins have read-only access.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/admin/dashboard.php'));
    exit;
}


verify_csrf_token($_POST['csrf_token'] ?? '');

$action = $_POST['action'] ?? '';
$lockFile = __DIR__ . '/../.maintenance_lock';
$criticalLockFile = __DIR__ . '/../.critical_update_lock';

if ($action === 'enable') {
    file_put_contents($lockFile, 'Site is under maintenance. Generated at ' . date('Y-m-d H:i:s'));
    file_put_contents($criticalLockFile, 'Critical write lock enabled at ' . date('Y-m-d H:i:s'));
    $_SESSION['success'] = 'Maintenance Mode ENABLED with Critical Write Lock.';
} elseif ($action === 'disable') {
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
    if (file_exists($criticalLockFile)) {
        unlink($criticalLockFile);
    }
    $_SESSION['success'] = 'Maintenance Mode DISABLED. Site is now live.';
}

header("Location: " . BASE_URL . "/admin/dashboard.php");
exit;
