<?php
session_start();
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="padding-top: 4rem; padding-bottom: 4rem;">
    <div class="auth-container" style="margin-top: 0; max-width: 500px;">
        <h2 class="auth-title">Forgot Password</h2>
        <p class="auth-subtitle">Enter your email address to reset your password.</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div
                style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                <?= h($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div
                style="background: var(--secondary); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                <?= h($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/forgot_password_action.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Send Reset Link</button>
            <p style="text-align: center; margin-top: 1.5rem; color: var(--text-muted);">
                Remember your password? <a href="<?= BASE_URL ?>/login.php" class="text-primary"
                    style="font-weight: 500;">Log in here</a>.
            </p>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>