<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /share_hope/forgot_password.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    $_SESSION['error'] = "Please enter your email address.";
    header("Location: /share_hope/forgot_password.php");
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $user['id']]);

        require_once __DIR__ . '/includes/mailer.php';
        $reset_link = "http://localhost/share_hope/reset_password.php?token=" . $token;

        $body = "Hello,\n\nWe received a request to reset your Share Hope password.\n\n";
        $body .= "Please click the following link to securely reset your password:\n<a href='{$reset_link}'>{$reset_link}</a>\n\n";
        $body .= "If you did not request this, please ignore this email. This link will expire in 1 hour.\n\n";

        send_mock_email($email, "Password Reset Request", $body);

        $_SESSION['success'] = "If that email exists in our system, a password reset link has been sent to it. <br><a href='/share_hope/email_sandbox.php' style='text-decoration: underline; color: #ffeb3b; font-weight: bold;'>Click here to view Mock Inbox</a>";
    } else {
        // Privacy best practice: don't reveal if an email exists or not
        $_SESSION['success'] = "If that email exists in our system, a password reset link has been sent to it. <br><a href='/share_hope/email_sandbox.php' style='text-decoration: underline; color: #ffeb3b; font-weight: bold;'>Click here to view Mock Inbox</a>";
    }
    header("Location: /share_hope/login.php");
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = "System Error. Please try again later.";
    header("Location: /share_hope/forgot_password.php");
    exit;
}
