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
    $where_clauses = [];
    $params = [];

    if (!empty($_GET['ngo'])) {
        $where_clauses[] = "c.ngo_id = ?";
        $params[] = $_GET['ngo'];
    }

    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

    $stmt = $pdo->prepare("SELECT i.*, c.title as campaign_title,
                          COALESCE(nu.name, 'Share Hope') as ngo_name
                          FROM inkind_donations i
                          JOIN campaigns c ON i.campaign_id = c.id
                          LEFT JOIN ngos n ON c.ngo_id = n.id
                          LEFT JOIN users nu ON n.user_id = nu.id
                          ORDER BY i.created_at DESC");
    $stmt->execute([]);
    $export_data = $stmt->fetchAll();

    // Log export action
    log_admin_activity($pdo, $_SESSION['user_id'], 'Exported all pledge records', 'export', 'inkind_donations', null, 'All Pledges CSV', 'Exported ' . count($export_data) . ' records');

    // Generate CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="all_pledges_export_' . date('Y-m-d_His') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Item Category', 'Description', 'Quantity', 'Donor', 'NGO', 'Campaign', 'Status']);
    
    foreach ($export_data as $row) {
        fputcsv($output, [
            date('Y-m-d H:i:s', strtotime($row['created_at'])),
            $row['item_category'],
            $row['item_description'],
            $row['quantity'],
            $row['donor_name'] ?? 'Anonymous',
            $row['ngo_name'],
            $row['campaign_title'],
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

// Advanced Filtering for All Pledges
$where_clauses = [];
$params = [];

if (!empty($_GET['ngo'])) {
    $where_clauses[] = "c.ngo_id = ?";
    $params[] = $_GET['ngo'];
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get all pledges with pagination
$stmt = $pdo->prepare("SELECT i.*, c.title as campaign_title,
                      COALESCE(nu.name, 'Share Hope') as ngo_name
                      FROM inkind_donations i
                      JOIN campaigns c ON i.campaign_id = c.id
                      LEFT JOIN ngos n ON c.ngo_id = n.id
                      LEFT JOIN users nu ON n.user_id = nu.id
                      ORDER BY i.created_at DESC
                      LIMIT ? OFFSET ?");
$stmt->execute([$per_page, $offset]);
$pledges = $stmt->fetchAll();

// Get total count for pagination
$count_stmt = $pdo->query("SELECT COUNT(*) as total FROM inkind_donations");
$total_pledges = $count_stmt->fetch()['total'];
$total_pages = ceil($total_pledges / $per_page);

// Get list of NGOs for filter dropdown
$ngo_stmt = $pdo->query("SELECT n.id, u.name FROM ngos n JOIN users u ON n.user_id = u.id WHERE n.is_verified = 1 ORDER BY u.name");
$ngo_list = $ngo_stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 2.5rem 0; max-width: 1150px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">

        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
                <div>
                    <h1 style="font-size: 2rem; margin: 0;"><i class="fa-solid fa-hand-holding-heart" style="color: var(--accent); margin-right: 0.5rem;"></i>All In-Kind Pledges</h1>
                    <p style="color: var(--text-muted); margin: 0.5rem 0 0 0;">Complete pledge history and status management</p>
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
                        <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Total Pledges</div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--accent);"><?= $total_pledges ?></div>
                    </div>
                </div>
            </div>

            <!-- All Pledges Table -->
            <div
                style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 2px solid var(--accent); overflow: hidden;">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 1.2rem;">All In-Kind Pledges (<?= $total_pledges ?>)</h3>
                    <div style="color: var(--text-muted); font-size: 0.875rem;">
                        Showing <?= ($offset + 1) ?> to <?= min($offset + $per_page, $total_pledges) ?> of <?= $total_pledges ?> records
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr
                                style="border-bottom: 1px solid var(--accent); color: var(--text-muted); font-size: 0.875rem; background: rgba(245, 158, 11, 0.05);">
                                <th style="padding: 1rem 1.5rem;">Date</th>
                                <th style="padding: 1rem 1.5rem;">Item Category</th>
                                <th style="padding: 1rem 1.5rem;">Description</th>
                                <th style="padding: 1rem 1.5rem;">Quantity</th>
                                <th style="padding: 1rem 1.5rem;">Donor</th>
                                <th style="padding: 1rem 1.5rem;">NGO Target</th>
                                <th style="padding: 1rem 1.5rem;">Campaign</th>
                                <th style="padding: 1rem 1.5rem;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pledges)): ?>
                                <tr>
                                    <td colspan="8" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                                        <i class="fa-solid fa-hand-holding-heart" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; color: var(--accent);"></i>
                                        <h4>No pledges found</h4>
                                        <p>No in-kind pledges match your current filter criteria.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pledges as $pledge): ?>
                                    <tr style="border-bottom: 1px solid var(--border); font-size: 0.875rem;">
                                        <td style="padding: 1rem 1.5rem; white-space: nowrap;">
                                            <?= date('M j, Y H:i', strtotime($pledge['created_at'])) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; font-weight: 500;">
                                            <?= h($pledge['item_category']) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; color: var(--text-muted); max-width: 200px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"
                                            title="<?= h($pledge['item_description']) ?>">
                                            <?= h($pledge['item_description']) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; color: var(--text-muted);">
                                            <?= h($pledge['quantity']) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; font-weight: 500;">
                                            <?php if ($pledge['donor_name']): ?>
                                                <?= h($pledge['donor_name']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Anonymous</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; font-weight: 500; color: var(--primary);">
                                            <?= h($pledge['ngo_name']) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; color: var(--text-muted); max-width: 200px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"
                                            title="<?= h($pledge['campaign_title']) ?>">
                                            <?= h($pledge['campaign_title']) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <form method="POST" action="<?= BASE_URL ?>/actions/update_pledge_status.php" style="margin: 0;">
                                                <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                <input type="hidden" name="pledge_id" value="<?= $pledge['id'] ?>">
                                                <input type="hidden" name="redirect" value="all_pledges">
                                                <select name="status" onchange="this.form.submit()" style="padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid var(--border); font-size: 0.75rem; font-weight: 600; background: <?= 
                                                    $pledge['status'] === 'pledged' ? 'rgba(245, 158, 11, 0.1)' : 
                                                    ($pledge['status'] === 'received' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(79, 70, 229, 0.1)') 
                                                ?>; color: <?= 
                                                    $pledge['status'] === 'pledged' ? 'var(--accent)' : 
                                                    ($pledge['status'] === 'received' ? 'var(--secondary)' : 'var(--primary)') 
                                                ?>;">
                                                    <option value="pledged" <?= $pledge['status'] === 'pledged' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="received" <?= $pledge['status'] === 'received' ? 'selected' : '' ?>>Received</option>
                                                    <option value="distributed" <?= $pledge['status'] === 'distributed' ? 'selected' : '' ?>>Distributed</option>
                                                </select>
                                            </form>
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
