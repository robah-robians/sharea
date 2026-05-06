<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/");
    exit;
}

$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '/'));
exit;
