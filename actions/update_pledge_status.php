<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header("Location: /share_hope/login.php");
    exit;
}

// CSRF check
$submitted_token = $_POST['csrf_token'] ?? '';
$session_token = $_SESSION['csrf_token'] ?? '';
if (empty($submitted_token) || !hash_equals($session_token, $submitted_token)) {
    die("Security verification failed. CSRF token mismatch.");
}

$pledge_id = intval($_POST['pledge_id'] ?? 0);
$status = $_POST['status'] ?? 'pledged';
$valid_statuses = ['pledged', 'received', 'distributed'];

if (!in_array($status, $valid_statuses)) {
    $_SESSION['error'] = "Invalid status.";
    header("Location: /share_hope/ngo/dashboard.php");
    exit;
}

try {
    // Ensure the pledge belongs to a campaign owned by this NGO
    $stmt = $pdo->prepare("SELECT i.id FROM inkind_donations i 
                           JOIN campaigns c ON i.campaign_id = c.id 
                           JOIN ngos n ON c.ngo_id = n.id 
                           WHERE i.id = ? AND n.user_id = ?");
    $stmt->execute([$pledge_id, $_SESSION['user_id']]);

    if ($stmt->fetch()) {
        $updateStmt = $pdo->prepare("UPDATE inkind_donations SET status = ? WHERE id = ?");
        $updateStmt->execute([$status, $pledge_id]);
        $_SESSION['success'] = "Pledge status updated to " . ucfirst($status) . ".";
    } else {
        $_SESSION['error'] = "Unauthorized or pledge not found.";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

header("Location: /share_hope/ngo/dashboard.php");
exit;
