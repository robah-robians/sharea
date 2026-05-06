<?php
session_start();
require_once __DIR__ . '/includes/header.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    $_SESSION['error'] = "Invalid or missing password reset token.";
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
?>

<div class="container" style="padding-top: 4rem; padding-bottom: 4rem;">
    <div class="auth-container" style="margin-top: 0;">
        <h2 class="auth-title">Set New Password</h2>
        <p class="auth-subtitle">Please create a new strong password.</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div
                style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                <?= h($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/actions/reset_password_action.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
            <input type="hidden" name="token" value="<?= h($token) ?>">

            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" required minlength="8"
                    autocomplete="new-password" autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="8"
                    autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Update Password</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>