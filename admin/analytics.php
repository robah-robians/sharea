<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: /share_hope/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/activity_logger.php';

// Handle exports
if (!empty($_GET['export'])) {
    $export_type = $_GET['export'];
    try {
        switch($export_type) {
            case 'donations':
                $stmt = $pdo->query("SELECT d.id, d.transaction_id, d.amount, d.payment_method, d.status, d.is_anonymous, d.created_at, u.name as donor_name, u.email as donor_email, c.title as campaign_title FROM donations d LEFT JOIN users u ON d.donor_id = u.id JOIN campaigns c ON d.campaign_id = c.id WHERE d.status = 'completed' ORDER BY d.created_at DESC");
                $data = $stmt->fetchAll();
                $filename = 'donations_export_' . date('Y-m-d_His') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Date', 'Transaction ID', 'Donor', 'Amount', 'Payment Method', 'Campaign', 'Status']);
                foreach ($data as $row) {
                    fputcsv($output, [date('Y-m-d H:i', strtotime($row['created_at'])), $row['transaction_id'] ?? 'N/A', $row['is_anonymous'] ? 'Anonymous' : ($row['donor_name'] ?? 'Guest'), number_format($row['amount'], 2), ucfirst($row['payment_method']), $row['campaign_title'], ucfirst($row['status'])]);
                }
                fclose($output);
                log_admin_activity($pdo, $_SESSION['user_id'], 'export_donations', 'export', 'donations', null, 'donations', 'Exported ' . count($data) . ' records');
                exit;
            case 'campaigns':
                $stmt = $pdo->query("SELECT c.id, c.title, c.status, c.goal_amount, c.current_amount, c.deadline, c.created_at, COUNT(DISTINCT d.donor_id) as unique_donors FROM campaigns c LEFT JOIN donations d ON c.id = d.campaign_id AND d.status = 'completed' GROUP BY c.id ORDER BY c.created_at DESC");
                $data = $stmt->fetchAll();
                $filename = 'campaigns_export_' . date('Y-m-d_His') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Campaign', 'Status', 'Goal (KES)', 'Raised (KES)', 'Progress %', 'Donors', 'Deadline', 'Created']);
                foreach ($data as $row) {
                    $progress = $row['goal_amount'] > 0 ? ($row['current_amount'] / $row['goal_amount']) * 100 : 0;
                    fputcsv($output, [$row['title'], ucfirst($row['status']), number_format($row['goal_amount'], 2), number_format($row['current_amount'], 2), number_format($progress, 1), $row['unique_donors'], date('Y-m-d', strtotime($row['deadline'])), date('Y-m-d', strtotime($row['created_at']))]);
                }
                fclose($output);
                log_admin_activity($pdo, $_SESSION['user_id'], 'export_campaigns', 'export', 'campaigns', null, 'campaigns', 'Exported ' . count($data) . ' records');
                exit;
            case 'users':
                $stmt = $pdo->query("SELECT id, name, email, role, status, created_at FROM users ORDER BY created_at DESC");
                $data = $stmt->fetchAll();
                $filename = 'users_export_' . date('Y-m-d_His') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Name', 'Email', 'Role', 'Status', 'Joined']);
                foreach ($data as $row) {
                    fputcsv($output, [$row['name'], $row['email'], ucfirst($row['role']), ucfirst($row['status']), date('Y-m-d', strtotime($row['created_at']))]);
                }
                fclose($output);
                log_admin_activity($pdo, $_SESSION['user_id'], 'export_users', 'export', 'users', null, 'users', 'Exported ' . count($data) . ' records');
                exit;
            case 'ngos':
                $stmt = $pdo->query("SELECT n.id, n.name, n.is_verified, n.created_at, u.email, u.name as contact_name FROM ngos n JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC");
                $data = $stmt->fetchAll();
                $filename = 'ngos_export_' . date('Y-m-d_His') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                $output = fopen('php://output', 'w');
                fputcsv($output, ['NGO Name', 'Contact', 'Email', 'Status', 'Registered']);
                foreach ($data as $row) {
                    fputcsv($output, [$row['name'], $row['contact_name'], $row['email'], $row['is_verified'] ? 'Verified' : 'Pending', date('Y-m-d', strtotime($row['created_at']))]);
                }
                fclose($output);
                log_admin_activity($pdo, $_SESSION['user_id'], 'export_ngos', 'export', 'ngos', null, 'ngos', 'Exported ' . count($data) . ' records');
                exit;
            case 'transactions':
                $stmt = $pdo->query("SELECT d.id, d.transaction_id, d.amount, d.payment_method, d.status, d.created_at, u.name as donor_name, c.title as campaign_title FROM donations d LEFT JOIN users u ON d.donor_id = u.id JOIN campaigns c ON d.campaign_id = c.id ORDER BY d.created_at DESC");
                $data = $stmt->fetchAll();
                $filename = 'transactions_export_' . date('Y-m-d_His') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Date', 'Transaction ID', 'Amount', 'Payment Method', 'Status', 'Donor', 'Campaign']);
                foreach ($data as $row) {
                    fputcsv($output, [date('Y-m-d H:i', strtotime($row['created_at'])), $row['transaction_id'] ?? 'N/A', number_format($row['amount'], 2), ucfirst($row['payment_method']), ucfirst($row['status']), $row['donor_name'] ?? 'Anonymous', $row['campaign_title']]);
                }
                fclose($output);
                log_admin_activity($pdo, $_SESSION['user_id'], 'export_transactions', 'export', 'donations', null, 'transactions', 'Exported ' . count($data) . ' records');
                exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Export failed: " . $e->getMessage();
    }
}

// Get stats
$stmt = $pdo->query("SELECT SUM(amount) as total, COUNT(*) as count, AVG(amount) as avg FROM donations WHERE status = 'completed'");
$donation_stats = $stmt->fetch();

$stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'active' THEN 1 END) as active, SUM(current_amount) as total_raised FROM campaigns");
$campaign_stats = $stmt->fetch();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'donor'");
$donor_count = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM ngos WHERE is_verified = 1");
$verified_ngos = $stmt->fetch()['total'];

