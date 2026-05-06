<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($token) || empty($password) || $password !== $confirm_password || strlen($password) < 8) {
    $_SESSION['error'] = "Passwords must match and be at least 8 characters long.";
    header("Location: ' . BASE_URL . '/reset_password.php?token=" . urlencode($token));
    exit;
}

try {
    // Verify token exists and hasn't expired
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires_at > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error'] = "The reset link is invalid or has expired. Please request a new one.";
        header("Location: " . BASE_URL . "/forgot_password.php");
        exit;
    }

    // Update password, nullify token
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
    $stmt->execute([$new_hash, $user['id']]);

    $_SESSION['success'] = "Your password has been successfully reset! You can now log in.";
    header("Location: " . BASE_URL . "/login.php");
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = "System Error. Please try again.";
    header("Location: " . BASE_URL . "/forgot_password.php");
    exit;
}
