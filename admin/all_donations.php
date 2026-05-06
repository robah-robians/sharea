<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'samesite' => 'Strict'
    ]);
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';
require_once __DIR__ . '/../includes/security.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Handle CSV Export
if (!empty($_GET['export']) && $_GET['export'] === 'csv') {
    $where_clauses = ["d.status = 'completed'"];
    $params = [];

    if (!empty($_GET['ngo'])) {
        $where_clauses[] = "ngo.id = ?";
        $params[] = $_GET['ngo'];
    }

    $where_sql = implode(' AND ', $where_clauses);

    $stmt = $pdo->prepare("SELECT d.*, c.title as campaign_title, u.name as donor_name, 'Share Hope Admin' as ngo_name 
                         FROM donations d 
                         JOIN campaigns c ON d.campaign_id = c.id 
                         LEFT JOIN users u ON d.donor_id = u.id
                         WHERE $where_sql
                         ORDER BY d.created_at DESC");
    $stmt->execute($params);
    $export_data = $stmt->fetchAll();

    // Log export action
    log_admin_activity($pdo, $_SESSION['user_id'], 'Exported all donation records', 'export', 'donations', null, 'All Donations CSV', 'Exported ' . count($export_data) . ' records');

    // Generate CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="all_donations_export_' . date('Y-m-d_His') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Transaction ID', 'Donor', 'NGO', 'Campaign', 'Amount', 'Payment Method', 'Status']);
    
    foreach ($export_data as $row) {
        fputcsv($output, [
            date('Y-m-d H:i:s', strtotime($row['created_at'])),
            $row['transaction_id'],
            $row['is_anonymous'] ? 'Anonymous' : ($row['donor_name'] ?? 'Guest'),
            $row['ngo_name'],
            $row['campaign_title'],
            $row['amount'],
            $row['payment_method'],
            $row['status']
        ]);
    }
    fclose($output);
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Advanced Filtering for All Donations
$where_clauses = ["d.status = 'completed'"];
$params = [];

if (!empty($_GET['ngo'])) {
    $where_clauses[] = "ngo.id = ?";
    $params[] = $_GET['ngo'];
}

$where_sql = implode(' AND ', $where_clauses);

// Get all donations with pagination
$stmt = $pdo->prepare("SELECT d.*, c.title as campaign_title, u.name as donor_name, 'Share Hope Admin' as ngo_name 
                     FROM donations d 
                     JOIN campaigns c ON d.campaign_id = c.id 
                     LEFT JOIN users u ON d.donor_id = u.id
                     WHERE $where_sql
                     ORDER BY d.created_at DESC
                     LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$per_page, $offset]));
$transactions = $stmt->fetchAll();

// Get total count for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM donations d 
                            JOIN campaigns c ON d.campaign_id = c.id
                            WHERE $where_sql");
$count_stmt->execute($params);
$total_donations = $count_stmt->fetch()['total'];
$total_pages = ceil($total_donations / $per_page);

// Get list of NGOs for filter dropdown
$ngo_stmt = $pdo->query("SELECT n.id, u.name FROM ngos n JOIN users u ON n.user_id = u.id WHERE n.is_verified = 1 ORDER BY u.name");
$ngo_list = $ngo_stmt->fetchAll();

// Get total volume for view
$total_volume = array_sum(array_column($transactions, 'amount'));

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 2.5rem 0; max-width: 1150px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">

        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
                <div>
                    <h1 style="font-size: 2rem; margin: 0;">All Donations</h1>
                    <p style="color: var(--text-muted); margin: 0.5rem 0 0 0;">Complete donation history and records</p>
                </div>
                <a href="<?= BASE_URL ?>/admin/donations.php<?= !empty($_GET['ngo']) ? '?ngo=' . $_GET['ngo'] : '' ?>" class="btn btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <div
                style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                    <div style="font-weight: 500;">Filter:</div>
                    <select name="ngo" class="form-control" style="width: auto; padding: 0.5rem 1rem;">
                        <option value="">All NGOs</option>
                        <?php foreach ($ngo_list as $n): ?>
                            <option value="<?= $n['id'] ?>" <?= (isset($_GET['ngo']) && $_GET['ngo'] == $n['id']) ? 'selected' : '' ?>>
                                <?= h($n['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Apply</button>
                </form>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline" style="padding: 0.5rem 1rem; white-space: nowrap;">
                        <i class="fa-solid fa-download"></i> Export CSV
                    </a>
                    <div style="text-align: right;">
                        <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Total Volume</div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--secondary);">$ <?= number_format($total_volume, 2) ?></div>
                    </div>
                </div>
            </div>

            <!-- All Donations Table -->
            <div
                style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 1.2rem;">All Donations (<?= $total_donations ?>)</h3>
                    <div style="color: var(--text-muted); font-size: 0.875rem;">
                        Showing <?= ($offset + 1) ?> to <?= min($offset + $per_page, $total_donations) ?> of <?= $total_donations ?> records
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr
                                style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.875rem; background: var(--background);">
                                <th style="padding: 1rem 1.5rem;">Date</th>
                                <th style="padding: 1rem 1.5rem;">TXN ID</th>
                                <th style="padding: 1rem 1.5rem;">Donor</th>
                                <th style="padding: 1rem 1.5rem;">NGO Target</th>
                                <th style="padding: 1rem 1.5rem;">Campaign</th>
                                <th style="padding: 1rem 1.5rem;">Amount</th>
                                <th style="padding: 1rem 1.5rem;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                                        <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                                        <h4>No donations found</h4>
                                        <p>No donations match your current filter criteria.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $txn): ?>
                                    <tr style="border-bottom: 1px solid var(--border); font-size: 0.875rem;">
                                        <td style="padding: 1rem 1.5rem; white-space: nowrap;">
                                            <?= date('M j, Y H:i', strtotime($txn['created_at'])) ?>
                                        </td>
                                        <td
                                            style="padding: 1rem 1.5rem; color: var(--text-muted); font-family: monospace; font-size: 0.75rem;">
                                            <?= h($txn['transaction_id']) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; font-weight: 500;">
                                            <?= $txn['is_anonymous'] ? '<span class="text-muted">Anonymous</span>' : h($txn['donor_name'] ?? 'Guest') ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; font-weight: 500; color: var(--primary);">
                                            <?= h($txn['ngo_name']) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; color: var(--text-muted); max-width: 250px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"
                                            title="<?= h($txn['campaign_title']) ?>">
                                            <?= h($txn['campaign_title']) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; font-weight: 700; color: var(--secondary);">$
                                            <?= number_format($txn['amount'], 2) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <a href="<?= BASE_URL ?>/receipt.php?id=<?= $txn['id'] ?>" target="_blank"
                                                class="btn btn-outline" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;"><i
                                                    class="fa-solid fa-print"></i> Receipt</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div style="padding: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: center; align-items: center; gap: 0.5rem;">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="btn btn-outline" style="padding: 0.5rem 1rem;">
                                <i class="fa-solid fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                               class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>" 
                               style="padding: 0.5rem 0.75rem;">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="btn btn-outline" style="padding: 0.5rem 1rem;">
                                Next <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
