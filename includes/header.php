<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/check_maintenance.php';

$unread_notifications_count = 0;
$unread_notifications = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $unread_notifications = $stmt->fetchAll();

    $stmt_count = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt_count->execute([$_SESSION['user_id']]);
    $unread_notifications_count = $stmt_count->fetch()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHARE HOPE | Empowering Change</title>
    <meta name="description"
        content="A premium donation platform connecting NGOs and private donors for maximum impact.">
    <!-- Open Graph / SEO Meta Tags -->
    <meta property="og:title" content="SHARE HOPE | Empowering Change">
    <meta property="og:description"
        content="A premium donation platform connecting NGOs and private donors for maximum impact in Kenya.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="/share_hope/assets/uploads/images/Together__We_Can_Make_a_Difference_.jpg">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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
            <nav class="main-nav" id="main-nav">
                <ul>
                    <li><a href="/share_hope/"><i class="fa-solid fa-house" style="margin-right: 5px;"></i> Home</a>
                    </li>
                    <li><a href="/share_hope/campaigns.php"><i class="fa-solid fa-hand-holding-heart"
                                style="margin-right: 5px;"></i> Campaigns</a></li>
                    <li><a href="/share_hope/ngos.php"><i class="fa-solid fa-users" style="margin-right: 5px;"></i> Our
                            NGOs</a></li>
                    <li><a href="/share_hope/about.php"><i class="fa-solid fa-circle-info"
                                style="margin-right: 5px;"></i> About</a></li>

                    <!-- Mobile Auth Links (Hidden on Desktop) -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php $uRole = $_SESSION['user_role'] ?? 'donor'; ?>
                        <li class="mobile-only"><a
                                href="/share_hope/<?= $uRole === 'admin' ? 'admin' : ($uRole === 'ngo' ? 'ngo' : 'donor') ?>/dashboard.php"
                                style="color: var(--primary); font-weight: 700;">Dashboard</a></li>
                        <li class="mobile-only"><a href="/share_hope/actions/logout_action.php">Logout</a></li>
                    <?php else: ?>
                        <li class="mobile-only"><a href="/share_hope/login.php"
                                style="color: var(--primary); font-weight: 700;">Log In</a></li>
                        <li class="mobile-only"><a href="/share_hope/register.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="header-actions" style="display: flex; align-items: center; gap: 1rem;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div style="position: relative; display: inline-block;" id="notificationDropdown">
                        <button
                            onclick="document.getElementById('notifMenu').style.display = document.getElementById('notifMenu').style.display === 'block' ? 'none' : 'block';"
                            class="btn btn-text" style="padding: 0.5rem; position: relative;">
                            <i class="fa-solid fa-bell" style="font-size: 1.25rem; color: var(--text-main);"></i>
                            <?php if ($unread_notifications_count > 0): ?>
                                <span
                                    style="position: absolute; top: 0; right: 0; background: var(--danger); color: white; width: 18px; height: 18px; border-radius: 50%; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; font-weight: 700; border: 2px solid white;"><?= $unread_notifications_count ?></span>
                            <?php endif; ?>
                        </button>
                        <div id="notifMenu"
                            style="display: none; position: absolute; right: 0; top: 120%; width: 300px; background: white; border-radius: var(--radius-md); box-shadow: 0 10px 25px rgba(0,0,0,0.1); border: 1px solid var(--border); overflow: hidden; z-index: 100;">
                            <div
                                style="padding: 1rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: var(--background);">
                                <h4 style="margin: 0; font-size: 0.875rem;">Notifications</h4>
                                <?php if ($unread_notifications_count > 0): ?>
                                    <form action="/share_hope/actions/mark_notifications_read.php" method="POST"
                                        style="margin: 0;">
                                        <button type="submit"
                                            style="background: none; border: none; color: var(--primary); font-size: 0.75rem; cursor: pointer; text-decoration: underline;">Mark
                                            all read</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div style="max-height: 300px; overflow-y: auto;">
                                <?php if (empty($unread_notifications)): ?>
                                    <div
                                        style="padding: 1.5rem; text-align: center; color: var(--text-muted); font-size: 0.875rem;">
                                        No new notifications.</div>
                                <?php else: ?>
                                    <?php foreach ($unread_notifications as $notif): ?>
                                        <div
                                            style="padding: 1rem; border-bottom: 1px solid var(--border); display: flex; gap: 0.75rem; align-items: flex-start; background: rgba(79, 70, 229, 0.03);">
                                            <div
                                                style="background: var(--primary); color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; flex-shrink: 0;">
                                                <i class="fa-solid fa-bell"></i>
                                            </div>
                                            <div>
                                                <div style="font-size: 0.875rem; color: var(--text-main); line-height: 1.4;">
                                                    <?= h($notif['message']) ?>
                                                </div>
                                                <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.25rem;"><i
                                                        class="fa-regular fa-clock"></i>
                                                    <?= date('M j, g:i A', strtotime($notif['created_at'])) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php $uRole = $_SESSION['user_role'] ?? 'donor'; ?>
                    <a href="/share_hope/<?= $uRole === 'admin' ? 'admin' : ($uRole === 'ngo' ? 'ngo' : 'donor') ?>/dashboard.php"
                        class="btn btn-outline">Dashboard</a>
                    <a href="/share_hope/actions/logout_action.php" class="btn btn-text">Logout</a>
                <?php else: ?>
                    <a href="/share_hope/login.php" class="btn btn-text">Log In</a>
                    <a href="/share_hope/register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
                <button class="mobile-menu-toggle" id="mobile-toggle"><i class="fa-solid fa-bars"></i></button>
            </div>
        </div>
    </header>

    <!-- Mobile Navigation Script -->
    <script>
        document.getElementById('mobile-toggle').addEventListener('click', function () {
            document.getElementById('main-nav').classList.toggle('show-mobile');
        });
    </script>

    <div class="header-spacer"></div>
    <main class="main-content">