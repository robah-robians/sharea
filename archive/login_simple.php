<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Share Hope</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="container" style="padding: 4rem 0;">
        <div style="max-width: 400px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2 style="text-align: center; margin-bottom: 2rem;">Login to Share Hope</h2>
            
            <?php if ($error): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form action="<?= BASE_URL ?>/actions/login_action.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                
                <div style="margin-bottom: 1rem;">
                    <label>Email Address</label>
                    <input type="email" name="email" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label>Password</label>
                    <input type="password" name="password" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <button type="submit" style="width: 100%; padding: 0.75rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Login
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 1rem;">
                <a href="<?= BASE_URL ?>/register.php">Don't have an account? Register here</a>
            </div>
            
            <div style="text-align: center; margin-top: 1rem;">
                <a href="<?= BASE_URL ?>/index_simple.php">← Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>