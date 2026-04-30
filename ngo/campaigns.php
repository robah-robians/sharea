<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header("Location: /share_hope/login.php");
    exit;
}

// REDIRECT back to dashboard. Dashboard now handles assigned initiatives.
$_SESSION['error'] = "Direct campaign management is restricted to the Administrative Hub. Your assigned operations are displayed below.";
header("Location: /share_hope/ngo/dashboard.php");
exit;
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>