<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';
require_once __DIR__ . '/../includes/security.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/login.php"); exit;
}

// CSV Export
if (!empty($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = $pdo->query("SELECT d.*, c.title as campaign_title, u.name as donor_name,
                         COALESCE(nu.name, 'Share Hope') as ngo_name
                         FROM donations d
                         JOIN campaigns c ON d.campaign_id = c.id
                         LEFT JOIN ngos n ON c.ngo_id = n.id
                         LEFT JOIN users nu ON n.user_id = nu.id
                         LEFT JOIN users u ON d.donor_id = u.id
                         WHERE d.status = 'completed'
                         ORDER BY d.created_at DESC");
    $export_data = $stmt->fetchAll();
    log_admin_activity($pdo, $_SESSION['user_id'], 'Exported donation records', 'export', 'donations', null, 'Donations Ledger CSV', 'Exported ' . count($export_data) . ' records');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="donations_export_' . date('Y-m-d_His') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Transaction ID', 'Donor', 'NGO', 'Campaign', 'Amount', 'Payment Method', 'Status']);
    foreach ($export_data as $row) {
        fputcsv($output, [
            date('Y-m-d H:i:s', strtotime($row['created_at'])),
            $row['transaction_id'],
            $row['is_anonymous'] ? 'Anonymous' : ($row['donor_name'] ?? 'Guest'),
            $row['ngo_name'], $row['campaign_title'],
            $row['amount'], $row['payment_method'], $row['status']
        ]);
    }
    fclose($output); exit;
}

// Latest 5 donations
$stmt = $pdo->query("SELECT d.*, c.title as campaign_title, u.name as donor_name,
                     COALESCE(nu.name, 'Share Hope') as ngo_name
                     FROM donations d
                     JOIN campaigns c ON d.campaign_id = c.id
                     LEFT JOIN ngos n ON c.ngo_id = n.id
                     LEFT JOIN users nu ON n.user_id = nu.id
                     LEFT JOIN users u ON d.donor_id = u.id
                     WHERE d.status = 'completed'
                     ORDER BY d.created_at DESC LIMIT 5");
$transactions = $stmt->fetchAll();
$total_volume = array_sum(array_column($transactions, 'amount'));

$total_donations = $pdo->query("SELECT COUNT(*) FROM donations WHERE status = 'completed'")->fetchColumn();

// Latest 5 in-kind pledges
$inkind_stmt = $pdo->query("SELECT i.*, c.title as campaign_title,
                            COALESCE(nu.name, 'Share Hope') as ngo_name
                            FROM inkind_donations i
                            JOIN campaigns c ON i.campaign_id = c.id
                            LEFT JOIN ngos n ON c.ngo_id = n.id
                            LEFT JOIN users nu ON n.user_id = nu.id
                            ORDER BY i.created_at DESC LIMIT 5");
$inkind_pledges = $inkind_stmt->fetchAll();
$total_pledges = $pdo->query("SELECT COUNT(*) FROM inkind_donations")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 2.5rem 0; max-width: 1150px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
                <h1 style="font-size: 2rem; margin: 0;">Donations Ledger</h1>
                <a href="?export=csv" class="btn btn-outline" style="padding: 0.5rem 1rem;">
                    <i class="fa-solid fa-download"></i> Export CSV
                </a>
            </div>

            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; margin-bottom: 2rem;">
                <div style="padding: 1rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 1.1rem;">Latest Donations</h3>
                    <a href="<?= BASE_URL ?>/admin/all_donations.php" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">View All (<?= $total_donations ?>)</a>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.8rem; background: var(--background);">
                                <th style="padding: 0.75rem 1rem;">Date</th>
                                <th style="padding: 0.75rem 1rem;">TXN ID</th>
                                <th style="padding: 0.75rem 1rem;">Donor</th>
                                <th style="padding: 0.75rem 1rem;">NGO</th>
                                <th style="padding: 0.75rem 1rem;">Campaign</th>
                                <th style="padding: 0.75rem 1rem;">Amount</th>
                                <th style="padding: 0.75rem 1rem;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr><td colspan="7" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">No recent transactions.</td></tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $txn): ?>
                                    <tr style="border-bottom: 1px solid var(--border); font-size: 0.8rem;" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                        <td style="padding: 0.75rem 1rem; white-space: nowrap;"><?= date('M j, Y H:i', strtotime($txn['created_at'])) ?></td>
                                        <td style="padding: 0.75rem 1rem; color: var(--text-muted); font-family: monospace; font-size: 0.7rem;"><?= h($txn['transaction_id']) ?></td>
                                        <td style="padding: 0.75rem 1rem; font-weight: 500;"><?= $txn['is_anonymous'] ? '<span style="color:var(--text-muted)">Anonymous</span>' : h($txn['donor_name'] ?? 'Guest') ?></td>
                                        <td style="padding: 0.75rem 1rem; color: var(--primary); font-weight: 500;"><?= h($txn['ngo_name']) ?></td>
                                        <td style="padding: 0.75rem 1rem; color: var(--text-muted); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= h($txn['campaign_title']) ?></td>
                                        <td style="padding: 0.75rem 1rem; font-weight: 700; color: var(--secondary);">KSh <?= number_format($txn['amount'], 2) ?></td>
                                        <td style="padding: 0.75rem 1rem;"><a href="<?= BASE_URL ?>/receipt.php?id=<?= $txn['id'] ?>" target="_blank" class="btn btn-outline" style="padding: 0.2rem 0.6rem; font-size: 0.7rem;"><i class="fa-solid fa-print"></i> Receipt</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div style="padding: 1rem; border-top: 1px solid var(--border); text-align: center; background: var(--background);">
                    <a href="<?= BASE_URL ?>/admin/all_donations.php" class="btn btn-primary" style="padding: 0.6rem 1.5rem; font-size: 0.875rem;">
                        <i class="fa-solid fa-list"></i> View All Donations (<?= $total_donations ?>)
                    </a>
                </div>
            </div>

            <!-- In-Kind Pledges -->
            <div style="margin-top: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2 style="font-size: 1.5rem; margin: 0;"><i class="fa-solid fa-hand-holding-heart" style="color: var(--accent); margin-right: 0.5rem;"></i>Latest In-Kind Pledges</h2>
                    <a href="<?= BASE_URL ?>/admin/all_pledges.php" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem;">View All (<?= $total_pledges ?>)</a>
                </div>
                <div style="background: var(--surface); border-radius: var(--radius-lg); border: 2px solid var(--accent); overflow: hidden;">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--accent); color: var(--text-muted); font-size: 0.875rem; background: rgba(245,158,11,0.05);">
                                    <th style="padding: 1rem 1.5rem;">Date</th>
                                    <th style="padding: 1rem 1.5rem;">Item</th>
                                    <th style="padding: 1rem 1.5rem;">Qty</th>
                                    <th style="padding: 1rem 1.5rem;">Donor</th>
                                    <th style="padding: 1rem 1.5rem;">NGO</th>
                                    <th style="padding: 1rem 1.5rem;">Campaign</th>
                                    <th style="padding: 1rem 1.5rem;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($inkind_pledges)): ?>
                                    <tr><td colspan="7" style="padding: 2rem; text-align: center; color: var(--text-muted);">No recent pledges.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($inkind_pledges as $pledge): ?>
                                        <tr style="border-bottom: 1px solid var(--border); font-size: 0.875rem;">
                                            <td style="padding: 1rem 1.5rem; white-space: nowrap;"><?= date('M j, Y H:i', strtotime($pledge['created_at'])) ?></td>
                                            <td style="padding: 1rem 1.5rem; font-weight: 500;"><?= h($pledge['item_category']) ?></td>
                                            <td style="padding: 1rem 1.5rem; color: var(--text-muted);"><?= h($pledge['quantity']) ?></td>
                                            <td style="padding: 1rem 1.5rem;"><?= h($pledge['donor_name'] ?? 'Anonymous') ?></td>
                                            <td style="padding: 1rem 1.5rem; color: var(--primary); font-weight: 500;"><?= h($pledge['ngo_name']) ?></td>
                                            <td style="padding: 1rem 1.5rem; color: var(--text-muted); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= h($pledge['campaign_title']) ?></td>
                                            <td style="padding: 1rem 1.5rem;">
                                                <span style="padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;
                                                    background: <?= $pledge['status'] === 'pledged' ? 'rgba(245,158,11,0.1)' : ($pledge['status'] === 'received' ? 'rgba(16,185,129,0.1)' : 'rgba(79,70,229,0.1)') ?>;
                                                    color: <?= $pledge['status'] === 'pledged' ? 'var(--accent)' : ($pledge['status'] === 'received' ? 'var(--secondary)' : 'var(--primary)') ?>;">
                                                    <?= ucfirst($pledge['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="padding: 1rem; border-top: 1px solid var(--border); text-align: center; background: var(--background);">
                        <a href="<?= BASE_URL ?>/admin/all_pledges.php" class="btn btn-primary" style="padding: 0.6rem 1.5rem; font-size: 0.875rem;">
                            <i class="fa-solid fa-hand-holding-heart"></i> View All Pledges (<?= $total_pledges ?>)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
