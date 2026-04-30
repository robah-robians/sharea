<?php
require "includes/db.php";
$stmt = $pdo->prepare("SELECT * FROM users WHERE email='ngo@sharehope.org'");
$stmt->execute();
$user = $stmt->fetch();
var_dump($user);
var_dump(password_verify("password", $user["password_hash"]));
