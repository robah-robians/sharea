<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /share_hope/admin/dashboard.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    $_SESSION['error'] = "Unauthorized.";
    header("Location: /share_hope/login.php");
    exit;
}

$ngo_id = intval($_POST['ngo_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($ngo_id > 0) {
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE ngos SET is_verified = 1 WHERE id = ?");
        $stmt->execute([$ngo_id]);
        $_SESSION['success'] = "NGO has been successfully approved and verified.";
    } elseif ($action === 'reject') {
        // Find user_id before deleting
        $stmt = $pdo->prepare("SELECT user_id FROM ngos WHERE id = ?");
        $stmt->execute([$ngo_id]);
        $ngo = $stmt->fetch();
        
        if ($ngo) {
            // Delete user, which cascades to ngo due to DB foreign constraints
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$ngo['user_id']]);
            $_SESSION['success'] = "NGO application rejected and account securely removed.";
        }
    }
}

header("Location: /share_hope/admin/dashboard.php");
exit;
