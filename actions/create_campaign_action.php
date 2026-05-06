<?php
// actions/create_campaign_action.php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/ngo/dashboard.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Unauthorized.";
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$is_admin = in_array($_SESSION['user_role'], ['admin', 'super_admin']);

if ($is_admin) {
    // Admin deploying on behalf of an NGO — ngo_id must be passed in POST
    $ngo_id = intval($_POST['ngo_id'] ?? 0);
    if (!$ngo_id) {
        $_SESSION['error'] = "No NGO specified for this campaign.";
        header("Location: " . BASE_URL . "/admin/campaigns_hub.php?tab=deploy");
        exit;
    }
} else {
    if ($_SESSION['user_role'] !== 'ngo') {
        $_SESSION['error'] = "Unauthorized.";
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
    // Fetch NGO ID from user ID
    $stmt = $pdo->prepare("SELECT id, is_verified FROM ngos WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $ngo = $stmt->fetch();
    if (!$ngo || !$ngo['is_verified']) {
        $_SESSION['error'] = "Your NGO account has not been approved yet. Active campaigns require admin verification.";
        header("Location: " . BASE_URL . "/ngo/dashboard.php");
        exit;
    }
    $ngo_id = $ngo['id'];
}
$title = trim($_POST['title'] ?? '');
$goal_amount = floatval($_POST['goal_amount'] ?? 0);
$deadline = $_POST['deadline'] ?? '';
$category_id = intval($_POST['category_id'] ?? 0);
$description = trim($_POST['description'] ?? '');

if (empty($title) || $goal_amount <= 0 || empty($deadline) || empty($description)) {
    $_SESSION['error'] = "Please provide all required campaign details.";
    header("Location: " . BASE_URL . "/ngo/create_campaign.php");
    exit;
}

// Handle image upload
$image_url = null;
if (!empty($_FILES['image']['name'])) {
    $uploadDir = __DIR__ . '/../assets/uploads/campaigns/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileInfo = pathinfo($_FILES['image']['name']);
    $ext = strtolower($fileInfo['extension']);
    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
        $newFilename = uniqid('camp_') . '.' . $ext;
        $destination = $uploadDir . $newFilename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            // DB relative path
            $image_url = '/assets/uploads/campaigns/' . $newFilename;
        }
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO campaigns (ngo_id, title, description, goal_amount, deadline, category_id, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$ngo_id, $title, $description, $goal_amount, $deadline, $category_id, $image_url]);
    
    // If admin deployed from a campaign request, mark it as deployed
    $from_request = intval($_POST['from_request_id'] ?? 0);
    if ($is_admin && $from_request) {
        $pdo->prepare("UPDATE campaign_requests SET status='approved', reviewed_by=?, reviewed_at=NOW() WHERE id=?")
            ->execute([$_SESSION['user_id'], $from_request]);
    }

    $_SESSION['success'] = "Campaign deployed successfully!";
    $redirect = $_POST['redirect_url'] ?? ($is_admin ? BASE_URL . '/admin/campaigns_hub.php?tab=performance' : '/share_hope/ngo_profile.php?id=' . $ngo_id);
    header("Location: $redirect");
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to create campaign. System error.";
    $fallback = $is_admin ? BASE_URL . '/admin/campaigns_hub.php?tab=deploy' : '/share_hope/ngo/create_campaign.php';
    header("Location: $fallback");
    exit;
}
