<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header("Location: /share_hope/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get donation statistics for this NGO (as a donor)
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT d.id) as total_donations,
        COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) as total_donated,
        COUNT(DISTINCT d.campaign_id) as campaigns_supported,
        COUNT(CASE WHEN d.status = 'completed' THEN d.id END) as completed_donations,
        AVG(CASE WHEN d.status = 'completed' THEN d.amount ELSE NULL END) as avg_donation
    FROM donations d
    WHERE d.donor_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Get monthly donation data
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(d.created_at, '%Y-%m') as month,
        SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END) as total
    FROM donations d
    WHERE d.donor_id = ? AND d.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(d.created_at, '%Y-%m')
    ORDER BY month
");
$stmt->execute([$user_id]);
$monthly_data = $stmt->fetchAll();

// Get top campaigns supported
$stmt = $pdo->prepare("
    SELECT c.title, 
           COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) as donated_amount,
           COUNT(CASE WHEN d.status = 'completed' THEN d.id END) as donation_count
    FROM donations d
    JOIN campaigns c ON d.campaign_id = c.id
    WHERE d.donor_id = ?
    GROUP BY d.campaign_id
    ORDER BY donated_amount DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$top_campaigns = $stmt->fetchAll();
?>

<div class="admin-layout">
    <?php include __DIR__ . '/includes/ngo_nav.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1><i class="fa-solid fa-chart-bar"></i> My Donation Reports</h1>
            <button onclick="window.print()" class="btn btn-outline">
                <i class="fa-solid fa-print"></i> Print Report
            </button>
        </div>

        <!-- Key Metrics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700;">$<?= number_format($stats['total_donated'], 2) ?></h3>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Total Donated</p>
                    </div>
                    <i class="fa-solid fa-dollar-sign" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700;"><?= $stats['total_donations'] ?></h3>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Total Donations</p>
                    </div>
                    <i class="fa-solid fa-hand-holding-dollar" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700;"><?= $stats['campaigns_supported'] ?></h3>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Campaigns Supported</p>
                    </div>
                    <i class="fa-solid fa-bullhorn" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700;">$<?= number_format($stats['avg_donation'] ?? 0, 2) ?></h3>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Avg Donation</p>
                    </div>
                    <i class="fa-solid fa-chart-line" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; width: 80vw; margin-left: auto; margin-right: auto;">
            <!-- Monthly Donation Chart -->
            <div class="chart-section" style="background: var(--surface); padding: 1.25rem; border-radius: var(--radius-lg); border: 1px solid var(--border);">
                <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 600;">Monthly Donation Trend</h3>
                <div style="height: 150px; display: flex; align-items: end; gap: 0.75rem; padding: 0.5rem 0;">
                    <?php if(empty($monthly_data)): ?>
                        <div style="width: 100%; text-align: center; color: var(--text-muted); display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                            <i class="fa-solid fa-chart-line" style="font-size: 1.5rem; opacity: 0.3; margin-bottom: 0.5rem;"></i>
                            <p style="margin: 0; font-size: 0.85rem;">No donation data available yet.</p>
                        </div>
                    <?php else: ?>
                        <?php 
                        $max_amount = max(array_column($monthly_data, 'total'));
                        foreach($monthly_data as $data): 
                            $height = $max_amount > 0 ? ($data['total'] / $max_amount) * 120 : 0;
                        ?>
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                                <div style="background: var(--primary); width: 100%; height: <?= $height ?>px; border-radius: var(--radius-sm) var(--radius-sm) 0 0; margin-bottom: 0.25rem; transition: all 0.3s ease;" title="$<?= number_format($data['total'], 2) ?>"></div>
                                <small style="color: var(--text-muted); font-size: 0.65rem;"><?= date('M', strtotime($data['month'] . '-01')) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Campaigns Supported -->
            <div class="campaigns-section" style="background: var(--surface); padding: 1.25rem; border-radius: var(--radius-lg); border: 1px solid var(--border);">
                <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 600;">Top Campaigns Supported</h3>
                <?php if(empty($top_campaigns)): ?>
                    <div style="text-align: center; color: var(--text-muted); padding: 1rem 0;">
                        <i class="fa-solid fa-bullhorn" style="font-size: 1.25rem; opacity: 0.3; margin-bottom: 0.5rem;"></i>
                        <p style="margin: 0; font-size: 0.85rem;">No donations made yet.</p>
                    </div>
                <?php else: ?>
                    <div class="campaigns-list">
                        <?php foreach($top_campaigns as $campaign): ?>
                            <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                                <div style="flex: 1;">
                                    <h4 style="margin: 0 0 0.15rem 0; font-size: 0.85rem;"><?= h(substr($campaign['title'], 0, 30)) ?><?= strlen($campaign['title']) > 30 ? '...' : '' ?></h4>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">
                                        <?= $campaign['donation_count'] ?> donations
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 600; color: var(--secondary); font-size: 0.9rem;">$<?= number_format($campaign['donated_amount'], 0) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detailed Report -->
        <div class="report-section" style="background: var(--surface); padding: 1.75rem; border-radius: var(--radius-lg); border: 1px solid var(--border); margin-top: 1.5rem; box-shadow: var(--shadow-sm); width: 80vw; margin-left: auto; margin-right: auto;">
            <h3 style="margin: 0 0 1.5rem 0; text-align: center; font-size: 1.25rem; font-weight: 700; color: var(--primary);">Donation Summary</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div style="padding: 1.25rem; background: rgba(0, 102, 255, 0.03); border-radius: var(--radius-md); border-left: 4px solid var(--primary);">
                    <h4 style="margin: 0 0 1rem 0; font-size: 0.95rem; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 0.4rem;"><i class="fa-solid fa-chart-line" style="color: var(--primary); font-size: 0.9rem;"></i> Donation Performance</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 0.7rem 0; border-bottom: 1px solid rgba(0, 102, 255, 0.1); display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--text-muted); font-weight: 500; font-size: 0.9rem;">Total Donations Made:</span>
                            <span style="font-weight: 700; color: var(--text-main); font-size: 1rem;"><?= $stats['total_donations'] ?></span>
                        </li>
                        <li style="padding: 0.7rem 0; border-bottom: 1px solid rgba(0, 102, 255, 0.1); display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--text-muted); font-weight: 500; font-size: 0.9rem;">Completed Donations:</span>
                            <span style="font-weight: 700; color: var(--secondary); font-size: 1rem;"><?= $stats['completed_donations'] ?></span>
                        </li>
                        <li style="padding: 0.7rem 0; display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--text-muted); font-weight: 500; font-size: 0.9rem;">Average Donation Amount:</span>
                            <span style="font-weight: 700; color: var(--primary); font-size: 1rem;">$<?= number_format($stats['avg_donation'] ?? 0, 2) ?></span>
                        </li>
                    </ul>
                </div>
                <div style="padding: 1.25rem; background: rgba(0, 217, 255, 0.03); border-radius: var(--radius-md); border-left: 4px solid var(--accent);">
                    <h4 style="margin: 0 0 1rem 0; font-size: 0.95rem; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 0.4rem;"><i class="fa-solid fa-bullhorn" style="color: var(--accent); font-size: 0.9rem;"></i> Campaign Support</h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="padding: 0.7rem 0; border-bottom: 1px solid rgba(0, 217, 255, 0.1); display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--text-muted); font-weight: 500; font-size: 0.9rem;">Campaigns Supported:</span>
                            <span style="font-weight: 700; color: var(--text-main); font-size: 1rem;"><?= $stats['campaigns_supported'] ?></span>
                        </li>
                        <li style="padding: 0.7rem 0; border-bottom: 1px solid rgba(0, 217, 255, 0.1); display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--text-muted); font-weight: 500; font-size: 0.9rem;">Total Impact:</span>
                            <span style="font-weight: 700; color: var(--secondary); font-size: 1rem;">$<?= number_format($stats['total_donated'], 2) ?></span>
                        </li>
                        <li style="padding: 0.7rem 0; display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--text-muted); font-weight: 500; font-size: 0.9rem;">Average per Campaign:</span>
                            <span style="font-weight: 700; color: var(--accent); font-size: 1rem;">$<?= $stats['campaigns_supported'] > 0 ? number_format($stats['total_donated'] / $stats['campaigns_supported'], 2) : '0.00' ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .admin-nav, .admin-header button { display: none !important; }
    .admin-content { margin-left: 0 !important; }
}

@media (max-width: 768px) {
    .report-section > div[style*="grid-template-columns: 1fr 1fr"],
    [style*="width: 80vw"] {
        width: 100% !important;
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
