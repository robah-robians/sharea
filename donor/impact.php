<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get donation impact statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT d.campaign_id) as campaigns_supported,
        0 as ngos_supported,
        COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) as total_donated,
        COUNT(CASE WHEN d.status = 'completed' THEN d.id END) as successful_donations,
        AVG(CASE WHEN d.status = 'completed' THEN d.amount ELSE NULL END) as avg_donation
    FROM donations d
    JOIN campaigns c ON d.campaign_id = c.id
    WHERE d.donor_id = ?
");
$stmt->execute([$user_id]);
$impact_stats = $stmt->fetch();

// Get category breakdown
$stmt = $pdo->prepare("
    SELECT 
        cat.name as category_name,
        COUNT(d.id) as donation_count,
        SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END) as total_amount
    FROM donations d
    JOIN campaigns c ON d.campaign_id = c.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE d.donor_id = ? AND d.status = 'completed'
    GROUP BY cat.id, cat.name
    ORDER BY total_amount DESC
");
$stmt->execute([$user_id]);
$category_breakdown = $stmt->fetchAll();

// Get recent campaign updates from supported campaigns
$stmt = $pdo->prepare("
    SELECT DISTINCT cu.*, c.title as campaign_title, 'Share Hope' as ngo_name
    FROM campaign_updates cu
    JOIN campaigns c ON cu.campaign_id = c.id
    WHERE c.id IN (
        SELECT DISTINCT campaign_id FROM donations WHERE donor_id = ? AND status = 'completed'
    )
    ORDER BY cu.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$campaign_updates = $stmt->fetchAll();

// Get monthly donation pattern
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as donation_count,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount
    FROM donations 
    WHERE donor_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");
$stmt->execute([$user_id]);
$monthly_pattern = $stmt->fetchAll();
?>

<div class="admin-layout">
    <?php include __DIR__ . '/includes/donor_nav.php'; ?>
    
    <div class="admin-content" style="padding-bottom: 40px;">
        <div class="admin-header" style="text-align: center;">
            <h1><i class="fa-solid fa-chart-pie"></i> Impact Tracker</h1>
            <div style="color: var(--text-muted);">Your giving impact overview</div>
        </div>

        <!-- Impact Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; width: 70vw; margin-left: auto; margin-right: auto;">
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700;">$<?= number_format($impact_stats['total_donated'], 2) ?></h3>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Total Impact</p>
                    </div>
                    <i class="fa-solid fa-heart" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700;"><?= $impact_stats['campaigns_supported'] ?></h3>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Campaigns Supported</p>
                    </div>
                    <i class="fa-solid fa-bullhorn" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700;"><?= $impact_stats['ngos_supported'] ?></h3>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">NGOs Helped</p>
                    </div>
                    <i class="fa-solid fa-hands-helping" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700;">$<?= number_format($impact_stats['avg_donation'] ?? 0, 2) ?></h3>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Avg Donation</p>
                    </div>
                    <i class="fa-solid fa-chart-line" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; width: 70vw; margin-left: auto; margin-right: auto;">
            <!-- Category Breakdown -->
            <div class="category-section" style="background: var(--surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border);">
                <h3 style="margin-top: 0;">Impact by Category</h3>
                <?php if(empty($category_breakdown)): ?>
                    <div style="text-align: center; color: var(--text-muted); padding: 2rem 0;">
                        <i class="fa-solid fa-chart-pie" style="font-size: 2rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                        <p>No donations completed yet.</p>
                    </div>
                <?php else: ?>
                    <div class="category-list">
                        <?php 
                        $total_donated = array_sum(array_column($category_breakdown, 'total_amount'));
                        foreach($category_breakdown as $category): 
                            $percentage = $total_donated > 0 ? ($category['total_amount'] / $total_donated) * 100 : 0;
                        ?>
                            <div style="margin-bottom: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span style="font-weight: 600;"><?= h($category['category_name'] ?? 'Other') ?></span>
                                    <span style="color: var(--success); font-weight: 600;">$<?= number_format($category['total_amount'], 2) ?></span>
                                </div>
                                <div style="background: var(--border); height: 8px; border-radius: var(--radius-sm); overflow: hidden;">
                                    <div style="background: var(--primary); height: 100%; width: <?= $percentage ?>%; transition: width 0.3s ease;"></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-top: 0.25rem; font-size: 0.875rem; color: var(--text-muted);">
                                    <span><?= round($percentage, 1) ?>% of total</span>
                                    <span><?= $category['donation_count'] ?> donations</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Monthly Giving Pattern -->
            <div class="pattern-section" style="background: var(--surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border);">
                <h3 style="margin-top: 0;">Monthly Giving Pattern</h3>
                <?php if(empty($monthly_pattern)): ?>
                    <div style="text-align: center; color: var(--text-muted); padding: 2rem 0;">
                        <i class="fa-solid fa-chart-line" style="font-size: 2rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                        <p>No donation history available.</p>
                    </div>
                <?php else: ?>
                    <div style="height: 200px; display: flex; align-items: end; gap: 0.5rem; padding: 1rem 0;">
                        <?php 
                        $max_amount = max(array_column($monthly_pattern, 'total_amount'));
                        foreach($monthly_pattern as $data): 
                            $height = $max_amount > 0 ? ($data['total_amount'] / $max_amount) * 150 : 0;
                        ?>
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                                <div style="background: var(--success); width: 100%; height: <?= $height ?>px; border-radius: var(--radius-sm) var(--radius-sm) 0 0; margin-bottom: 0.5rem; transition: all 0.3s ease;" title="$<?= number_format($data['total_amount'], 2) ?> (<?= $data['donation_count'] ?> donations)"></div>
                                <small style="color: var(--text-muted); font-size: 0.7rem;"><?= date('M', strtotime($data['month'] . '-01')) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Campaign Updates -->
        <div class="updates-section" style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); overflow: hidden; width: 70vw; margin: 0 auto;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                <h3 style="margin: 0;">Recent Updates from Your Supported Campaigns</h3>
            </div>
            
            <?php if(empty($campaign_updates)): ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                    <i class="fa-solid fa-bullhorn" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3>No Updates Yet</h3>
                    <p>Campaign updates from NGOs you've supported will appear here.</p>
                </div>
            <?php else: ?>
                <div class="updates-list">
                    <?php foreach($campaign_updates as $update): ?>
                        <div class="update-item" style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                            <div style="display: flex; gap: 1rem;">
                                <?php if($update['image_url']): ?>
                                    <img src="<?= h($update['image_url']) ?>" alt="Update Image" style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-md);">
                                <?php endif; ?>
                                <div style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                        <h4 style="margin: 0; font-size: 1.1rem; color: var(--primary);">
                                            <?= h($update['campaign_title']) ?>
                                        </h4>
                                        <span style="color: var(--text-muted); font-size: 0.875rem;">
                                            <?= date('M j, Y', strtotime($update['created_at'])) ?>
                                        </span>
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 0.75rem;">
                                        by <?= h($update['ngo_name']) ?>
                                    </div>
                                    <p style="margin: 0; line-height: 1.5; color: var(--text);">
                                        <?= h($update['message']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Impact Summary -->
        <div class="summary-section" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg); margin-top: 2rem; text-align: center; width: 70vw; margin-left: auto; margin-right: auto;">
            <h3 style="margin-top: 0; font-size: 1.5rem;">Your Giving Impact</h3>
            <p style="font-size: 1.1rem; opacity: 0.9; margin-bottom: 1.5rem;">
                Through your generous donations of <strong>$<?= number_format($impact_stats['total_donated'], 2) ?></strong>, 
                you've supported <strong><?= $impact_stats['campaigns_supported'] ?></strong> campaigns 
                across <strong><?= $impact_stats['ngos_supported'] ?></strong> different organizations.
            </p>
            <div style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap;">
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.25rem;"><?= $impact_stats['successful_donations'] ?></div>
                    <div style="opacity: 0.8;">Successful Donations</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.25rem;"><?= count($category_breakdown) ?></div>
                    <div style="opacity: 0.8;">Categories Supported</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>