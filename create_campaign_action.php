<?php
// actions/create_campaign_action.php
session_start();
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /share_hope/ngo/dashboard.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    $_SESSION['error'] = "Unauthorized.";
    header("Location: /share_hope/login.php");
    exit;
}

// Fetch NGO ID from user ID
$stmt = $pdo->prepare("SELECT id, is_verified FROM ngos WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$ngo = $stmt->fetch();

if (!$ngo || !$ngo['is_verified']) {
    $_SESSION['error'] = "Your NGO account has not been approved yet. Active campaigns require admin verification.";
    // If not verified, just redirect to dashboard with error instead of breaking flow completely
    header("Location: /share_hope/ngo/dashboard.php");
    exit;
}

$ngo_id = $ngo['id'];
$title = trim($_POST['title'] ?? '');
$goal_amount = floatval($_POST['goal_amount'] ?? 0);
$deadline = $_POST['deadline'] ?? '';
$category_id = intval($_POST['category_id'] ?? 0);
$description = trim($_POST['description'] ?? '');

if (empty($title) || $goal_amount <= 0 || empty($deadline) || empty($description)) {
    $_SESSION['error'] = "Please provide all required campaign details.";
    header("Location: /share_hope/ngo/create_campaign.php");
    exit;
}

// Handle image upload
$image_url = null;
if (!empty($_FILES['image']['name'])) {
    $uploadDir = __DIR__ . 'assets/uploads/campaigns/';
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
    
    $_SESSION['success'] = "Campaign created successfully!";
    header("Location: /share_hope/ngo_profile.php?id=" . $ngo_id);
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to create campaign. System error.";
    header("Location: /share_hope/ngo/create_campaign.php");
    exit;
}
