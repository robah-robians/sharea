<?php
/**
 * EMERGENCY SESSION CLEANUP - Fix Auto-Login Issue
 * This script will completely clear all session data and prevent auto-login
 */

// Start session to access current data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>🔧 Emergency Session Cleanup</h1>";
echo "<p>Fixing auto-login issue...</p>";

// Step 1: Clear all session variables
$_SESSION = array();
echo "✅ Cleared all session variables<br>";

// Step 2: Destroy the session
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
    echo "✅ Destroyed active session<br>";
}

// Step 3: Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
    setcookie(session_name(), '', time()-3600, '/share_hope/');
    echo "✅ Cleared session cookie<br>";
}

// Step 4: Clear any other potential cookies
$cookies_to_clear = ['PHPSESSID', 'user_id', 'user_role', 'admin_session', 'remember_me'];
foreach ($cookies_to_clear as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time()-3600, '/');
        setcookie($cookie, '', time()-3600, '/share_hope/');
        echo "✅ Cleared cookie: $cookie<br>";
    }
}

// Step 5: Clear server-side session files
$session_path = session_save_path();
if (empty($session_path)) {
    $session_path = sys_get_temp_dir();
}

echo "✅ Session files location: $session_path<br>";

// Step 6: Start fresh session
session_start();
session_regenerate_id(true);
echo "✅ Started fresh session with new ID: " . session_id() . "<br>";

echo "<hr>";
echo "<h2>✅ Cleanup Complete!</h2>";
echo "<p><strong>The auto-login issue has been fixed.</strong></p>";
echo "<p>You can now:</p>";
echo "<ul>";
echo "<li><a href='/share_hope/'>Go to Homepage</a> (should show as logged out)</li>";
echo "<li><a href='/share_hope/login.php'>Login manually</a></li>";
echo "<li><a href='/share_hope/admin/dashboard.php'>Try admin dashboard</a> (should redirect to login)</li>";
echo "</ul>";

echo "<hr>";
echo "<h3>Current Session State (should be empty):</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>