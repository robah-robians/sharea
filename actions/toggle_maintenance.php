<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /share_hope/index.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$action = $_POST['action'] ?? '';
$lockFile = __DIR__ . '/../.maintenance_lock';

if ($action === 'enable') {
    file_put_contents($lockFile, "Site is under maintenance. Generated at " . date('Y-m-d H:i:s'));
    $_SESSION['success'] = "Maintenance Mode ENABLED. Normal users can no longer access the site.";
} elseif ($action === 'disable') {
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
    $_SESSION['success'] = "Maintenance Mode DISABLED. The site is now live.";
}

header("Location: /share_hope/admin/dashboard.php");
exit;
