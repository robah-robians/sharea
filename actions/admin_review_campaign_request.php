<?php
// actions/admin_review_campaign_request.php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin','super_admin'])) {
    header("Location: " . BASE_URL . "/login.php"); exit;
}

// RBAC Enforcement: Assistant Admins (Level 1) cannot perform write actions
if (($_SESSION['role_level'] ?? 1) < 2) {
    $_SESSION['error'] = 'Unauthorized action. Assistant Admins have read-only access.';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/admin/dashboard.php'));
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$request_id = intval($_POST['request_id'] ?? 0);
$action     = $_POST['action'] ?? ''; // 'approve' or 'reject'
$reason     = trim($_POST['rejection_reason'] ?? '');
$redirect   = BASE_URL . "/admin/dashboard.php";

if (!$request_id || !in_array($action, ['approve','reject','consider'])) {
    $_SESSION['error'] = "Invalid operation.";
    header("Location: $redirect"); exit;
}

// Fetch the request
$stmt = $pdo->prepare("SELECT cr.*, u.name as ngo_user_name, n.user_id as ngo_user_id FROM campaign_requests cr JOIN ngos n ON cr.ngo_id = n.id JOIN users u ON n.user_id = u.id WHERE cr.id = ?");
$stmt->execute([$request_id]);
$req = $stmt->fetch();
if (!$req) {
    $_SESSION['error'] = "Request not found.";
    header("Location: $redirect"); exit;
}

try {
    if ($action === 'consider') {
        $pdo->prepare("UPDATE campaign_requests SET status='under_consideration', reviewed_by=?, reviewed_at=NOW() WHERE id=?")
            ->execute([$_SESSION['user_id'], $request_id]);

        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
            ->execute([$req['ngo_user_id'], "Your campaign request \"{$req['title']}\" is currently under consideration by our team. We will update you shortly."]);

        log_admin_activity($pdo, $_SESSION['user_id'], 'consider_campaign_request', 'update', 'campaign_requests', $request_id, null, "Marked under consideration: {$req['title']}");
        $_SESSION['success'] = "Campaign request marked as under consideration. The NGO has been notified.";
        header("Location: $redirect"); exit;

    } elseif ($action === 'approve') {
        // Mark request approved
        $pdo->prepare("UPDATE campaign_requests SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?")
            ->execute([$_SESSION['user_id'], $request_id]);

        // Notify NGO that request was approved and admin will create campaign
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
            ->execute([$req['ngo_user_id'], "Your campaign request \"{$req['title']}\" has been approved! The admin is creating the campaign."]);

        log_admin_activity($pdo, $_SESSION['user_id'], 'approve_campaign_request', 'approve', 'campaign_requests', $request_id, null, "Approved campaign request: {$req['title']}");

        // Redirect to Deploy tab pre-filled with request data
        $_SESSION['success'] = "Campaign request approved. Use the pre-filled form below to deploy the campaign.";
        $_SESSION['prefill_request_id'] = $request_id;
        header("Location: " . BASE_URL . "/admin/campaigns_hub.php?tab=deploy&from_request={$request_id}"); exit;

    } else {
        // reject
        if (empty($reason)) {
            $_SESSION['error'] = "A rejection reason is required.";
            header("Location: $redirect"); exit;
        }
        $pdo->prepare("UPDATE campaign_requests SET status='rejected', rejection_reason=?, reviewed_by=?, reviewed_at=NOW() WHERE id=?")
            ->execute([$reason, $_SESSION['user_id'], $request_id]);

        // Notify NGO
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
            ->execute([$req['ngo_user_id'], "Your campaign request \"{$req['title']}\" was not approved. Reason: {$reason}"]);

        log_admin_activity($pdo, $_SESSION['user_id'], 'reject_campaign_request', 'deny', 'campaign_requests', $request_id, null, "Rejected campaign request: {$req['title']}");
        $_SESSION['success'] = "Campaign request rejected. The NGO has been notified.";
        header("Location: $redirect"); exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Operation failed: " . $e->getMessage();
    header("Location: $redirect"); exit;
}
