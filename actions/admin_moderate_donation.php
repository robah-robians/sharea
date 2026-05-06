<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

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
    header("Location: " . BASE_URL . "/admin/finance_controls.php?tab=audit");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');
$donationId = (int)($_POST['donation_id'] ?? 0);
$action = $_POST['mod_action'] ?? '';
$allowed = ['hide', 'restore', 'flag', 'unflag', 'void', 'unvoid'];

if ($donationId <= 0 || !in_array($action, $allowed, true)) {
    $_SESSION['error'] = 'Invalid transaction moderation request.';
    header("Location: " . BASE_URL . "/admin/finance_controls.php?tab=audit");
    exit;
}
$stmt = $pdo->prepare('SELECT transaction_id, amount, hidden_in_ledger, moderation_state FROM donations WHERE id = ?');
$stmt->execute([$donationId]);
$txn = $stmt->fetch();

if (!$txn) {
    $_SESSION['error'] = 'Transaction not found.';
    header("Location: " . BASE_URL . "/admin/finance_controls.php?tab=audit");
    exit;
}

switch ($action) {
    case 'hide':
        $pdo->prepare("UPDATE donations SET hidden_in_ledger = 1, moderated_by = ?, moderated_at = NOW() WHERE id = ?")
            ->execute([$_SESSION['user_id'], $donationId]);
        $_SESSION['success'] = 'Transaction hidden from default ledger view.';
        $log = 'Hide transaction';
        $desc = 'Transaction hidden from default admin ledger';
        break;
    case 'restore':
        $pdo->prepare("UPDATE donations SET hidden_in_ledger = 0, moderated_by = ?, moderated_at = NOW() WHERE id = ?")
            ->execute([$_SESSION['user_id'], $donationId]);
        $_SESSION['success'] = 'Transaction restored to ledger view.';
        $log = 'Restore transaction';
        $desc = 'Transaction restored to default admin ledger';
        break;
    case 'flag':
        $pdo->prepare("UPDATE donations SET moderation_state = 'flagged', moderated_by = ?, moderated_at = NOW() WHERE id = ?")
            ->execute([$_SESSION['user_id'], $donationId]);
        $_SESSION['success'] = 'Transaction flagged for review.';
        $log = 'Flag transaction';
        $desc = 'Transaction flagged for follow-up';
        break;
    case 'unflag':
        $pdo->prepare("UPDATE donations SET moderation_state = 'normal', moderated_by = ?, moderated_at = NOW() WHERE id = ?")
            ->execute([$_SESSION['user_id'], $donationId]);
        $_SESSION['success'] = 'Transaction unflagged.';
        $log = 'Unflag transaction';
        $desc = 'Transaction removed from flagged state';
        break;
    case 'void':
        $pdo->prepare("UPDATE donations SET moderation_state = 'voided', moderated_by = ?, moderated_at = NOW() WHERE id = ?")
            ->execute([$_SESSION['user_id'], $donationId]);
        $_SESSION['success'] = 'Transaction marked as voided.';
        $log = 'Void transaction';
        $desc = 'Transaction marked as voided without hard delete';
        break;
    default:
        $pdo->prepare("UPDATE donations SET moderation_state = 'normal', moderated_by = ?, moderated_at = NOW() WHERE id = ?")
            ->execute([$_SESSION['user_id'], $donationId]);
        $_SESSION['success'] = 'Transaction restored from void state.';
        $log = 'Unvoid transaction';
        $desc = 'Voided transaction restored to normal state';
        break;
}

$redirect_page = BASE_URL . '/admin/finance_controls.php?tab=audit';
log_admin_activity($pdo, $_SESSION['user_id'], $log, 'update', 'donations', $donationId, $txn['transaction_id'], $desc);

header('Location: ' . $redirect_page);
exit;
