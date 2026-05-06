<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Simple auth check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header("Location: " . BASE_URL . "/login_simple.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>NGO Dashboard - Share Hope</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container" style="padding: 2rem 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>NGO Dashboard</h1>
            <div>
                <span>Welcome, <?= htmlspecialchars($user_name) ?></span>
                <a href="<?= BASE_URL ?>/actions/logout_action.php" style="margin-left: 1rem;">Logout</a>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; text-align: center;">
                <i class="fa-solid fa-satellite-dish" style="font-size: 2rem; color: #007bff; margin-bottom: 0.5rem;"></i>
                <h3>Field Ops</h3>
                <p>Track your synchronized deployments</p>
                <a href="<?= BASE_URL ?>/ngo/dashboard.php" class="btn btn-primary">View Assignments</a>
            </div>
            
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; text-align: center;">
                <i class="fa-solid fa-chart-line" style="font-size: 2rem; color: #28a745; margin-bottom: 0.5rem;"></i>
                <h3>Node Metrics</h3>
                <p>Impact reports for your NGO</p>
                <a href="<?= BASE_URL ?>/ngo/reports.php" class="btn btn-outline">Analytics Terminal</a>
            </div>
            
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; text-align: center;">
                <i class="fa-solid fa-server" style="font-size: 2rem; color: #ffc107; margin-bottom: 0.5rem;"></i>
                <h3>Node Profile</h3>
                <p>Manage NGO profile and details</p>
                <a href="<?= BASE_URL ?>/ngo/profile.php" class="btn btn-outline">Profile Hub</a>
            </div>
        </div>
        
        <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3>System Status</h3>
            <p>Your NGO is currently partnered with the Global Campaigns Hub. All initiatives must be authorized by Admin.</p>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="<?= BASE_URL ?>/index.php">← Back to System Portal</a>
        </div>
    </div>
</body>
</html>