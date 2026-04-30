<?php
require "includes/db.php";
$stmt = $pdo->query("SHOW COLUMNS FROM donations");
echo implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN));
