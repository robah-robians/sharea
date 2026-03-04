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
$role = $_GET['role'] ?? 'donor';
if (!in_array($role, ['donor', 'ngo'])) {
    $role = 'donor';
}
?>
<div class="container" style="padding-top: 4rem; padding-bottom: 4rem;">
    <div class="auth-container" style="margin-top: 0;">
        <h2 class="auth-title">Create an Account</h2>
        <p class="auth-subtitle">Join us as a <?= ucfirst($role) ?> to make an impact.</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div
                style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                <?= h($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div style="display: flex; gap: 1rem; margin-bottom: 2rem; justify-content: center;">
            <a href="register.php?role=donor" class="btn <?= $role === 'donor' ? 'btn-primary' : 'btn-outline' ?>"
                style="flex:1;">Donor</a>
            <a href="register.php?role=ngo" class="btn <?= $role === 'ngo' ? 'btn-primary' : 'btn-outline' ?>"
                style="flex:1;">NGO</a>
        </div>

        <form action="/share_hope/actions/register_action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
            <input type="hidden" name="role" value="<?= h($role) ?>">

            <div class="form-group">
                <label class="form-label"><?= $role === 'ngo' ? 'Organization Name' : 'Full Name' ?></label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required autocomplete="email">
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number (Optional)</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required minlength="8"
                    autocomplete="new-password">
            </div>

            <?php if ($role === 'ngo'): ?>
                <div class="form-group">
                    <label class="form-label">Mission Statement</label>
                    <textarea name="mission" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Verification Document (PDF/JPG)</label>
                    <input type="file" name="verification_doc" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required
                        style="padding: 0.5rem; background: var(--border);">
                    <small style="color: var(--text-muted); display:block; margin-top: 0.25rem;">Required for admin
                        approval.</small>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Register</button>
            <p style="text-align: center; margin-top: 1.5rem; color: var(--text-muted);">
                Already have an account? <a href="/share_hope/login.php" class="text-primary"
                    style="font-weight: 500;">Log in here</a>.
            </p>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>