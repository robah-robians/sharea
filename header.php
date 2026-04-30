<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHARE HOPE | Empowering Change</title>
    <meta name="description" content="A premium donation platform connecting NGOs and private donors for maximum impact.">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Main CSS -->
    <link rel="stylesheet" href="/share_hope/assets/css/style.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header class="glass-header">
        <div class="container header-container">
            <a href="/share_hope/" class="logo">
                <i class="fa-solid fa-hands-holding-circle text-primary"></i>
                SHARE <span>HOPE</span>
            </a>
            <nav class="main-nav">
                <ul>
                    <li><a href="/share_hope/">Home</a></li>
                    <li><a href="/share_hope/campaigns.php">Campaigns</a></li>
                    <li><a href="/share_hope/ngos.php">Our NGOs</a></li>
                    <li><a href="/share_hope/about.php">About</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/share_hope/dashboard.php" class="btn btn-outline">Dashboard</a>
                    <a href="/share_hope/actions/logout_action.php" class="btn btn-text">Logout</a>
                <?php else: ?>
                    <a href="/share_hope/login.php" class="btn btn-text">Log In</a>
                    <a href="/share_hope/register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
                <button class="mobile-menu-toggle"><i class="fa-solid fa-bars"></i></button>
            </div>
        </div>
    </header>
    <div class="header-spacer"></div>
    <main class="main-content">
