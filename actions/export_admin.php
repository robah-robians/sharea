<?php
require_once __DIR__ . '/../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /share_hope/login.php");
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=share_hope_all_transactions_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['Transaction ID', 'Date', 'Donor Name', 'NGO Name', 'Campaign', 'Amount (KSh)', 'Method']);

$stmt = $pdo->query("SELECT d.transaction_id, d.created_at, u.name as donor_name, n.name as ngo_name, c.title as campaign_title, d.amount, d.payment_method 
                     FROM donations d 
                     LEFT JOIN users u ON d.donor_id = u.id 
                     JOIN campaigns c ON d.campaign_id = c.id 
                     JOIN ngos ngo ON c.ngo_id = ngo.id
                     JOIN users n ON ngo.user_id = n.id
                     WHERE d.status = 'completed'
                     ORDER BY d.created_at DESC");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $donor = $row['donor_name'] ?? 'Anonymous';
    fputcsv($output, [
        $row['transaction_id'],
        $row['created_at'],
        $donor,
        $row['ngo_name'],
        $row['campaign_title'],
        $row['amount'],
        $row['payment_method']
    ]);
}
fclose($output);
exit;
