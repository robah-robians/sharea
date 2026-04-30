<?php
// actions/edit_campaign_action.php
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
$edit_url    = "/share_hope/admin/edit_campaign.php?id=" . $campaign_id;

if (!$campaign_id) {
    $_SESSION['error'] = "Invalid initiative ID.";
    header("Location: " . $redirect);
    exit;
}

// Validate inputs
$title       = trim($_POST['title'] ?? '');
$goal_amount = floatval($_POST['goal_amount'] ?? 0);
$deadline    = $_POST['deadline'] ?? '';
$category_id = intval($_POST['category_id'] ?? 0);
$description = trim($_POST['description'] ?? '');
$status      = in_array($_POST['status'] ?? '', ['active', 'completed', 'archived']) ? $_POST['status'] : 'active';

if (empty($title) || $goal_amount <= 0 || empty($deadline) || empty($description)) {
    $_SESSION['error'] = "All required parameters must be transmitted.";
    header("Location: " . $edit_url);
    exit;
}

// Fetch current campaign (for image fallback)
$stmt = $pdo->prepare("SELECT image_url FROM campaigns WHERE id = ?");
$stmt->execute([$campaign_id]);
$existing = $stmt->fetch();
if (!$existing) {
    $_SESSION['error'] = "Initiative not found.";
    header("Location: " . $redirect);
    exit;
}

// Handle image upload — replace only if new file submitted
$image_url = $existing['image_url'];
if (!empty($_FILES['image']['name'])) {
    $uploadDir = __DIR__ . '/../assets/uploads/campaigns/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
        $newFilename = uniqid('camp_') . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFilename)) {
            $image_url = '/share_hope/assets/uploads/campaigns/' . $newFilename;
        }
    } else {
        $_SESSION['error'] = "Invalid image format. Use JPG or PNG.";
        header("Location: " . $edit_url);
        exit;
    }
}

// Execute update
try {
    $stmt = $pdo->prepare("
        UPDATE campaigns
        SET title = ?, description = ?, goal_amount = ?,
            deadline = ?, category_id = ?, image_url = ?, status = ?
        WHERE id = ?
    ");
    $stmt->execute([$title, $description, $goal_amount, $deadline, $category_id, $image_url, $status, $campaign_id]);

    $_SESSION['success'] = "Initiative parameters successfully updated and transmitted.";
    header("Location: " . $redirect);
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = "Parameter update failure. System signal error.";
    header("Location: " . $edit_url);
    exit;
}
