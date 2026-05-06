<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Please fill in both email and password.";
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

if (!check_login_rate_limit($pdo, $email)) {
    $_SESSION['error'] = "Too many login attempts. Please try again in 15 minutes.";
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if (is_account_locked($pdo, $user['id'])) {
            $_SESSION['error'] = "Account locked due to too many failed attempts. Try again in 30 minutes.";
            header("Location: " . BASE_URL . "/login.php");
            exit;
        }
    }

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] === 'suspended') {
            $_SESSION['error'] = "Your account has been suspended. Please contact support.";
            header("Location: " . BASE_URL . "/login.php");
            exit;
        }

        // Login success
        reset_failed_login_attempts($pdo, $user['id']);
        log_login_attempt($pdo, $email, true, $user['id']);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
        
        // Determine role level — explicit overrides take priority over DB value
        if ($user['email'] === 'admin@sharehope.org' || $user['role'] === 'super_admin') {
            $role_level = 3;
        } elseif ($user['role'] === 'admin') {
            $role_level = max(2, intval($user['role_level'] ?? 2));
        } else {
            $role_level = intval($user['role_level'] ?? 1);
        }
        $_SESSION['role_level'] = $role_level;
        
        $_SESSION['login_time'] = time();
        $_SESSION['login_validated'] = true;

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: " . BASE_URL . "/admin/dashboard.php");
        } elseif ($user['role'] === 'ngo') {
            header("Location: " . BASE_URL . "/ngo/dashboard.php");
        } else {
            header("Location: " . BASE_URL . "/donor/dashboard.php");
        }
        exit;
    } else {
        if ($user) {
            $lock_status = handle_failed_login($pdo, $user['id']);
            if ($lock_status === 'locked') {
                $_SESSION['error'] = "Account locked due to too many failed attempts. Try again in 30 minutes.";
            } else {
                $_SESSION['error'] = "Invalid email or password.";
            }
            log_login_attempt($pdo, $email, false, $user['id']);
        } else {
            $_SESSION['error'] = "Invalid email or password.";
            log_login_attempt($pdo, $email, false, null);
        }
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Login failed: System Error.";
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
