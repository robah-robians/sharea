<?php
/**
 * Test Auto-Login Fix
 * This script tests if the auto-login issue has been resolved
 */

session_start();

echo "<h1>🧪 Auto-Login Fix Test</h1>";

echo "<h2>Current Session Status:</h2>";
if (empty($_SESSION)) {
    echo "✅ <strong style='color: green;'>GOOD:</strong> No session data found (user is logged out)<br>";
} else {
    echo "❌ <strong style='color: red;'>PROBLEM:</strong> Session data still exists:<br>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

echo "<h2>Authentication Check:</h2>";
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
if ($is_logged_in) {
    echo "❌ <strong style='color: red;'>PROBLEM:</strong> User appears to be logged in automatically<br>";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'N/A') . "<br>";
    echo "User Role: " . ($_SESSION['user_role'] ?? 'N/A') . "<br>";
} else {
    echo "✅ <strong style='color: green;'>GOOD:</strong> User is properly logged out<br>";
}

echo "<h2>Header Navigation Test:</h2>";
if ($is_logged_in) {
    echo "❌ Header will show: Dashboard link and Logout button<br>";
} else {
    echo "✅ Header will show: Log In and Sign Up buttons<br>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
if ($is_logged_in) {
    echo "<p><strong>❌ Auto-login issue still exists!</strong></p>";
    echo "<p>Run the emergency cleanup script: <a href='/share_hope/emergency_session_fix.php'>emergency_session_fix.php</a></p>";
} else {
    echo "<p><strong>✅ Auto-login issue is FIXED!</strong></p>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li><a href='/share_hope/'>Visit Homepage</a> (should show as logged out)</li>";
    echo "<li><a href='/share_hope/login.php'>Login manually</a></li>";
    echo "</ul>";
}
?>