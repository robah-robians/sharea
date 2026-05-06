<?php
/**
 * Login Redirect Test
 * This script tests if login redirects are working properly after authentication
 */

session_start();

echo "<h1>🔐 Login Redirect Test</h1>";

echo "<h2>Current Session Status:</h2>";
if (isset($_SESSION['user_id'])) {
    echo "✅ <strong style='color: green;'>User is logged in</strong><br>";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'N/A') . "<br>";
    echo "User Role: " . ($_SESSION['user_role'] ?? 'N/A') . "<br>";
    echo "User Name: " . ($_SESSION['user_name'] ?? 'N/A') . "<br>";
    echo "Login Time: " . (isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : 'N/A') . "<br>";
    echo "Login Validated: " . (isset($_SESSION['login_validated']) ? 'Yes' : 'No') . "<br>";
    
    echo "<h2>Expected Dashboard Link:</h2>";
    $role = $_SESSION['user_role'] ?? '';
    switch ($role) {
        case 'admin':
            echo "👑 <a href='/share_hope/admin/dashboard.php'>Admin Dashboard</a><br>";
            break;
        case 'ngo':
            echo "🏢 <a href='/share_hope/ngo/dashboard.php'>NGO Dashboard</a><br>";
            break;
        case 'donor':
            echo "💝 <a href='/share_hope/donor/dashboard.php'>Donor Dashboard</a><br>";
            break;
        default:
            echo "❓ Unknown role: $role<br>";
    }
} else {
    echo "❌ <strong style='color: red;'>User is not logged in</strong><br>";
    echo "<p>Try logging in: <a href='/share_hope/login.php'>Login Page</a></p>";
}

echo "<hr>";
echo "<h2>Test Actions:</h2>";
echo "<ul>";
echo "<li><a href='/share_hope/'>Go to Homepage</a></li>";
echo "<li><a href='/share_hope/login.php'>Go to Login Page</a></li>";
echo "<li><a href='/share_hope/actions/logout_action.php'>Logout</a></li>";
echo "</ul>";

echo "<hr>";
echo "<h2>Session Validation Check:</h2>";
if (isset($_SESSION['user_id']) && isset($_SESSION['login_validated']) && isset($_SESSION['login_time'])) {
    $session_age = time() - $_SESSION['login_time'];
    $max_age = 14400; // 4 hours
    
    if ($session_age > $max_age) {
        echo "⚠️ <strong style='color: orange;'>Session expired</strong> (Age: " . round($session_age/60) . " minutes)<br>";
    } else {
        echo "✅ <strong style='color: green;'>Session valid</strong> (Age: " . round($session_age/60) . " minutes)<br>";
    }
} else {
    echo "❌ <strong style='color: red;'>Session validation failed</strong> - Missing required session data<br>";
}
?>