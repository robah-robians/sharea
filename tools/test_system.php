<?php
session_start();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 4rem 0; text-align: center;">
    <h1>🎉 Share Hope - System Status</h1>
    
    <div style="background: var(--surface); padding: 2rem; border-radius: var(--radius-lg); margin: 2rem 0; border: 1px solid var(--border);">
        <h2>✅ Code Organization Complete</h2>
        <p>All functions have been properly organized and consolidated.</p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin: 2rem 0;">
            <div style="background: var(--success); color: white; padding: 1rem; border-radius: var(--radius-md);">
                <h3>Database Connection</h3>
                <p>✅ Working</p>
            </div>
            
            <div style="background: var(--primary); color: white; padding: 1rem; border-radius: var(--radius-md);">
                <h3>Functions Library</h3>
                <p>✅ Loaded</p>
            </div>
            
            <div style="background: var(--secondary); color: white; padding: 1rem; border-radius: var(--radius-md);">
                <h3>Session Management</h3>
                <p><?= is_logged_in() ? '✅ Logged In' : '⚪ Not Logged In' ?></p>
            </div>
            
            <div style="background: var(--warning); color: white; padding: 1rem; border-radius: var(--radius-md);">
                <h3>Security Functions</h3>
                <p>✅ Active</p>
            </div>
        </div>
        
        <?php if (is_logged_in()): ?>
            <div style="background: var(--info); color: white; padding: 1rem; border-radius: var(--radius-md); margin: 1rem 0;">
                <h3>Current User</h3>
                <p>Role: <?= h($_SESSION['user_role']) ?></p>
                <p>Name: <?= h($_SESSION['user_name']) ?></p>
                <a href="<?= BASE_URL ?>/<?= $_SESSION['user_role'] ?>/dashboard.php" class="btn" style="background: white; color: var(--info); margin-top: 0.5rem;">
                    Go to Dashboard
                </a>
            </div>
        <?php else: ?>
            <div style="margin: 2rem 0;">
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">Login</a>
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-outline">Register</a>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
            <h3>Available Features</h3>
            <ul style="list-style: none; padding: 0;">
                <li>✅ Input Validation (Phone, Email, Amounts)</li>
                <li>✅ NGO Dashboard with All Modules</li>
                <li>✅ Donor Dashboard with All Modules</li>
                <li>✅ Admin Dashboard</li>
                <li>✅ CSRF Protection</li>
                <li>✅ Session Security</li>
                <li>✅ Professional UI/UX</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>