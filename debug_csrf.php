<?php
/**
 * CSRF Token Debug Script
 * This script helps debug CSRF token issues
 */

session_start();
require_once __DIR__ . '/includes/db.php';

echo "<h1>🔐 CSRF Token Debug</h1>";

echo "<h2>Current Session State:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>CSRF Token Test:</h2>";

// Test token generation
$token1 = generate_csrf_token();
echo "Generated Token 1: " . $token1 . "<br>";

$token2 = generate_csrf_token();
echo "Generated Token 2: " . $token2 . "<br>";

if ($token1 === $token2) {
    echo "✅ <strong style='color: green;'>GOOD:</strong> Tokens are consistent<br>";
} else {
    echo "❌ <strong style='color: red;'>PROBLEM:</strong> Tokens are different<br>";
}

echo "<h2>Session CSRF Token:</h2>";
if (isset($_SESSION['csrf_token'])) {
    echo "Session Token: " . $_SESSION['csrf_token'] . "<br>";
    echo "✅ <strong style='color: green;'>CSRF token exists in session</strong><br>";
} else {
    echo "❌ <strong style='color: red;'>No CSRF token in session</strong><br>";
}

echo "<h2>Test Form:</h2>";
echo "<form method='POST' action='#'>";
echo "<input type='hidden' name='csrf_token' value='" . h(generate_csrf_token()) . "'>";
echo "<input type='text' name='test_field' placeholder='Test input'>";
echo "<button type='submit'>Test Submit</button>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Form Submission Test:</h2>";
    $submitted_token = $_POST['csrf_token'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';
    
    echo "Submitted Token: " . $submitted_token . "<br>";
    echo "Session Token: " . $session_token . "<br>";
    
    if (hash_equals($session_token, $submitted_token)) {
        echo "✅ <strong style='color: green;'>CSRF validation PASSED</strong><br>";
    } else {
        echo "❌ <strong style='color: red;'>CSRF validation FAILED</strong><br>";
    }
}

echo "<hr>";
echo "<h2>Quick Links:</h2>";
echo "<ul>";
echo "<li><a href='/share_hope/login.php'>Login Page</a></li>";
echo "<li><a href='/share_hope/emergency_session_fix.php'>Clear Sessions</a></li>";
echo "</ul>";
?>