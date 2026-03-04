<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /share_hope/login.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Please fill in both email and password.";
    header("Location: /share_hope/login.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] === 'suspended') {
            $_SESSION['error'] = "Your account has been suspended. Please contact support.";
            header("Location: /share_hope/login.php");
            exit;
        }

        // Login success
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: /share_hope/admin/dashboard.php");
        } elseif ($user['role'] === 'ngo') {
            header("Location: /share_hope/ngo/dashboard.php");
        } else {
            header("Location: /share_hope/donor/dashboard.php");
        }
        exit;
    } else {
        $_SESSION['error'] = "Invalid email or password.";
        header("Location: /share_hope/login.php");
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Login failed: System Error.";
    header("Location: /share_hope/login.php");
    exit;
}
