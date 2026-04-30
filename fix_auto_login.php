<?php
// Complete session reset to fix automatic login issue
session_start();

// Store current session data for debugging
$old_session = $_SESSION;

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
    setcookie(session_name(), '', time()-3600, '/share_hope/');
}

// Clear any other potential cookies
$cookies_to_clear = ['PHPSESSID', 'user_id', 'user_role', 'login_token'];
foreach ($cookies_to_clear as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time()-3600, '/');
        setcookie($cookie, '', time()-3600, '/share_hope/');
    }
}

// Start a fresh session
session_start();
session_regenerate_id(true);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Reset - Share Hope</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; background: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; padding: 1rem; border-radius: 4px; margin: 1rem 0; border: 1px solid #c3e6cb; }
        .info { background: #d1ecf1; color: #0c5460; padding: 1rem; border-radius: 4px; margin: 1rem 0; border: 1px solid #bee5eb; }
        .btn { background: #007bff; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; display: inline-block; margin: 0.5rem; }
        .btn:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Session Reset Complete</h1>
        
        <div class="success">
            <strong>✅ Success!</strong> All session data has been cleared and reset.
        </div>
        
        <div class="info">
            <strong>🔍 What was fixed:</strong>
            <ul>
                <li>Cleared all session variables</li>
                <li>Destroyed existing session</li>
                <li>Removed session cookies</li>
                <li>Generated new session ID</li>
                <li>Reset authentication state</li>
            </ul>
        </div>
        
        <?php if (!empty($old_session)): ?>
        <div class="info">
            <strong>📋 Previous session data (now cleared):</strong>
            <pre><?= htmlspecialchars(print_r($old_session, true)) ?></pre>
        </div>
        <?php endif; ?>
        
        <div class="info">
            <strong>🆕 Current session status:</strong>
            <ul>
                <li><strong>Session ID:</strong> <?= session_id() ?></li>
                <li><strong>Session Data:</strong> <?= empty($_SESSION) ? 'Empty (✅ Good!)' : 'Contains data' ?></li>
                <li><strong>User Logged In:</strong> <?= isset($_SESSION['user_id']) ? 'Yes' : 'No (✅ Good!)' ?></li>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="/share_hope/" class="btn">🏠 Go to Home Page</a>
            <a href="/share_hope/login.php" class="btn">🔐 Login Page</a>
            <a href="/share_hope/debug_session.php" class="btn">🔍 Debug Session</a>
        </div>
        
        <div class="info" style="margin-top: 2rem;">
            <strong>📝 Next Steps:</strong>
            <ol>
                <li>Go to the home page to verify you're logged out</li>
                <li>Try logging in normally with your credentials</li>
                <li>If the issue persists, check for any auto-login code in the application</li>
            </ol>
        </div>
    </div>
</body>
</html>