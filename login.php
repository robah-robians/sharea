<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['donor', 'ngo', 'admin'])) {
        $role = $_SESSION['user_role'];
        header("Location: /share_hope/{$role}/dashboard.php");
        exit;
    } else {
        // Corrupt session: missing or invalid role
        session_unset();
        session_destroy();
        session_start();
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<div class="container" style="padding-top: 4rem; padding-bottom: 4rem;">
    <div class="auth-container" style="margin-top: 0;">
        <h2 class="auth-title">Welcome Back</h2>
        <p class="auth-subtitle">Log in to continue your journey.</p>

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

        <form action="/share_hope/login_action.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required autofocus autocomplete="username">
            </div>
            <div class="form-group" style="position: relative;">
                <label class="form-label">Password</label>
                <input type="password" name="password" id="password_input" class="form-control" required
                    style="padding-right: 40px;" autocomplete="current-password">
                <button type="button" onclick="togglePassword()"
                    style="position: absolute; right: 10px; top: 38px; background: none; border: none; cursor: pointer; color: var(--text-muted);">
                    <i class="fa-regular fa-eye" id="toggle_icon"></i>
                </button>
            </div>

            <script>
                function togglePassword() {
                    const passwordInput = document.getElementById('password_input');
                    const toggleIcon = document.getElementById('toggle_icon');
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        toggleIcon.classList.remove('fa-eye');
                        toggleIcon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        toggleIcon.classList.remove('fa-eye-slash');
                        toggleIcon.classList.add('fa-eye');
                    }
                }
            </script>

            <div style="display: flex; justify-content: flex-end; margin-bottom: 1.5rem;">
                <a href="/share_hope/forgot_password.php" class="text-primary"
                    style="font-size: 0.875rem; font-weight: 500;">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Log In</button>
            <p style="text-align: center; margin-top: 1.5rem; color: var(--text-muted);">
                Don't have an account? <a href="/share_hope/register.php" class="text-primary"
                    style="font-weight: 500;">Register here</a>.
            </p>
        </form>
    </div>

    <!-- Quick Login Helper for Development -->
    <div style="margin-top: 3rem; text-align: center; color: var(--text-muted); font-size: 0.875rem;">
        <h4 style="margin-bottom: 1rem;">Developer Test Accounts</h4>
        <div style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
            <div>
                <strong>Admin</strong><br>
                admin@sharehope.org<br>
                admin123
            </div>
            <div>
                <strong>NGO</strong><br>
                hope@africa.org<br>
                password123
            </div>
            <div>
                <strong>Donor</strong><br>
                john@example.com<br>
                password123
            </div>
        </div>
        <p style="margin-top: 1rem; font-style: italic; font-size: 0.75rem;">(These are test credentials hardcoded into
            your local database)</p>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>