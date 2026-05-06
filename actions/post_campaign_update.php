<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$campaign_id = intval($_POST['campaign_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
$user_id = $_SESSION['user_id'];

// Verify NGO owns this campaign
$stmt = $pdo->prepare("SELECT c.id FROM campaigns c JOIN ngos n ON c.ngo_id = n.id WHERE c.id = ? AND n.user_id = ?");
$stmt->execute([$campaign_id, $user_id]);
if (!$stmt->fetch()) {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: " . BASE_URL . "/ngo/dashboard.php");
    exit;
}

if (empty($message)) {
    $_SESSION['error'] = "Update message cannot be empty.";
    header("Location: ' . BASE_URL . '/ngo/edit_campaign.php?id=" . $campaign_id);
    exit;
}

$image_url = null;

// Handle optional proof image
if (!empty($_FILES['update_image']['name'])) {
    $uploadDir = __DIR__ . '/../assets/uploads/images/';
    $fileInfo = pathinfo($_FILES['update_image']['name']);
    $ext = strtolower($fileInfo['extension']);

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $newFilename = uniqid('upd_') . '.' . $ext;
        $destination = $uploadDir . $newFilename;
        if (move_uploaded_file($_FILES['update_image']['tmp_name'], $destination)) {
            $image_url = '/assets/uploads/images/' . $newFilename;
        } else {
            $_SESSION['error'] = "Error uploading update image.";
            header("Location: ' . BASE_URL . '/ngo/edit_campaign.php?id=" . $campaign_id);
            exit;
        }
    } else {
        $_SESSION['error'] = "Invalid image format for update.";
        header("Location: ' . BASE_URL . '/ngo/edit_campaign.php?id=" . $campaign_id);
        exit;
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO campaign_updates (campaign_id, message, image_url, status, submitted_by) VALUES (?, ?, ?, 'pending', ?)");
    $stmt->execute([$campaign_id, $message, $image_url, $_SESSION['user_id']]);
    $_SESSION['success'] = "Update submitted for admin review. It will appear publicly once approved.";
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to submit update: " . $e->getMessage();
}

header("Location: " . BASE_URL . "/ngo/dashboard.php");
exit;