// Top campaigns
$stmt = $pdo->query("SELECT c.title, SUM(d.amount) as total FROM donations d JOIN campaigns c ON d.campaign_id = c.id WHERE d.status = 'completed' GROUP BY c.id ORDER BY total DESC LIMIT 5");
$top_campaigns = $stmt->fetchAll();

// Recent campaigns
$stmt = $pdo->query("SELECT c.id, c.title, c.current_amount, c.goal_amount FROM campaigns c WHERE c.status = 'active' ORDER BY c.created_at DESC LIMIT 5");
$recent_campaigns = $stmt->fetchAll();

// Payment method breakdown
$stmt = $pdo->query("SELECT payment_method, COUNT(*) as count, SUM(amount) as total FROM donations WHERE status = 'completed' GROUP BY payment_method");
$payment_methods = $stmt->fetchAll();

// Campaign status breakdown
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM campaigns GROUP BY status");
$campaign_status = $stmt->fetchAll();

// Donation trends (last 30 days)
$stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total FROM donations WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY date ASC");
$donation_trends = $stmt->fetchAll();

// Top NGOs by donations (via ngo user accounts directly linked to donations is not available; use top campaigns instead)
$top_ngos = [];
$ngo_labels = [];
$ngo_amounts = [];

// Prepare chart data
$trend_dates = array_map(fn($t) => date('M d', strtotime($t['date'])), $donation_trends);
$trend_amounts = array_map(fn($t) => $t['total'], $donation_trends);

$payment_labels = array_map(fn($p) => ucfirst($p['payment_method']), $payment_methods);
$payment_counts = array_map(fn($p) => $p['count'], $payment_methods);

$status_labels = array_map(fn($s) => ucfirst($s['status']), $campaign_status);
$status_counts = array_map(fn($s) => $s['count'], $campaign_status);

// ngo_labels and ngo_amounts already set to empty arrays above
?>

