<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// RBAC Enforcement: Assistant Admins (Level 1) cannot perform write actions
if (($_SESSION['role_level'] ?? 1) < 2) {
    $_SESSION['error'] = 'Unauthorized action. Assistant Admins have read-only access.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/admin/dashboard.php'));
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/admin/operations.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');
$operation = $_POST['operation'] ?? '';

$phpExe = 'C:\\xampp\\php\\php.exe';
$projectRoot = realpath(__DIR__ . '/..');
$migrateScript = $projectRoot . DIRECTORY_SEPARATOR . 'migrate.php';
$backupScript = $projectRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'backup.ps1';
$restoreScript = $projectRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'restore.ps1';

$output = [];
$code = 1;
$cmd = '';
switch ($operation) {
    case 'migration_status':
        $cmd = '"' . $phpExe . '" "' . $migrateScript . '" --status';
        break;
    case 'migrate_up':
        $cmd = '"' . $phpExe . '" "' . $migrateScript . '" --up';
        break;
    case 'backup_db':
        $cmd = 'powershell -ExecutionPolicy Bypass -File "' . $backupScript . '" -SkipFiles';
        break;
    case 'backup_full':
        $cmd = 'powershell -ExecutionPolicy Bypass -File "' . $backupScript . '"';
        break;
    case 'restore_db':
        if (($_POST['confirm_restore'] ?? '') !== '1') {
            $_SESSION['operations_error'] = 'Restore confirmation is required.';
            header("Location: " . BASE_URL . "/admin/operations.php");
            exit;
        }

        $folderName = trim((string)($_POST['backup_folder'] ?? ''));
        if ($folderName === '' || preg_match('/[^a-zA-Z0-9_\-]/', $folderName)) {
            $_SESSION['operations_error'] = 'Invalid backup folder selected.';
            header("Location: " . BASE_URL . "/admin/operations.php");
            exit;
        }
        $backupFolderPath = realpath($projectRoot . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $folderName);
        $backupsRoot = realpath($projectRoot . DIRECTORY_SEPARATOR . 'backups');

        if (!$backupFolderPath || !$backupsRoot || strpos($backupFolderPath, $backupsRoot) !== 0 || !is_dir($backupFolderPath)) {
            $_SESSION['operations_error'] = 'Backup folder not found.';
            header("Location: " . BASE_URL . "/admin/operations.php");
            exit;
        }

        $cmd = 'powershell -ExecutionPolicy Bypass -File "' . $restoreScript . '" -BackupFolder "' . $backupFolderPath . '"';
        break;
    default:
        $_SESSION['operations_error'] = 'Unknown operation requested.';
        header("Location: " . BASE_URL . "/admin/operations.php");
        exit;
}

exec($cmd . ' 2>&1', $output, $code);

$_SESSION['operations_output'] = "Command: {$cmd}\n\n" . implode("\n", $output);
if ($code === 0) {
    $_SESSION['operations_success'] = 'Operation completed successfully.';
} else {
    $_SESSION['operations_error'] = 'Operation failed. Check output below.';
}

header("Location: " . BASE_URL . "/admin/operations.php");
exit;
