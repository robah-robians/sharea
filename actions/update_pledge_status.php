<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: ../login.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Invalid security token. Please try again.";
        header("Location: ../admin/donations.php");
        exit;
    }

    $pledge_id = $_POST['pledge_id'] ?? '';
    $new_status = $_POST['status'] ?? '';

    if (empty($pledge_id) || empty($new_status)) {
        $_SESSION['error'] = "Missing required information.";
        header("Location: ../admin/donations.php");
        exit;
    }

    $valid_statuses = ['pledged', 'received', 'distributed'];
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['error'] = "Invalid status selected.";
        header("Location: ../admin/donations.php");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT i.*, c.title as campaign_title FROM inkind_donations i JOIN campaigns c ON i.campaign_id = c.id WHERE i.id = ?");
        $stmt->execute([$pledge_id]);
        $pledge = $stmt->fetch();

        if (!$pledge) {
            $_SESSION['error'] = "Pledge not found.";
            header("Location: ../admin/donations.php");
            exit;
        }

        $old_status = $pledge['status'];

        $stmt = $pdo->prepare("UPDATE inkind_donations SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $pledge_id]);

        log_admin_activity(
            $pdo, 
            $_SESSION['user_id'], 
            'Updated pledge status', 
            'update', 
            'inkind_donations', 
            $pledge_id, 
            $pledge['campaign_title'], 
            "Changed status from '{$old_status}' to '{$new_status}' for {$pledge['item_category']} (Qty: {$pledge['quantity']})"
        );

        $_SESSION['success'] = "Pledge status updated successfully.";

    } catch (Exception $e) {
        error_log("Error updating pledge status: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while updating the pledge status.";
    }
}

$redirect_page = $_POST['redirect'] ?? 'donations';
if ($redirect_page === 'all_pledges') {
    header("Location: ../admin/all_pledges.php");
} else {
    header("Location: ../admin/donations.php");
}
exit;
?>
