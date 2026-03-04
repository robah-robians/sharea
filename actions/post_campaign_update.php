<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header("Location: /share_hope/login.php");
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
    header("Location: /share_hope/ngo/dashboard.php");
    exit;
}

if (empty($message)) {
    $_SESSION['error'] = "Update message cannot be empty.";
    header("Location: /share_hope/ngo/edit_campaign.php?id=" . $campaign_id);
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
            header("Location: /share_hope/ngo/edit_campaign.php?id=" . $campaign_id);
            exit;
        }
    } else {
        $_SESSION['error'] = "Invalid image format for update.";
        header("Location: /share_hope/ngo/edit_campaign.php?id=" . $campaign_id);
        exit;
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO campaign_updates (campaign_id, message, image_url) VALUES (?, ?, ?)");
    $stmt->execute([$campaign_id, $message, $image_url]);
    $_SESSION['success'] = "Impact update posted successfully!";
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to post update: " . $e->getMessage();
}

header("Location: /share_hope/ngo/edit_campaign.php?id=" . $campaign_id);
exit;
