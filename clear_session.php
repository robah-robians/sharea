<?php
// Clear all sessions - run this once to fix the auto-login issue
session_start();
session_unset();
session_destroy();

// Clear any cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

echo "All sessions cleared. You should now be logged out.";
echo "<br><a href='/share_hope/'>Go to Home Page</a>";
?>