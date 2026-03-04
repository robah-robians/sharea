<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lockFile = __DIR__ . '/.maintenance_lock';

// If lockfile is gone, or if user is admin, redirect back to home
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
if (!file_exists($lockFile) || $isAdmin) {
    header("Location: /share_hope/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Maintenance | SHARE HOPE</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/share_hope/assets/css/style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body
    style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);">

    <div
        style="text-align: center; max-width: 600px; padding: 3rem; background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); border: 1px solid var(--border);">
        <i class="fa-solid fa-person-digging text-primary" style="font-size: 5rem; margin-bottom: 2rem;"></i>
        <h1 style="font-size: 2.5rem; margin-bottom: 1rem; color: var(--text-main);">We'll be right back</h1>
        <p style="font-size: 1.125rem; color: var(--text-muted); line-height: 1.8; margin-bottom: 2rem;">
            Share Hope is currently undergoing scheduled maintenance to upgrade our systems and ensure maximum security
            for your donations. We appreciate your patience and will be back online shortly!
        </p>

        <div
            style="padding: 1rem; background: rgba(245, 158, 11, 0.1); border-radius: var(--radius-md); border-left: 4px solid var(--accent); display: inline-block; text-align: left;">
            <p style="margin: 0; font-size: 0.875rem; color: var(--text-main);"><strong>Note:</strong> All active
                campaigns and pending donations are completely safe and secure during this process.</p>
        </div>
    </div>

</body>

</html>