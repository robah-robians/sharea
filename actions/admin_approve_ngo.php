<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/admin/dashboard.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    $_SESSION['error'] = "Unauthorized.";
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// RBAC Enforcement: Assistant Admins (Level 1) cannot perform write actions
if (($_SESSION['role_level'] ?? 1) < 2) {
    $_SESSION['error'] = 'Unauthorized action. Assistant Admins have read-only access.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/admin/dashboard.php'));
    exit;
}


$criticalLockFile = __DIR__ . '/../.critical_update_lock';
if (file_exists($criticalLockFile) && $_SESSION['user_role'] !== 'super_admin') {
    $_SESSION['error'] = 'Critical update lock is enabled. Only super admin can modify approval decisions right now.';
    header("Location: " . BASE_URL . "/admin/dashboard.php");
    exit;
}

$ngo_id = intval($_POST['ngo_id'] ?? 0);
$action = $_POST['action'] ?? '';

// Get NGO details for logging
$ngo_stmt = $pdo->prepare("SELECT n.user_id, u.name, u.email FROM ngos n JOIN users u ON n.user_id = u.id WHERE n.id = ?");
$ngo_stmt->execute([$ngo_id]);
$ngo_details = $ngo_stmt->fetch();

if ($ngo_id > 0) {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE ngos SET is_verified = 1 WHERE id = ?");
        $stmt->execute([$ngo_id]);

        // Log activity
        log_admin_activity($pdo, $_SESSION['user_id'], 'Approved NGO', 'approve', 'ngos', $ngo_id, $ngo_details['name'], 'NGO ' . $ngo_details['name'] . ' approved for verification');

        // Find user_id to notify
        $stmt = $pdo->prepare("SELECT user_id FROM ngos WHERE id = ?");
        $stmt->execute([$ngo_id]);
        $ngo_user = $stmt->fetch();
        if ($ngo_user) {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->execute([$ngo_user['user_id'], "Congratulations! Your NGO application has been approved. You can now create campaigns."]);
        }

        $_SESSION['success'] = "NGO has been successfully approved and verified.";
    } elseif ($action === 'reject') {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?")->execute([$ngo_details['user_id']]);
            $pdo->prepare("UPDATE ngos SET is_verified = 0 WHERE id = ?")->execute([$ngo_id]);
            $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$ngo_details['user_id'], "Your NGO application was rejected. Your account has been suspended for review."]);
            log_admin_activity($pdo, $_SESSION['user_id'], 'Rejected NGO', 'deny', 'ngos', $ngo_id, $ngo_details['name'], 'NGO rejected and account suspended (no hard delete).');
            $pdo->commit(); $_SESSION['success'] = "NGO application rejected. Account suspended safely (data retained).";
        } catch (Exception $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } $_SESSION['error'] = "Failed to reject NGO safely: " . $e->getMessage(); }
    }
}

header("Location: " . BASE_URL . "/admin/dashboard.php");
exit;
