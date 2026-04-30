<?php
// includes/header.php
if (ob_get_level() === 0) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'samesite' => 'Strict'
    ]);
    session_start();
}

// CRITICAL: Validate session integrity to prevent auto-login
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    // Check if session has required validation timestamp
    if (!isset($_SESSION['login_time']) || !isset($_SESSION['login_validated'])) {
        // Invalid session - clear it
        $_SESSION = array();
        session_destroy();
        session_start();
    } else {
        // Check session timeout (4 hours)
        if ((time() - $_SESSION['login_time']) > 14400) {
            $_SESSION = array();
            session_destroy();
            session_start();
        }
    }
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/activity_logger.php';

// Check if we're on the homepage - if so, don't redirect
$current_page = basename($_SERVER['PHP_SELF']);
$is_homepage = ($current_page === 'index.php' || isset($_GET['force_homepage']));

$unread_notifications_count = 0;
$unread_notifications = [];
$announcements_count = 0;
$recent_announcements = [];

// Get announcements
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM announcements WHERE is_public = 1");
    $result = $stmt->fetch();
    $announcements_count = $result['count'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE is_public = 1 ORDER BY created_at DESC LIMIT 3");
    $stmt->execute();
    $recent_announcements = $stmt->fetchAll();
} catch (Exception $e) {
    // Ignore errors
}

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$_SESSION['user_id']]);
        $unread_notifications = $stmt->fetchAll();

        $stmt_count = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt_count->execute([$_SESSION['user_id']]);
        $unread_notifications_count = $stmt_count->fetch()['count'];
    } catch (Exception $e) {
        // Ignore errors
    }
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
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Main CSS -->
    <link rel="stylesheet" href="/share_hope/assets/css/style.css?v=<?= time() ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
                    <li><a href="/share_hope/impact.php">Impact</a></li>
                    <li><a href="/share_hope/about.php">About</a></li>
                </ul>
            </nav>
            <div class="header-actions" style="display: flex; align-items: center; gap: 1rem;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/share_hope/<?= $_SESSION['user_role'] === 'admin' ? 'admin' : ($_SESSION['user_role'] === 'ngo' ? 'ngo' : 'donor') ?>/dashboard.php"
                        class="btn btn-outline">Dashboard</a>
                    <a href="/share_hope/actions/logout_action.php" class="btn btn-text">Logout</a>
                <?php else: ?>
                    <a href="/share_hope/login.php" class="btn btn-text">Log In</a>
                    <a href="/share_hope/register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
                <button class="mobile-menu-toggle"><i class="fa-solid fa-bars"></i></button>
            </div>
        </div>
    </header>

    <!-- Notification Bell - Top Right Corner -->
    <?php if (isset($_SESSION['user_id'])): ?>
        <div style="position: fixed; top: 20px; right: 20px; z-index: 9999;" id="notificationDropdown">
            <button
                onclick="document.getElementById('notifMenu').style.display = document.getElementById('notifMenu').style.display === 'block' ? 'none' : 'block';"
                class="btn btn-text" style="padding: 0.75rem; position: relative; background: rgba(255, 255, 255, 0.95); border-radius: 50%; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <i class="fa-solid fa-bell" style="font-size: 1.4rem; color: #ef4444;"></i>
                <?php if ($unread_notifications_count > 0): ?>
                    <span id="notificationCounter" style="position: absolute; top: -2px; right: -2px; background: #ef4444; color: white; width: 20px; height: 20px; border-radius: 50%; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; font-weight: 700; border: 2px solid white; animation: pulse 2s infinite;">
                        <?= min($unread_notifications_count, 9) ?><?= $unread_notifications_count > 9 ? '+' : '' ?>
                    </span>
                <?php endif; ?>
            </button>
            <div id="notifMenu" class="dropdown-menu" style="position: absolute; top: 100%; right: 0; margin-top: 0.5rem; min-width: 350px;">
                <div class="dropdown-header brand">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="background: rgba(255,255,255,0.2); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-bell" style="color: white; font-size: 1.1rem;"></i>
                        </div>
                        <div>
                            <h4>🔔 Notifications</h4>
                            <p><?= $unread_notifications_count ?> unread messages</p>
                        </div>
                    </div>
                    <?php if ($unread_notifications_count > 0): ?>
                        <form action="/share_hope/actions/mark_notifications_read.php" method="POST" style="margin: 0;">
                            <button type="submit"
                                style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; font-size: 0.75rem; cursor: pointer; padding: 0.5rem 1rem; border-radius: var(--radius-sm); font-weight: 600; transition: all 0.3s;">Mark All Read</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="dropdown-body">
                    <?php if (empty($unread_notifications)): ?>
                        <div style="padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.875rem;">
                            <i class="fa-solid fa-check-circle" style="font-size: 2rem; color: var(--success); margin-bottom: 1rem;"></i>
                            <p style="margin: 0;">All caught up! No new notifications.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($unread_notifications as $notif): ?>
                            <div class="dropdown-item">
                                <div class="icon-circle">
                                    <i class="fa-solid fa-bell"></i>
                                </div>
                                <div class="dropdown-item-content">
                                    <div class="dropdown-item-title">
                                        <?= h($notif['message']) ?>
                                    </div>
                                    <div class="dropdown-item-meta">
                                        <span><i class="fa-regular fa-clock"></i> <?= date('M j, g:i A', strtotime($notif['created_at'])) ?></span>
                                        <span style="background: var(--background); color: var(--text-main); border: 1px solid var(--border); padding: 0.2rem 0.6rem; border-radius: 999px; font-weight: 600;">Admin Update</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

<script>
(function () {
  var notifBtn = document.querySelector("#notificationDropdown > button");
  var notifMenu = document.getElementById("notifMenu");
  function closeMenus(){ if (notifMenu) notifMenu.style.display="none"; }
  document.addEventListener("click", function (e) {
    var inNotif = notifMenu && (notifMenu.contains(e.target) || (notifBtn && notifBtn.contains(e.target)));
    if (!inNotif) closeMenus();
  });
  window.addEventListener("resize", closeMenus);
})();

// Auto-update notification counter
<?php if (isset($_SESSION['user_id'])): ?>
setInterval(function() {
    fetch('/share_hope/api/get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            const counter = document.getElementById('notificationCounter');
            const bellButton = document.querySelector('#notificationDropdown > button');
            
            if (data.count > 0) {
                if (counter) {
                    counter.textContent = data.count > 9 ? '9+' : data.count;
                } else {
                    // Create counter if it doesn't exist
                    const newCounter = document.createElement('span');
                    newCounter.id = 'notificationCounter';
                    newCounter.style.cssText = 'position: absolute; top: -2px; right: -2px; background: #ef4444; color: white; width: 20px; height: 20px; border-radius: 50%; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; font-weight: 700; border: 2px solid white; animation: pulse 2s infinite;';
                    newCounter.textContent = data.count > 9 ? '9+' : data.count;
                    bellButton.appendChild(newCounter);
                }
            } else {
                if (counter) {
                    counter.remove();
                }
            }
        })
        .catch(error => console.log('Notification check failed:', error));
}, 30000); // Check every 30 seconds
<?php endif; ?>
</script>

<style>
@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

/* Responsive adjustments for notification bell */
@media (max-width: 768px) {
    #notificationDropdown {
        top: 15px !important;
        right: 15px !important;
    }
    
    #notifMenu {
        min-width: 300px !important;
        max-width: calc(100vw - 30px) !important;
    }
}
</style>


    <div class="header-spacer"></div>
    <main class="main-content">


