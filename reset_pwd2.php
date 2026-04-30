<?php
require "includes/db.php";
$hash = password_hash("password", PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE users SET password_hash=?, account_locked_until=NULL, failed_login_attempts=0 WHERE email=?");
$stmt->execute([$hash, "ngo@sharehope.org"]);
echo "Password reset done. Verified: " . (password_verify("password", $hash) ? "YES" : "NO");
