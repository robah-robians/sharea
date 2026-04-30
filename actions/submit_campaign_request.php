<?php
// actions/submit_campaign_request.php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header("Location: /share_hope/login.php"); exit;
}
verify_csrf_token($_POST['csrf_token'] ?? '');

// Get NGO record
$stmt = $pdo->prepare("SELECT * FROM ngos WHERE user_id = ? AND is_verified = 1");
$stmt->execute([$_SESSION['user_id']]);
$ngo = $stmt->fetch();
if (!$ngo) {
    $_SESSION['error'] = "Your NGO account must be verified before submitting campaign requests.";
    header("Location: /share_hope/ngo/dashboard.php"); exit;
}

$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$goal_amount = floatval($_POST['goal_amount'] ?? 0);
$deadline    = $_POST['deadline'] ?? '';
$category_id = intval($_POST['category_id'] ?? 0);

if (empty($title) || empty($description) || $goal_amount < 100 || empty($deadline) || !$category_id) {
    $_SESSION['error'] = "All required fields must be completed.";
    header("Location: /share_hope/ngo/submit_campaign.php"); exit;
}

// Handle image upload
$image_url = null;
if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../assets/uploads/campaigns/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png'])) {
        $filename = 'req_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
            $image_url = '/share_hope/assets/uploads/campaigns/' . $filename;
        }
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO campaign_requests (ngo_id, title, description, goal_amount, deadline, category_id, image_url, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$ngo['id'], $title, $description, $goal_amount, $deadline, $category_id, $image_url]);

    // Notify the admin via notifications table
    $admin = $pdo->query("SELECT id FROM users WHERE role IN ('admin','super_admin') LIMIT 1")->fetch();
    if ($admin) {
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
            ->execute([$admin['id'], "New campaign request from {$_SESSION['user_name']}: \"{$title}\""]);
    }

    $_SESSION['success'] = "Your campaign request has been submitted successfully. The admin will review it shortly.";
    header("Location: /share_hope/ngo/dashboard.php"); exit;
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to submit request: " . $e->getMessage();
    header("Location: /share_hope/ngo/submit_campaign.php"); exit;
}
