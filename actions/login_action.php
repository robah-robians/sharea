<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /share_hope/login.php");
    exit;
}

// Verify CSRF token - but be more lenient during login
$csrf_token = $_POST['csrf_token'] ?? '';
if (empty($csrf_token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    // Regenerate CSRF token for next attempt
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['error'] = "Security token expired. Please try again.";
    header("Location: /share_hope/login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Please fill in both email and password.";
    header("Location: /share_hope/login.php");
    exit;
}

// Check rate limiting
if (!check_login_rate_limit($pdo, $email)) {
    $_SESSION['error'] = "Too many login attempts. Please try again in 15 minutes.";
    header("Location: /share_hope/login.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        log_login_attempt($pdo, $email, false);
        $_SESSION['error'] = "Invalid email or password.";
        header("Location: /share_hope/login.php");
        exit;
    }

    // Check if account is locked
    if (is_account_locked($pdo, $user['id'])) {
        $_SESSION['error'] = "Account temporarily locked due to multiple failed login attempts. Try again in 30 minutes.";
        header("Location: /share_hope/login.php");
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        log_login_attempt($pdo, $email, false, $user['id']);
        handle_failed_login($pdo, $user['id']);
        $_SESSION['error'] = "Invalid email or password.";
        header("Location: /share_hope/login.php");
        exit;
    }

    // Check if email is verified
    // TEMPORARILY DISABLED FOR TESTING - ENABLE LATER
    // if (!$user['email_verified']) {
    //     $_SESSION['error'] = "Please verify your email before logging in.";
    //     $_SESSION['unverified_email'] = $email;
    //     header("Location: ../login.php");
    //     exit;
    // }

    if ($user['status'] === 'suspended') {
        log_login_attempt($pdo, $email, false, $user['id']);
        $_SESSION['error'] = "Your account has been suspended. Please contact support.";
        header("Location: /share_hope/login.php");
        exit;
    }

    // Login success
    log_login_attempt($pdo, $email, true, $user['id']);
    reset_failed_login_attempts($pdo, $user['id']);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['login_time'] = time();
    $_SESSION['login_validated'] = true; // Required for session validation

    // Redirect based on role
    if ($user['role'] === 'admin') {
        header("Location: /share_hope/admin/dashboard.php");
    } elseif ($user['role'] === 'ngo') {
        header("Location: /share_hope/ngo/dashboard.php");
    } else {
        header("Location: /share_hope/donor/dashboard.php");
    }
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = "Login failed: System Error.";
    header("Location: /share_hope/login.php");
    exit;
}
