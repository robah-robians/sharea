<?php
session_start();
echo "<h2>Current Session State</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Session ID</h2>";
echo session_id();

echo "<h2>Session Cookie Params</h2>";
echo "<pre>";
print_r(session_get_cookie_params());
echo "</pre>";

echo "<h2>All Cookies</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";
?>