<div style="padding: 2rem 0; max-width: none; margin: 0; width: 100%;">
    <div class="admin-layout" style="display: flex; gap: 0; align-items: flex-start; margin: 0; padding: 0;">
        <div style="padding-left: 0; margin-left: 0;">
            <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>
        </div>

        <div class="admin-main" style="flex: 1; min-width: 0; padding-left: 2.5rem; padding-right: 1.5rem; max-width: 1400px;">
            <h1 style="font-size: 1.75rem; margin: 0 0 1.5rem 0;">Analytics Dashboard</h1>

            <!-- Quick Stats Grid -->
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: linear-gradient(135deg, var(--primary), rgba(79,70,229,0.8)); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.9; margin-bottom: 0.3rem;">Total Raised</div>
                    <div style="font-size: 1.2rem; font-weight: 700;">KES <?= number_format($donation_stats['total'] ?? 0, 0) ?></div>
                    <div style="font-size: 0.7rem; opacity: 0.8; margin-top: 0.2rem;"><?= number_format($donation_stats['count'] ?? 0) ?> txns</div>
                </div>
                <div style="background: linear-gradient(135deg, var(--secondary), rgba(16,185,129,0.8)); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.9; margin-bottom: 0.3rem;">Active Campaigns</div>
                    <div style="font-size: 1.2rem; font-weight: 700;"><?= number_format($campaign_stats['active']) ?></div>
                    <div style="font-size: 0.7rem; opacity: 0.8; margin-top: 0.2rem;">of <?= number_format($campaign_stats['total']) ?></div>
                </div>
                <div style="background: linear-gradient(135deg, var(--accent), rgba(245,158,11,0.8)); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.9; margin-bottom: 0.3rem;">Verified NGOs</div>
                    <div style="font-size: 1.2rem; font-weight: 700;"><?= number_format($verified_ngos) ?></div>
                    <div style="font-size: 0.7rem; opacity: 0.8; margin-top: 0.2rem;">Active Partners</div>
                </div>
                <div style="background: linear-gradient(135deg, #8B5CF6, rgba(139,92,246,0.8)); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.9; margin-bottom: 0.3rem;">Avg Donation</div>
                    <div style="font-size: 1.2rem; font-weight: 700;">KES <?= number_format($donation_stats['avg'] ?? 0, 0) ?></div>
                    <div style="font-size: 0.7rem; opacity: 0.8; margin-top: 0.2rem;">Per Transaction</div>
                </div>
                <div style="background: linear-gradient(135deg, #EC4899, rgba(236,72,153,0.8)); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.9; margin-bottom: 0.3rem;">Total Donors</div>
                    <div style="font-size: 1.2rem; font-weight: 700;"><?= number_format($donor_count) ?></div>
                    <div style="font-size: 0.7rem; opacity: 0.8; margin-top: 0.2rem;">Registered</div>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <!-- Donation Trends -->
                <div style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 1.25rem;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 700;">Donation Trends (30 Days)</h3>
                    <canvas id="trendChart" style="max-height: 250px;"></canvas>
                </div>

                <!-- Payment Methods -->
                <div style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 1.25rem;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 700;">Payment Methods</h3>
                    <canvas id="paymentChart" style="max-height: 250px;"></canvas>
                </div>

                <!-- Campaign Status -->
                <div style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 1.25rem;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 700;">Campaign Status</h3>
                    <canvas id="statusChart" style="max-height: 250px;"></canvas>
                </div>
            </div>

            <!-- Charts Row 2 -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <!-- Top NGOs -->
                <div style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 1.25rem;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 700;">Top NGOs by Donations</h3>
                    <canvas id="ngoChart" style="max-height: 250px;"></canvas>
                </div>

                <!-- Top Campaigns -->
                <div style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 1.25rem; max-height: 350px; overflow-y: auto;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 700;">Top Campaigns</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach ($top_campaigns as $idx => $camp): ?>
                            <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: var(--background); border-radius: var(--radius-md);">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; flex-shrink: 0;"><?= $idx + 1 ?></div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= h(mb_substr($camp['title'], 0, 20)) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">KES <?= number_format($camp['total'], 0) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Active Campaigns -->
            <div style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 1.25rem; margin-bottom: 1.5rem;">
                <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 700;">Active Campaigns Progress</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                    <?php foreach ($recent_campaigns as $camp): ?>
                        <?php $progress = $camp['goal_amount'] > 0 ? round(($camp['current_amount'] / $camp['goal_amount']) * 100) : 0; ?>
                        <div style="padding: 1rem; background: var(--background); border-radius: var(--radius-md);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <div style="font-size: 0.85rem; font-weight: 600; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1;"><?= h(mb_substr($camp['title'], 0, 25)) ?></div>
                                <div style="font-size: 0.75rem; color: var(--primary); font-weight: 700; margin-left: 0.5rem;"><?= $progress ?>%</div>
                            </div>
                            <div style="width: 100%; height: 6px; background: var(--border); border-radius: 999px; overflow: hidden; margin-bottom: 0.5rem;">
                                <div style="width: <?= min(100, $progress) ?>%; height: 100%; background: linear-gradient(90deg, var(--primary), var(--accent));"></div>
                            </div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);">KES <?= number_format($camp['current_amount'], 0) ?> / KES <?= number_format($camp['goal_amount'], 0) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Export Section -->
            <div style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 1.25rem;">
                <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 700;">Data Exports</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.75rem;">
                    <a href="?export=donations" class="btn btn-primary" style="padding: 0.6rem 1rem; font-size: 0.85rem; text-align: center; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <i class="fa-solid fa-download" style="font-size: 0.8rem;"></i> Donations
                    </a>
                    <a href="?export=campaigns" class="btn btn-primary" style="padding: 0.6rem 1rem; font-size: 0.85rem; text-align: center; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <i class="fa-solid fa-download" style="font-size: 0.8rem;"></i> Campaigns
                    </a>
                    <a href="?export=transactions" class="btn btn-primary" style="padding: 0.6rem 1rem; font-size: 0.85rem; text-align: center; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <i class="fa-solid fa-download" style="font-size: 0.8rem;"></i> Transactions
                    </a>
                    <a href="?export=users" class="btn btn-primary" style="padding: 0.6rem 1rem; font-size: 0.85rem; text-align: center; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <i class="fa-solid fa-download" style="font-size: 0.8rem;"></i> Users
                    </a>
                    <a href="?export=ngos" class="btn btn-primary" style="padding: 0.6rem 1rem; font-size: 0.85rem; text-align: center; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <i class="fa-solid fa-download" style="font-size: 0.8rem;"></i> NGOs
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartColors = {
    primary: '#0066FF',
    accent: '#00D9FF',
    secondary: '#10B981',
    danger: '#EF4444',
    warning: '#F59E0B',
    purple: '#8B5CF6',
    pink: '#EC4899'
};

// Donation Trends
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($trend_dates) ?>,
        datasets: [{
            label: 'Donations (KES)',
            data: <?= json_encode($trend_amounts) ?>,
            borderColor: chartColors.primary,
            backgroundColor: 'rgba(0, 102, 255, 0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: chartColors.primary
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// Payment Methods
new Chart(document.getElementById('paymentChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($payment_labels) ?>,
        datasets: [{
            data: <?= json_encode($payment_counts) ?>,
            backgroundColor: [chartColors.primary, chartColors.accent, chartColors.secondary, chartColors.warning]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
    }
});

// Campaign Status
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($status_labels) ?>,
        datasets: [{
            data: <?= json_encode($status_counts) ?>,
            backgroundColor: [chartColors.secondary, chartColors.warning, chartColors.danger]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
    }
});

// Top NGOs
new Chart(document.getElementById('ngoChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($ngo_labels) ?>,
        datasets: [{
            label: 'Total Donations (KES)',
            data: <?= json_encode($ngo_amounts) ?>,
            backgroundColor: chartColors.primary
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true } }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
