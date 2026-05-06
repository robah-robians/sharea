<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/security.php';

$success = false;
$error = '';

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    $result = verify_email_token($pdo, $token);
    
    if ($result['valid']) {
        $success = true;
    } else {
        $error = $result['message'];
    }
} else {
    $error = "No verification token provided.";
}
?>

<div style="min-height: 60vh; display: flex; align-items: center; justify-content: center; padding: 2rem;">
    <div style="max-width: 500px; width: 100%; background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border); padding: 3rem 2rem; text-align: center;">
        
        <?php if ($success): ?>
            <div style="margin-bottom: 1.5rem;">
                <div style="background: var(--secondary); color: white; width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.5rem;">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h2 style="color: var(--text-main); margin: 0 0 0.5rem 0;">Email Verified!</h2>
                <p style="color: var(--text-muted); margin: 0 0 1.5rem 0;">Your email address has been successfully verified.</p>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">Log In to Your Account</a>
            </div>
        <?php else: ?>
            <div style="margin-bottom: 1.5rem;">
                <div style="background: var(--danger); color: white; width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.5rem;">
                    <i class="fa-solid fa-xmark"></i>
                </div>
                <h2 style="color: var(--text-main); margin: 0 0 0.5rem 0;">Verification Failed</h2>
                <p style="color: var(--text-muted); margin: 0 0 1.5rem 0;"><?= h($error) ?></p>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0;">
                    <a href="<?= BASE_URL ?>/login.php" style="color: var(--primary); text-decoration: none;">Return to login</a> or 
                    <a href="<?= BASE_URL ?>/register.php" style="color: var(--primary); text-decoration: none;">register again</a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
