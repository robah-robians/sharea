<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die("CSRF token validation failed.");
    }

    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $target_audience = trim($_POST['target_audience'] ?? 'all');
    $admin_id = $_SESSION['user_id'];

    if (empty($title) || empty($message)) {
        die("Title and message are required.");
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO announcements (admin_id, title, message, target_audience) VALUES (?, ?, ?, ?)");
        $stmt->execute([$admin_id, $title, $message, $target_audience]);
        $_SESSION['success_message'] = "Announcement broadcasted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error broadcasting announcement: " . $e->getMessage();
    }

    header("Location: /share_hope/admin/dashboard.php");
    exit;
}
