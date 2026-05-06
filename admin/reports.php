<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';


// Date filtering
$start_date = $_GET['start_date'] ?? date('Y-m-01', strtotime('-6 months'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Report data based on date filter
$stmt = $pdo->prepare("SELECT 
    SUM(amount) as total_contributions,
    COUNT(*) as total_donations,
    AVG(amount) as avg_donation,
    MIN(amount) as min_donation,
    MAX(amount) as max_donation
    FROM donations 
    WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$report_stats = $stmt->fetch();

// Min and Max contributors by stakeholder type (Private vs NGO)
$stmt = $pdo->prepare("SELECT 
    CASE WHEN d.donor_id IS NULL THEN 'Anonymous' ELSE 'Private Donors' END as contributor_type,
    SUM(d.amount) as total
    FROM donations d 
    WHERE d.status = 'completed' AND DATE(d.created_at) BETWEEN ? AND ?
    GROUP BY CASE WHEN d.donor_id IS NULL THEN 'Anonymous' ELSE 'Private Donors' END
    ORDER BY total DESC");
$stmt->execute([$start_date, $end_date]);
$contributor_types = $stmt->fetchAll();
$max_contributor_type = $contributor_types[0] ?? null;
$min_contributor_type = end($contributor_types) ?: null;

// Category analysis (by Campaign)
$stmt = $pdo->prepare("SELECT c.title as category, SUM(d.amount) as total
    FROM donations d 
    JOIN campaigns c ON d.campaign_id = c.id 
    WHERE d.status = 'completed' AND DATE(d.created_at) BETWEEN ? AND ?
    GROUP BY c.id
    ORDER BY total DESC");
$stmt->execute([$start_date, $end_date]);
$categories = $stmt->fetchAll();
$max_category = $categories[0] ?? null;
$min_category = end($categories) ?: null;

// Trend data for chart
$trend_data = [];
$trend_labels = [];
$current = strtotime($start_date);
$end = strtotime($end_date);
while ($current <= $end) {
    $month_start = date('Y-m-01', $current);
    $month_end = date('Y-m-t', $current);
    $month_label = date('M Y', $current);
    
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM donations WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$month_start, $month_end]);
    $total = $stmt->fetch()['total'] ?? 0;
    
    $trend_labels[] = $month_label;
    $trend_data[] = (float) $total;
    
    $current = strtotime('+1 month', $current);
}

// Pie chart data - Top 5 campaigns by donation amount
$stmt = $pdo->prepare("SELECT c.title as campaign_name, SUM(d.amount) as total_amount
    FROM donations d 
    JOIN campaigns c ON d.campaign_id = c.id 
    WHERE d.status = 'completed' AND DATE(d.created_at) BETWEEN ? AND ?
    GROUP BY c.id, c.title
    ORDER BY total_amount DESC
    LIMIT 5");
$stmt->execute([$start_date, $end_date]);
$pie_campaigns = $stmt->fetchAll();

$pie_labels = [];
$pie_data = [];
foreach ($pie_campaigns as $campaign) {
    $pie_labels[] = mb_substr($campaign['campaign_name'], 0, 25) . (strlen($campaign['campaign_name']) > 25 ? '...' : '');
    $pie_data[] = (float) $campaign['total_amount'];
}

// Add "Others" if there are more campaigns
if (count($pie_campaigns) >= 5) {
    $stmt = $pdo->prepare("SELECT SUM(d.amount) as other_total
        FROM donations d 
        JOIN campaigns c ON d.campaign_id = c.id 
        WHERE d.status = 'completed' AND DATE(d.created_at) BETWEEN ? AND ?
        AND c.id NOT IN (
            SELECT c2.id FROM donations d2 
            JOIN campaigns c2 ON d2.campaign_id = c2.id 
            WHERE d2.status = 'completed' AND DATE(d2.created_at) BETWEEN ? AND ?
            GROUP BY c2.id ORDER BY SUM(d2.amount) DESC LIMIT 5
        )");
    $stmt->execute([$start_date, $end_date, $start_date, $end_date]);
    $other_total = $stmt->fetch()['other_total'] ?? 0;
    if ($other_total > 0) {
        $pie_labels[] = 'Other Campaigns';
        $pie_data[] = (float) $other_total;
    }
}
?>

<div class="container" style="padding: 2.5rem 0; max-width: 1150px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        
        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>
        
        <div class="admin-main" style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
                <h1 style="font-size: 2rem; margin: 0;">Analytics & Reports</h1>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <button onclick="generateReport()" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                        <i class="fa-solid fa-download"></i> Download Report
                    </button>
                    <button onclick="window.print()" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
                        <i class="fa-solid fa-print"></i> Print
                    </button>
                </div>
            </div>

            <!-- Date Filter -->
            <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); margin-bottom: 2rem;">
                <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <label style="font-weight: 600;">Filter by Date:</label>
                    <input type="date" name="start_date" value="<?= $start_date ?>" class="form-control" style="width: auto;">
                    <span>to</span>
                    <input type="date" name="end_date" value="<?= $end_date ?>" class="form-control" style="width: auto;">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    <a href="reports.php" class="btn btn-outline">Reset</a>
                </form>
            </div>

            <!-- Report Stats -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Total Contributions</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--primary);">$<?= number_format($report_stats['total_contributions'] ?? 0, 2) ?></div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Max Contributor Type</div>
                    <div style="font-size: 1.1rem; font-weight: 600; color: var(--secondary);"><?= h($max_contributor_type['contributor_type'] ?? 'N/A') ?></div>
                    <div style="font-size: 0.9rem; color: var(--text-muted);">$<?= number_format($max_contributor_type['total'] ?? 0, 2) ?></div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Max Campaign</div>
                    <div style="font-size: 1.1rem; font-weight: 600; color: var(--accent);"><?= h(mb_substr($max_category['category'] ?? 'N/A', 0, 25)) ?></div>
                    <div style="font-size: 0.9rem; color: var(--text-muted);">$<?= number_format($max_category['total'] ?? 0, 2) ?></div>
                </div>
            </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 3rem;">
            <!-- Line Chart: Donation Trends -->
            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; padding: 1.5rem;">
                <h3 style="margin-bottom: 1.5rem; text-align: center; color: var(--text-main);">Donation Trend</h3>
                <canvas id="trendChart" style="width: 100%; height: 300px;"></canvas>
            </div>

            <!-- Pie Chart: Contributors -->
            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; padding: 1.5rem;">
                <h3 style="margin-bottom: 1.5rem; text-align: center; color: var(--text-main);">Top Campaigns</h3>
                <div style="display: flex; justify-content: center;">
                    <canvas id="pieChart" style="max-width: 350px; max-height: 350px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Integration -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Line Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($trend_labels) ?>,
            datasets: [{
                label: 'Donation Volume ($)',
                data: <?= json_encode($trend_data) ?>,
                borderColor: '#4F46E5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Pie Chart
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($pie_labels) ?>,
            datasets: [{
                data: <?= json_encode($pie_data) ?>,
                backgroundColor: [
                    '#4F46E5', // Indigo
                    '#10B981', // Emerald  
                    '#F59E0B', // Amber
                    '#EF4444', // Red
                    '#8B5CF6', // Violet
                    '#6B7280'  // Gray for Others
                ],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return label + ': $' + value.toLocaleString() + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '50%'
        }
    });

    function generateReport() {
        // Wait for charts to be fully rendered
        setTimeout(() => {
            try {
                // Capture chart images
                const trendChart = document.getElementById('trendChart');
                const pieChart = document.getElementById('pieChart');
                
                let trendImage = '';
                let pieImage = '';
                
                if (trendChart) {
                    trendImage = trendChart.toDataURL('image/png', 1.0);
                }
                if (pieChart) {
                    pieImage = pieChart.toDataURL('image/png', 1.0);
                }
                
                const reportContent = `
                    <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">
                        <div style="text-align: center; border-bottom: 2px solid #4F46E5; padding-bottom: 20px; margin-bottom: 30px;">
                            <h1 style="color: #4F46E5; margin: 0;">SHARE HOPE</h1>
                            <h2 style="margin: 10px 0; color: #333;">Donation Analytics Report</h2>
                            <p style="color: #666; margin: 0;">Period: <?= date('M j, Y', strtotime($start_date)) ?> - <?= date('M j, Y', strtotime($end_date)) ?></p>
                            <p style="color: #666; margin: 5px 0;">Generated: <?= date('M j, Y g:i A') ?></p>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <h3 style="margin-top: 0; color: #4F46E5;">Summary Statistics</h3>
                                <p><strong>Total Contributions:</strong> $<?= number_format($report_stats['total_contributions'] ?? 0, 2) ?></p>
                                <p><strong>Total Donations:</strong> <?= number_format($report_stats['total_donations'] ?? 0) ?></p>
                                <p><strong>Average Donation:</strong> $<?= number_format($report_stats['avg_donation'] ?? 0, 2) ?></p>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <h3 style="margin-top: 0; color: #10B981;">Key Insights</h3>
                                <p><strong>Top Contributor Type:</strong> <?= h($max_contributor_type['contributor_type'] ?? 'N/A') ?> ($<?= number_format($max_contributor_type['total'] ?? 0, 2) ?>)</p>
                                <p><strong>Top Campaign:</strong> <?= h(mb_substr($max_category['category'] ?? 'N/A', 0, 30)) ?> ($<?= number_format($max_category['total'] ?? 0, 2) ?>)</p>
                                <p><strong>Min Contributor Type:</strong> <?= h($min_contributor_type['contributor_type'] ?? 'N/A') ?> ($<?= number_format($min_contributor_type['total'] ?? 0, 2) ?>)</p>
                            </div>
                        </div>
                        
                        ${trendImage && pieImage ? `
                        <div style="margin-bottom: 20px; page-break-inside: avoid;">
                            <h3 style="color: #4F46E5; text-align: center; margin-bottom: 15px;">Analytics Dashboard</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; align-items: center;">
                                <div style="text-align: center;">
                                    <h4 style="color: #4F46E5; margin-bottom: 8px; font-size: 13px;">Donation Trend</h4>
                                    <img src="${trendImage}" style="max-width: 100%; height: 150px; object-fit: contain; border: 1px solid #ddd; border-radius: 6px;" alt="Donation Trend Chart">
                                </div>
                                <div style="text-align: center;">
                                    <h4 style="color: #10B981; margin-bottom: 8px; font-size: 13px;">Campaign Distribution</h4>
                                    <img src="${pieImage}" style="max-width: 100%; height: 150px; object-fit: contain; border: 1px solid #ddd; border-radius: 6px;" alt="Campaign Distribution Chart">
                                </div>
                            </div>
                        </div>
                        ` : '<div style="margin-bottom: 20px;"><h3 style="color: #4F46E5; text-align: center;">Analytics Dashboard</h3><p style="text-align: center; color: #666;">Chart data not available</p></div>'}
                        
                        <div style="text-align: center; border-top: 1px solid #ddd; padding-top: 15px; margin-top: 25px; color: #666; font-size: 11px;">
                            <p style="margin: 5px 0;">© <?= date('Y') ?> Share Hope Platform | Empowering Change Through Transparency</p>
                            <p style="margin: 5px 0;">This report contains confidential information. Handle with care.</p>
                        </div>
                    </div>
                `;
                
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Share Hope - Analytics Report</title>
                            <style>
                                @media print {
                                    body { margin: 0; }
                                    @page { margin: 1in; }
                                    .page-break-inside { page-break-inside: avoid; }
                                }
                                @media screen {
                                    body { background: #f5f5f5; padding: 20px; }
                                }
                                img { max-width: 100% !important; height: auto !important; object-fit: contain !important; }
                                .chart-container { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
                                @media print and (max-width: 8in) {
                                    .chart-container { grid-template-columns: 1fr; }
                                }
                            </style>
                        </head>
                        <body>${reportContent}</body>
                    </html>
                `);
                printWindow.document.close();
                
                // Wait a bit more for images to load in the new window
                setTimeout(() => {
                    printWindow.print();
                }, 1000);
                
            } catch (error) {
                console.error('Error generating report:', error);
                alert('Error generating report. Please try again.');
            }
        }, 2000); // Wait 2 seconds for charts to fully render
    }
</script>

<style>
@media print {
    .admin-sidebar, .header-actions, .btn { display: none !important; }
    .admin-main { margin: 0 !important; }
    body { background: white !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
