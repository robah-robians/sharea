<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Ensure NGO is verified before they can export data
$stmt = $pdo->prepare("SELECT id, is_verified FROM ngos WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$ngo = $stmt->fetch();

if (!$ngo || !$ngo['is_verified']) {
    die("Unauthorized or NGO account not yet verified.");
}

$ngo_id = $ngo['id'];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=my_campaign_donors_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Campaign', 'Donor Name', 'Amount (KSh)', 'Status']);

$stmt = $pdo->prepare("SELECT d.created_at, c.title as campaign_title, u.name as donor_name, d.amount, d.status, d.is_anonymous 
                     FROM donations d 
                     JOIN campaigns c ON d.campaign_id = c.id 
                     LEFT JOIN users u ON d.donor_id = u.id 
                     WHERE c.ngo_id = ? AND d.status = 'completed'
                     ORDER BY d.created_at DESC");
$stmt->execute([$ngo_id]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $donor = $row['is_anonymous'] ? 'Anonymous' : ($row['donor_name'] ?? 'Guest');
    fputcsv($output, [
        $row['created_at'],
        $row['campaign_title'],
        $donor,
        $row['amount'],
        $row['status']
    ]);
}
fclose($output);
exit;
