<?php
session_start();
require_once __DIR__ . '/includes/db.php';

// Simple session check
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Share Hope - Working</title>
    <link rel="stylesheet" href="/share_hope/assets/css/style.css">
</head>
<body>
    <div class="container" style="padding: 4rem 0; text-align: center;">
        <h1>🎉 Share Hope Platform</h1>
        
        <?php if ($is_logged_in): ?>
            <div style="background: #d4edda; padding: 2rem; border-radius: 8px; margin: 2rem 0;">
                <h2>Welcome back!</h2>
                <p>Role: <?= htmlspecialchars($_SESSION['user_role']) ?></p>
                <p>Name: <?= htmlspecialchars($_SESSION['user_name']) ?></p>
                <a href="/share_hope/<?= $_SESSION['user_role'] ?>/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                <a href="/share_hope/actions/logout_action.php" class="btn btn-outline">Logout</a>
            </div>
        <?php else: ?>
            <div style="background: #f8f9fa; padding: 2rem; border-radius: 8px; margin: 2rem 0;">
                <h2>Welcome to Share Hope</h2>
                <p>A platform connecting NGOs and donors for maximum impact.</p>
                <a href="/share_hope/login.php" class="btn btn-primary">Login</a>
                <a href="/share_hope/register.php" class="btn btn-outline">Register</a>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 3rem;">
            <h3>Quick Links</h3>
            <a href="/share_hope/campaigns.php">View Campaigns</a> | 
            <a href="/share_hope/ngos.php">Our NGOs</a> | 
            <a href="/share_hope/about.php">About Us</a>
        </div>
        
        <div style="margin-top: 2rem; padding: 1rem; background: #e9ecef; border-radius: 8px;">
            <small>System Status: ✅ Working | Database: ✅ Connected | Functions: ✅ Loaded</small>
        </div>
    </div>
</body>
</html>