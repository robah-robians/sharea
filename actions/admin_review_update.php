<?php
// actions/admin_review_update.php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin','super_admin'])) {
    header("Location: /share_hope/login.php"); exit;
}
verify_csrf_token($_POST['csrf_token'] ?? '');

$update_id = intval($_POST['update_id'] ?? 0);
$action    = $_POST['action'] ?? '';
$reason    = trim($_POST['rejection_reason'] ?? '');
$redirect  = "/share_hope/admin/campaign_updates_review.php";

if (!$update_id || !in_array($action, ['approve','reject'])) {
    $_SESSION['error'] = "Invalid operation.";
    header("Location: $redirect"); exit;
}

// Fetch update + campaign + NGO user for notification
$stmt = $pdo->prepare("
    SELECT cu.*, c.title as campaign_title, n.user_id as ngo_user_id
    FROM campaign_updates cu
    JOIN campaigns c ON cu.campaign_id = c.id
    LEFT JOIN ngos n ON c.ngo_id = n.id AND c.ngo_id IS NOT NULL
    WHERE cu.id = ?
");
$stmt->execute([$update_id]);
$update = $stmt->fetch();

if (!$update) {
    $_SESSION['error'] = "Update not found.";
    header("Location: $redirect"); exit;
}

try {
    if ($action === 'approve') {
        $pdo->prepare("UPDATE campaign_updates SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?")
            ->execute([$_SESSION['user_id'], $update_id]);

        // Notify NGO
        if ($update['ngo_user_id']) {
            $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
                ->execute([$update['ngo_user_id'], "Your campaign update for \"{$update['campaign_title']}\" has been approved and is now live."]);
        }

        log_admin_activity($pdo, $_SESSION['user_id'], 'approve_campaign_update', 'approve', 'campaign_updates', $update_id, null, "Approved update for campaign: {$update['campaign_title']}");
        $_SESSION['success'] = "Update approved and is now publicly visible.";

    } else {
        if (empty($reason)) {
            $_SESSION['error'] = "A rejection reason is required.";
            header("Location: $redirect"); exit;
        }
        $pdo->prepare("UPDATE campaign_updates SET status='rejected', rejection_reason=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")
            ->execute([$reason, $_SESSION['user_id'], $update_id]);

        // Notify NGO
        if ($update['ngo_user_id']) {
            $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
                ->execute([$update['ngo_user_id'], "Your update for \"{$update['campaign_title']}\" was not approved. Reason: {$reason}"]);
        }

        log_admin_activity($pdo, $_SESSION['user_id'], 'reject_campaign_update', 'deny', 'campaign_updates', $update_id, null, "Rejected update for campaign: {$update['campaign_title']}");
        $_SESSION['success'] = "Update rejected. The NGO has been notified.";
    }
    header("Location: $redirect"); exit;
} catch (Exception $e) {
    $_SESSION['error'] = "Operation failed: " . $e->getMessage();
    header("Location: $redirect"); exit;
}
