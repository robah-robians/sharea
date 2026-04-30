<?php
/**
 * Quick Login Test
 * Test login functionality with proper CSRF handling
 */

session_start();
require_once __DIR__ . '/includes/db.php';

echo "<h1>🔐 Quick Login Test</h1>";

// Create test admin user if doesn't exist
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@test.com'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if (!$admin) {
        $password_hash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (role, name, email, password_hash, status, email_verified) VALUES ('admin', 'Test Admin', 'admin@test.com', ?, 'active', 1)");
        $stmt->execute([$password_hash]);
        echo "✅ Created test admin user<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Login Form:</h2>";
echo "<form method='POST' action='/share_hope/actions/login_action.php'>";
echo "<input type='hidden' name='csrf_token' value='" . h(generate_csrf_token()) . "'>";
echo "<p><label>Email:</label><br><input type='email' name='email' value='admin@test.com' style='width: 300px; padding: 8px;'></p>";
echo "<p><label>Password:</label><br><input type='password' name='password' value='admin123' style='width: 300px; padding: 8px;'></p>";
echo "<button type='submit' style='padding: 10px 20px; background: #4F46E5; color: white; border: none; border-radius: 4px;'>Test Login</button>";
echo "</form>";

echo "<h2>Current Session:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>CSRF Token Status:</h2>";
if (isset($_SESSION['csrf_token'])) {
    echo "✅ CSRF token exists: " . substr($_SESSION['csrf_token'], 0, 20) . "...<br>";
} else {
    echo "❌ No CSRF token found<br>";
}

echo "<hr>";
echo "<p><strong>Test Credentials:</strong></p>";
echo "<ul>";
echo "<li>Email: admin@test.com</li>";
echo "<li>Password: admin123</li>";
echo "</ul>";

echo "<p><a href='/share_hope/login.php'>Go to Regular Login Page</a></p>";
?>