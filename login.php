<?php
session_start();

// Ensure CSRF token is available for login form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="padding-top: 4rem; padding-bottom: 4rem;">
    <div class="auth-container" style="margin-top: 0; border: 1px solid var(--border); box-shadow: var(--shadow-lg); overflow: hidden;">
        <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 2rem; text-align: center;">
            <h2 class="auth-title" style="color: white; margin: 0; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">Welcome Back</h2>
            <p class="auth-subtitle" style="color: rgba(255,255,255,0.9); margin: 0.5rem 0 0 0; text-shadow: 0 1px 2px rgba(0,0,0,0.1);">Log in to continue your journey.</p>
        </div>
        <div style="padding: 2.5rem;">

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

        <form action="actions/login_action.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="password_field" name="password" class="form-control" required style="flex: 1; padding-right: 2.5rem;">
                    <button type="button" onclick="togglePassword('password_field')" class="btn btn-text" style="position: absolute; right: 0.5rem; padding: 0.5rem; color: var(--text-muted);">
                        <i id="pwd_icon" class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-bottom: 1.5rem;">
                <a href="#" class="text-primary" style="font-size: 0.875rem; font-weight: 500;">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; background: linear-gradient(135deg, var(--primary), var(--accent)); border: none; box-shadow: 0 4px 14px rgba(0, 102, 255, 0.39);">Log In</button>
            <p style="text-align: center; margin-top: 1.5rem; color: var(--text-muted);">
                Don't have an account? <a href="/share_hope/register.php" class="text-primary" style="font-weight: 500;">Register
                    here</a>.
            </p>
        </form>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById('pwd_icon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
