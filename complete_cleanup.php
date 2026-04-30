<?php
// Complete system cleanup to fix auto-login issue
session_start();

// Store debug info
$debug_info = [
    'old_session' => $_SESSION,
    'cookies' => $_COOKIE,
    'session_id' => session_id(),
    'session_save_path' => session_save_path(),
    'php_session_dir' => sys_get_temp_dir()
];

// 1. Clear all session variables
$_SESSION = array();

// 2. Destroy the session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// 3. Clear all possible cookies
$cookies_to_clear = [
    'PHPSESSID',
    'user_id', 
    'user_role',
    'login_token',
    'remember_me',
    'auth_token',
    session_name()
];

foreach ($cookies_to_clear as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        // Clear for multiple paths
        setcookie($cookie, '', time()-3600, '/');
        setcookie($cookie, '', time()-3600, '/share_hope/');
        setcookie($cookie, '', time()-3600, '/share_hope');
        setcookie($cookie, '', time()-3600);
        
        // Also try with domain
        setcookie($cookie, '', time()-3600, '/', 'localhost');
        setcookie($cookie, '', time()-3600, '/', '127.0.0.1');
    }
}

// 4. Start fresh session
session_start();
session_regenerate_id(true);

// 5. Try to clear server-side session files (if accessible)
$session_path = session_save_path();
if (empty($session_path)) {
    $session_path = sys_get_temp_dir();
}

$cleared_files = [];
if (is_dir($session_path) && is_writable($session_path)) {
    $files = glob($session_path . '/sess_*');
    foreach ($files as $file) {
        if (is_file($file) && is_writable($file)) {
            if (unlink($file)) {
                $cleared_files[] = basename($file);
            }
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete System Cleanup - Share Hope</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin: 1rem 0; border: 1px solid #c3e6cb; }
        .warning { background: #fff3cd; color: #856404; padding: 1rem; border-radius: 4px; margin: 1rem 0; border: 1px solid #ffeaa7; }
        .info { background: #d1ecf1; color: #0c5460; padding: 1rem; border-radius: 4px; margin: 1rem 0; border: 1px solid #bee5eb; }
        .btn { background: #007bff; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; display: inline-block; margin: 0.5rem; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; font-size: 0.9rem; }
        .step { background: #e9ecef; padding: 1rem; margin: 1rem 0; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Complete System Cleanup</h1>
        
        <div class="success">
            <strong>✅ Cleanup Complete!</strong> All authentication data has been cleared.
        </div>
        
        <div class="step">
            <h3>🔧 What was cleaned:</h3>
            <ul>
                <li>✅ All session variables cleared</li>
                <li>✅ Session destroyed and regenerated</li>
                <li>✅ <?= count($cookies_to_clear) ?> types of cookies cleared</li>
                <li>✅ <?= count($cleared_files) ?> server session files removed</li>
                <li>✅ Fresh session started</li>
            </ul>
        </div>
        
        <?php if (!empty($debug_info['old_session'])): ?>
        <div class="warning">
            <strong>⚠️ Previous session data found:</strong>
            <pre><?= htmlspecialchars(print_r($debug_info['old_session'], true)) ?></pre>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($cleared_files)): ?>
        <div class="info">
            <strong>🗑️ Server session files cleared:</strong>
            <ul>
                <?php foreach ($cleared_files as $file): ?>
                    <li><?= htmlspecialchars($file) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="info">
            <strong>🆕 Current status:</strong>
            <ul>
                <li><strong>New Session ID:</strong> <?= session_id() ?></li>
                <li><strong>Session Data:</strong> <?= empty($_SESSION) ? 'Empty ✅' : 'Contains data ⚠️' ?></li>
                <li><strong>Logged In:</strong> <?= isset($_SESSION['user_id']) ? 'Yes ⚠️' : 'No ✅' ?></li>
                <li><strong>Session Path:</strong> <?= htmlspecialchars($session_path) ?></li>
            </ul>
        </div>
        
        <div class="step">
            <h3>📋 Manual Steps (Important!):</h3>
            <ol>
                <li><strong>Clear Browser Data:</strong>
                    <ul>
                        <li>Press <code>Ctrl+Shift+Delete</code> (or <code>Cmd+Shift+Delete</code> on Mac)</li>
                        <li>Select "Cookies and other site data"</li>
                        <li>Select "Cached images and files"</li>
                        <li>Choose "All time" as time range</li>
                        <li>Click "Clear data"</li>
                    </ul>
                </li>
                <li><strong>Close and Reopen Browser:</strong> Completely close all browser windows and reopen</li>
                <li><strong>Test the Fix:</strong> Go to the home page and verify you're logged out</li>
            </ol>
        </div>
        
        <div style=\"text-align: center; margin-top: 2rem;\">\n            <a href=\"/share_hope/\" class=\"btn\">🏠 Go to Home Page</a>\n            <a href=\"/share_hope/login.php\" class=\"btn\">🔐 Login Page</a>\n            <a href=\"javascript:location.reload()\" class=\"btn btn-danger\">🔄 Run Cleanup Again</a>\n        </div>\n        \n        <div class=\"warning\" style=\"margin-top: 2rem;\">\n            <strong>🚨 If the issue persists:</strong>\n            <ul>\n                <li>Check if there are any test files setting session variables</li>\n                <li>Look for auto-login code in login_action.php</li>\n                <li>Verify no cookies are being set automatically</li>\n                <li>Check for any \"remember me\" functionality</li>\n            </ul>\n        </div>\n    </div>\n    \n    <script>\n        // Additional client-side cleanup\n        try {\n            // Clear localStorage\n            localStorage.clear();\n            // Clear sessionStorage\n            sessionStorage.clear();\n            console.log('Client-side storage cleared');\n        } catch(e) {\n            console.log('Could not clear client storage:', e);\n        }\n    </script>\n</body>\n</html>