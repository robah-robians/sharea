<?php
// actions/undeploy_campaign_action.php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /share_hope/admin/campaigns_hub.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

// Admin only
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    $_SESSION['error'] = "Unauthorized. Admin access required.";
    header("Location: /share_hope/login.php");
    exit;
}

$campaign_id = intval($_POST['campaign_id'] ?? 0);
$redirect    = $_POST['redirect_url'] ?? "/share_hope/admin/campaigns_hub.php?tab=performance";

if (!$campaign_id) {
    $_SESSION['error'] = "Invalid initiative ID.";
    header("Location: " . $redirect);
    exit;
}

// Verify campaign exists
$stmt = $pdo->prepare("SELECT id, title FROM campaigns WHERE id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    $_SESSION['error'] = "Initiative not found or already terminated.";
    header("Location: " . $redirect);
    exit;
}

// SAFETY CHECK: Count completed donations linked to this campaign
$stmt = $pdo->prepare("SELECT COUNT(*) as donation_count FROM donations WHERE campaign_id = ? AND status = 'completed'");
$stmt->execute([$campaign_id]);
$result = $stmt->fetch();
$donation_count = (int)($result['donation_count'] ?? 0);

try {
    if ($donation_count > 0) {
        // SOFT TERMINATE — archive it to preserve financial audit trail
        $stmt = $pdo->prepare("UPDATE campaigns SET status = 'archived' WHERE id = ?");
        $stmt->execute([$campaign_id]);
        $_SESSION['success'] = "Initiative \"{$campaign['title']}\" has been archived. {$donation_count} donation record(s) are preserved in the financial audit trail.";
    } else {
        // HARD TERMINATE — zero donations, safe to remove from network
        $pdo->prepare("DELETE FROM campaign_updates WHERE campaign_id = ?")->execute([$campaign_id]);
        $pdo->prepare("DELETE FROM inkind_donations WHERE campaign_id = ?")->execute([$campaign_id]);
        $pdo->prepare("DELETE FROM campaigns WHERE id = ?")->execute([$campaign_id]);
        $_SESSION['success'] = "Initiative \"{$campaign['title']}\" has been permanently terminated and removed from the network.";
    }
    header("Location: " . $redirect);
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = "Termination failure. System could not complete the operation.";
    header("Location: " . $redirect);
    exit;
}
