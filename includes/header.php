<?php
// includes/header.php
if (ob_get_level() === 0) ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Validate session integrity
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if (!isset($_SESSION['login_time']) || !isset($_SESSION['login_validated'])) {
        $_SESSION = []; session_destroy(); session_start();
    } elseif ((time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
        $_SESSION = []; session_destroy(); session_start();
    }
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/activity_logger.php';

$current_page = basename($_SERVER['PHP_SELF']);
$is_homepage  = ($current_page === 'index.php' || isset($_GET['force_homepage']));

$unread_notifications_count = 0;
$unread_notifications       = [];
$announcements_count        = 0;
$recent_announcements       = [];

try {
    $result = $pdo->query("SELECT COUNT(*) as count FROM announcements WHERE is_public = 1")->fetch();
    $announcements_count  = $result['count'] ?? 0;
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE is_public = 1 ORDER BY created_at DESC LIMIT 3");
    $stmt->execute();
    $recent_announcements = $stmt->fetchAll();
} catch (Exception $e) {}

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$_SESSION['user_id']]);
        $unread_notifications = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $unread_notifications_count = $stmt->fetch()['count'];
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> | Empowering Change</title>
    <meta name="description" content="A premium donation platform connecting NGOs and private donors for maximum impact.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= CSS_URL ?>/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <header class="glass-header">
        <div class="container header-container">
            <a href="<?= BASE_URL ?>/" class="logo">
                <i class="fa-solid fa-hands-holding-circle"></i>
                SHARE <span>HOPE</span>
            </a>
            <nav class="main-nav">
                <ul>
                    <li><a href="<?= BASE_URL ?>/">Home</a></li>
                    <li><a href="<?= BASE_URL ?>/campaigns.php">Campaigns</a></li>
                    <li><a href="<?= BASE_URL ?>/impact.php">Impact</a></li>
                    <li><a href="<?= BASE_URL ?>/about.php">About</a></li>
                </ul>
            </nav>
            <div class="header-actions" style="display: flex; align-items: center; gap: 1rem;">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= BASE_URL ?>/<?= $_SESSION['user_role'] === 'admin' ? 'admin' : ($_SESSION['user_role'] === 'ngo' ? 'ngo' : 'donor') ?>/dashboard.php" class="btn btn-outline">Dashboard</a>
                    <a href="<?= BASE_URL ?>/actions/logout_action.php" class="btn btn-text">Logout</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-text">Log In</a>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>
                <button class="mobile-menu-toggle"><i class="fa-solid fa-bars"></i></button>
            </div>
        </div>
    </header>

    <?php if (isset($_SESSION['user_id'])): ?>
    <div style="position: fixed; top: 20px; right: 20px; z-index: 9999;" id="notificationDropdown">
        <button onclick="document.getElementById('notifMenu').style.display = document.getElementById('notifMenu').style.display === 'block' ? 'none' : 'block';"
            class="btn btn-text" style="padding: 0.75rem; position: relative; background: rgba(255,255,255,0.95); border-radius: 50%; box-shadow: 0 4px 12px rgba(0,0,0,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
            <i class="fa-solid fa-bell" style="font-size: 1.4rem; color: var(--brand-notification);"></i>
            <?php if ($unread_notifications_count > 0): ?>
                <span id="notificationCounter" style="position: absolute; top: -2px; right: -2px; background: var(--brand-notification); color: white; width: 20px; height: 20px; border-radius: 50%; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; font-weight: 700; border: 2px solid white; animation: pulse 2s infinite;">
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
                    <form action="<?= BASE_URL ?>/actions/mark_notifications_read.php" method="POST" style="margin: 0;">
                        <button type="submit" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; font-size: 0.75rem; cursor: pointer; padding: 0.5rem 1rem; border-radius: var(--radius-sm); font-weight: 600;">Mark All Read</button>
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
                            <div class="icon-circle"><i class="fa-solid fa-bell"></i></div>
                            <div class="dropdown-item-content">
                                <div class="dropdown-item-title"><?= h($notif['message']) ?></div>
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
    var notifBtn  = document.querySelector("#notificationDropdown > button");
    var notifMenu = document.getElementById("notifMenu");
    function closeMenus() { if (notifMenu) notifMenu.style.display = "none"; }
    document.addEventListener("click", function (e) {
        var inNotif = notifMenu && (notifMenu.contains(e.target) || (notifBtn && notifBtn.contains(e.target)));
        if (!inNotif) closeMenus();
    });
    window.addEventListener("resize", closeMenus);
})();

<?php if (isset($_SESSION['user_id'])): ?>
setInterval(function () {
    fetch('<?= BASE_URL ?>/api/get_notification_count.php')
        .then(r => r.json())
        .then(data => {
            const counter    = document.getElementById('notificationCounter');
            const bellButton = document.querySelector('#notificationDropdown > button');
            if (data.count > 0) {
                if (counter) {
                    counter.textContent = data.count > 9 ? '9+' : data.count;
                } else {
                    const n = document.createElement('span');
                    n.id = 'notificationCounter';
                    n.style.cssText = 'position:absolute;top:-2px;right:-2px;background:#ef4444;color:white;width:20px;height:20px;border-radius:50%;font-size:0.7rem;display:flex;align-items:center;justify-content:center;font-weight:700;border:2px solid white;animation:pulse 2s infinite;';
                    n.textContent = data.count > 9 ? '9+' : data.count;
                    bellButton.appendChild(n);
                }
            } else if (counter) { counter.remove(); }
        })
        .catch(() => {});
}, 30000);
<?php endif; ?>
</script>

<style>
@keyframes pulse {
    0%   { transform: scale(1);   opacity: 1; }
    50%  { transform: scale(1.1); opacity: 0.8; }
    100% { transform: scale(1);   opacity: 1; }
}
@media (max-width: 768px) {
    #notificationDropdown { top: 15px !important; right: 15px !important; }
    #notifMenu { min-width: 300px !important; max-width: calc(100vw - 30px) !important; }
}
</style>

    <div class="header-spacer"></div>
    <main class="main-content">
<script>const BASE_URL = '<?= BASE_URL ?>';</script>
