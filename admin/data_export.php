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

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Handle exports
if (!empty($_GET['export'])) {
    $export_type = $_GET['export'];
    
    try {
        switch($export_type) {
            case 'donations':
                // Export completed donations
                $stmt = $pdo->query("
                    SELECT d.id, d.transaction_id, d.amount, d.payment_method, d.status, d.is_anonymous,
                           d.created_at, u.name as donor_name, u.email as donor_email,
                           c.title as campaign_title, n.name as ngo_name
                    FROM donations d
                    LEFT JOIN users u ON d.donor_id = u.id
                    JOIN campaigns c ON d.campaign_id = c.id
                    JOIN ngos ng ON c.ngo_id = ng.id
                    JOIN users n ON ng.user_id = n.id
                    WHERE d.status = 'completed'
                    ORDER BY d.created_at DESC
                ");
                $data = $stmt->fetchAll();
                
                $filename = 'donations_export_' . date('Y-m-d_His') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Date', 'Transaction ID', 'Donor', 'Amount', 'Payment Method', 'Campaign', 'NGO', 'Status']);
                
                foreach ($data as $row) {
                    fputcsv($output, [
                        date('Y-m-d H:i', strtotime($row['created_at'])),
                        $row['transaction_id'] ?? 'N/A',
                        $row['is_anonymous'] ? 'Anonymous' : ($row['donor_name'] ?? 'Guest'),
                        number_format($row['amount'], 2),
                        ucfirst($row['payment_method']),
                        $row['campaign_title'],
                        $row['ngo_name'],
                        ucfirst($row['status'])
                    ]);
                }
                fclose($output);
                log_admin_activity($pdo, $_SESSION['user_id'], 'export_donations', 'export', 'donations', null, 'Donations', 'Exported ' . count($data) . ' donation records');
                exit;
                
            case 'ngos':
                // Export NGO list with stats
                $stmt = $pdo->query("
                    SELECT n.id, u.name, u.email, n.is_verified, n.created_at, n.mission,
                           COUNT(c.id) as campaigns_count,
                           COALESCE(SUM(d.amount), 0) as total_raised
                    FROM ngos n
                    JOIN users u ON n.user_id = u.id
                    LEFT JOIN campaigns c ON n.id = c.ngo_id
                    LEFT JOIN donations d ON c.id = d.campaign_id AND d.status = 'completed'
                    GROUP BY n.id
                    ORDER BY n.created_at DESC
                ");
                $data = $stmt->fetchAll();
                
                $filename = 'ngos_export_' . date('Y-m-d_His') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Name', 'Email', 'Verified', 'Campaigns', 'Total Raised (KES)', 'Joined Date']);
                
                foreach ($data as $row) {
                    fputcsv($output, [
                        $row['name'],
                        $row['email'],
                        $row['is_verified'] ? 'Yes' : 'No',
                        $row['campaigns_count'],
                        number_format($row['total_raised'], 2),
                        date('Y-m-d', strtotime($row['created_at']))
                    ]);
                }
                fclose($output);
                log_admin_activity($pdo, $_SESSION['user_id'], 'export_ngos', 'export', 'ngos', null, 'NGOs', 'Exported ' . count($data) . ' NGO records');
                exit;
                
            case 'campaigns':
                // Export campaign performance data
                $stmt = $pdo->query("
                    SELECT c.id, c.title, c.status, c.goal_amount, c.current_amount,
                           c.deadline, c.created_at, u.name as ngo_name,
                           COUNT(DISTINCT d.id) as donor_count,
                           COUNT(DISTINCT d.donor_id) as unique_donors
                    FROM campaigns c
                    JOIN ngos n ON c.ngo_id = n.id
                    JOIN users u ON n.user_id = u.id
                    LEFT JOIN donations d ON c.id = d.campaign_id AND d.status = 'completed'
                    GROUP BY c.id
                    ORDER BY c.created_at DESC
                ");
                $data = $stmt->fetchAll();
                
                $filename = 'campaigns_export_' . date('Y-m-d_His') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Campaign', 'NGO', 'Status', 'Goal (KES)', 'Raised (KES)', 'Progress %', 'Donors', 'Deadline', 'Created']);
                
                foreach ($data as $row) {
                    $progress = ($row['current_amount'] / $row['goal_amount']) * 100;
                    fputcsv($output, [
                        $row['title'],
                        $row['ngo_name'],
                        ucfirst($row['status']),
                        number_format($row['goal_amount'], 2),
                        number_format($row['current_amount'], 2),
                        number_format($progress, 1),
                        $row['unique_donors'],
                        date('Y-m-d', strtotime($row['deadline'])),
                        date('Y-m-d', strtotime($row['created_at']))
                    ]);
                }
                fclose($output);
                log_admin_activity($pdo, $_SESSION['user_id'], 'export_campaigns', 'export', 'campaigns', null, 'Campaigns', 'Exported ' . count($data) . ' campaign records');
                exit;
                
            case 'users':
                // Export user base stats
                $stmt = $pdo->query("
                    SELECT 
                        (SELECT COUNT(*) FROM users WHERE role = 'donor') as donors,
                        (SELECT COUNT(*) FROM users WHERE role = 'ngo') as ngos,
                        (SELECT COUNT(*) FROM users WHERE role = 'admin') as admins,
                        (SELECT COUNT(DISTINCT donor_id) FROM donations WHERE status = 'completed') as active_donors,
                        (SELECT SUM(amount) FROM donations WHERE status = 'completed') as total_raised
                ");
                $stats = $stmt->fetch();
                
                $filename = 'platform_stats_' . date('Y-m-d_His') . '.csv';
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Metric', 'Count/Value']);
                fputcsv($output, ['Total Donors', $stats['donors']]);
                fputcsv($output, ['Total NGOs', $stats['ngos']]);
                fputcsv($output, ['Admin Staff', $stats['admins']]);
                fputcsv($output, ['Active Donors', $stats['active_donors']]);
                fputcsv($output, ['Total Raised (KES)', number_format($stats['total_raised'] ?? 0, 2)]);
                fputcsv($output, ['Export Date', date('Y-m-d H:i:s')]);
                fclose($output);
                log_admin_activity($pdo, $_SESSION['user_id'], 'export_stats', 'export', 'platform_stats', null, 'Platform Stats', 'Exported platform statistics');
                exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Export failed: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';

// Get stats for dashboard
$stats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'donor') as total_donors,
        (SELECT COUNT(*) FROM users WHERE role = 'ngo') as total_ngos,
        (SELECT COUNT(*) FROM campaigns WHERE status = 'active') as active_campaigns,
        (SELECT COUNT(*) FROM donations WHERE status = 'completed') as completed_donations,
        (SELECT SUM(amount) FROM donations WHERE status = 'completed') as total_raised
")->fetch();
?>

<div class="container" style="padding: 2.5rem 0; max-width: 1150px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">
            <div style="margin-bottom: 2.5rem;">
                <h1 style="font-size: 2rem; margin: 0 0 0.5rem 0;"><i class="fa-solid fa-download text-accent"></i> Data Export Center</h1>
                <p style="color: var(--text-muted); margin: 0;">Download platform data in CSV format for analysis</p>
            </div>

            <!-- Statistics -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                <div style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 1.5rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 0.5rem;"><i class="fa-solid fa-users"></i> Total Donors</div>
                    <div style="font-size: 2rem; font-weight: 700;"><?= number_format($stats['total_donors']) ?></div>
                </div>
                <div style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 1.5rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 0.5rem;"><i class="fa-solid fa-building"></i> NGOs</div>
                    <div style="font-size: 2rem; font-weight: 700;"><?= number_format($stats['total_ngos']) ?></div>
                </div>
                <div style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 1.5rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 0.5rem;"><i class="fa-solid fa-bullhorn"></i> Active Campaigns</div>
                    <div style="font-size: 2rem; font-weight: 700;"><?= number_format($stats['active_campaigns']) ?></div>
                </div>
                <div style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 1.5rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 0.5rem;"><i class="fa-solid fa-hand-holding-heart"></i> Total Raised</div>
                    <div style="font-size: 2rem; font-weight: 700;">KES <?= number_format($stats['total_raised'] ?? 0, 0) ?></div>
                </div>
            </div>

            <!-- Export Options -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
                <!-- Donations Export -->
                <div style="background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; transition: all 0.3s ease;"
                    onmouseover="this.style.boxShadow='0 10px 25px rgba(0,0,0,0.1)'; this.style.transform='translateY(-4px)';"
                    onmouseout="this.style.boxShadow='var(--shadow-sm)'; this.style.transform='translateY(0)';">
                    <div style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; text-align: center;">
                        <i class="fa-solid fa-heart" style="font-size: 3rem; opacity: 0.8;"></i>
                        <h3 style="margin: 1rem 0 0 0;">Donations Export</h3>
                    </div>
                    <div style="padding: 1.5rem;">
                        <p style="color: var(--text-muted); margin: 0 0 1.5rem 0; font-size: 0.9rem;">
                            Export all completed donations with donor info, amounts, campaigns, and payment methods
                        </p>
                        <p style="color: var(--text-muted); margin: 0 0 1.5rem 0; font-size: 0.8rem;">
                            <strong>Includes:</strong> <?= number_format($stats['completed_donations']) ?> records
                        </p>
                        <a href="?export=donations" class="btn btn-primary" style="width: 100%;">
                            <i class="fa-solid fa-download"></i> Download CSV
                        </a>
                    </div>
                </div>

                <!-- NGOs Export -->
                <div style="background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; transition: all 0.3s ease;"
                    onmouseover="this.style.boxShadow='0 10px 25px rgba(0,0,0,0.1)'; this.style.transform='translateY(-4px)';"
                    onmouseout="this.style.boxShadow='var(--shadow-sm)'; this.style.transform='translateY(0)';">
                    <div style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; text-align: center;">
                        <i class="fa-solid fa-building" style="font-size: 3rem; opacity: 0.8;"></i>
                        <h3 style="margin: 1rem 0 0 0;">NGOs Export</h3>
                    </div>
                    <div style="padding: 1.5rem;">
                        <p style="color: var(--text-muted); margin: 0 0 1.5rem 0; font-size: 0.9rem;">
                            Export comprehensive NGO list with verification status, campaigns, and funds raised
                        </p>
                        <p style="color: var(--text-muted); margin: 0 0 1.5rem 0; font-size: 0.8rem;">
                            <strong>Includes:</strong> <?= number_format($stats['total_ngos']) ?> organizations
                        </p>
                        <a href="?export=ngos" class="btn btn-primary" style="width: 100%;">
                            <i class="fa-solid fa-download"></i> Download CSV
                        </a>
                    </div>
                </div>

                <!-- Campaigns Export -->
                <div style="background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; transition: all 0.3s ease;"
                    onmouseover="this.style.boxShadow='0 10px 25px rgba(0,0,0,0.1)'; this.style.transform='translateY(-4px)';"
                    onmouseout="this.style.boxShadow='var(--shadow-sm)'; this.style.transform='translateY(0)';">
                    <div style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; text-align: center;">
                        <i class="fa-solid fa-bullhorn" style="font-size: 3rem; opacity: 0.8;"></i>
                        <h3 style="margin: 1rem 0 0 0;">Campaigns Export</h3>
                    </div>
                    <div style="padding: 1.5rem;">
                        <p style="color: var(--text-muted); margin: 0 0 1.5rem 0; font-size: 0.9rem;">
                            Export campaign performance data including goals, progress, and donor metrics
                        </p>
                        <p style="color: var(--text-muted); margin: 0 0 1.5rem 0; font-size: 0.8rem;">
                            <strong>Includes:</strong> <?= number_format($stats['active_campaigns']) ?> active campaigns
                        </p>
                        <a href="?export=campaigns" class="btn btn-primary" style="width: 100%;">
                            <i class="fa-solid fa-download"></i> Download CSV
                        </a>
                    </div>
                </div>

                <!-- Platform Stats -->
                <div style="background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; transition: all 0.3s ease;"
                    onmouseover="this.style.boxShadow='0 10px 25px rgba(0,0,0,0.1)'; this.style.transform='translateY(-4px)';"
                    onmouseout="this.style.boxShadow='var(--shadow-sm)'; this.style.transform='translateY(0)';">
                    <div style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; text-align: center;">
                        <i class="fa-solid fa-chart-line" style="font-size: 3rem; opacity: 0.8;"></i>
                        <h3 style="margin: 1rem 0 0 0;">Platform Stats</h3>
                    </div>
                    <div style="padding: 1.5rem;">
                        <p style="color: var(--text-muted); margin: 0 0 1.5rem 0; font-size: 0.9rem;">
                            Quick snapshot of platform metrics: users, NGOs, campaigns, and revenue summary
                        </p>
                        <p style="color: var(--text-muted); margin: 0 0 1.5rem 0; font-size: 0.8rem;">
                            <strong>Generated:</strong> Real-time data
                        </p>
                        <a href="?export=users" class="btn btn-primary" style="width: 100%;">
                            <i class="fa-solid fa-download"></i> Download CSV
                        </a>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div style="background: rgba(79, 70, 229, 0.05); border-left: 4px solid var(--primary); padding: 1.5rem; border-radius: var(--radius-md); margin-top: 3rem;">
                <h4 style="margin: 0 0 0.75rem 0; color: var(--primary);">📊 Export Information</h4>
                <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-muted); font-size: 0.9rem; line-height: 1.8;">
                    <li>All exports are in CSV format (compatible with Excel, Google Sheets, etc.)</li>
                    <li>Each export automatically logs an activity entry for audit trailing</li>
                    <li>Donations export includes only completed transactions</li>
                    <li>NGO export includes campaign count and total funds raised statistics</li>
                    <li>Campaign export shows progress percentage and unique donor counts</li>
                    <li>Platform stats provide a quick overview of key metrics</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

