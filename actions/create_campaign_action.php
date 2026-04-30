<?php
// actions/create_campaign_action.php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /share_hope/admin/dashboard.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized. Admin access required.";
    header("Location: /share_hope/login.php");
    exit;
}

$title = trim($_POST['title'] ?? '');
$goal_amount = floatval($_POST['goal_amount'] ?? 0);
$deadline = $_POST['deadline'] ?? '';
$deployment_date = $_POST['deployment_date'] ?? '';
$deployment_time = $_POST['deployment_time'] ?? '';
$deployment_details = trim($_POST['deployment_details'] ?? '');
$category_id = intval($_POST['category_id'] ?? 0);
$description = trim($_POST['description'] ?? '');

if (empty($title) || $goal_amount <= 0 || empty($deadline) || empty($deployment_date) || empty($deployment_time) || empty($description)) {
    $_SESSION['error'] = "Please provide all required campaign details including deployment date and time.";
    header("Location: /share_hope/admin/campaigns_hub.php?tab=deploy");
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
            $image_url = '/share_hope/assets/uploads/campaigns/' . $newFilename;
        }
    }
}

try {
    // Check if deployment columns exist
    $check_columns = $pdo->query("SHOW COLUMNS FROM campaigns LIKE 'deployment_date'");
    $has_deployment_cols = $check_columns->rowCount() > 0;
    
    if ($has_deployment_cols) {
        $stmt = $pdo->prepare("INSERT INTO campaigns (ngo_id, title, description, goal_amount, deadline, deployment_date, deployment_time, deployment_details, category_id, image_url, status) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$title, $description, $goal_amount, $deadline, $deployment_date, $deployment_time, $deployment_details, $category_id, $image_url]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO campaigns (ngo_id, title, description, goal_amount, deadline, category_id, image_url, status) VALUES (NULL, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$title, $description, $goal_amount, $deadline, $category_id, $image_url]);
    }
    
    $_SESSION['success'] = "Initiative systematically deployed!";
    $redirect = $_POST['redirect_url'] ?? "/share_hope/admin/campaigns_hub.php?tab=performance";
    header("Location: " . $redirect);
    exit;
} catch (PDOException $e) {
    error_log("Campaign creation error: " . $e->getMessage());
    $_SESSION['error'] = "Deployment failure: " . $e->getMessage();
    $redirect = $_POST['redirect_url'] ?? "/share_hope/admin/campaigns_hub.php?tab=deploy";
    header("Location: " . $redirect);
    exit;
} catch (Exception $e) {
    error_log("Campaign creation error: " . $e->getMessage());
    $_SESSION['error'] = "Deployment failure. Signal interruption during initialization.";
    $redirect = $_POST['redirect_url'] ?? "/share_hope/admin/campaigns_hub.php?tab=deploy";
    header("Location: " . $redirect);
    exit;
}
