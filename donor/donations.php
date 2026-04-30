<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: /share_hope/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';


$user_id = $_SESSION['user_id'];

// Get donation statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_donations,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_donated,
        COUNT(DISTINCT campaign_id) as campaigns_supported
    FROM donations 
    WHERE donor_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Get donations with campaign details
$stmt = $pdo->prepare("
    SELECT d.*, c.title as campaign_title, c.image_url, 'Share Hope Admin' as ngo_name, cat.name as category_name
    FROM donations d
    JOIN campaigns c ON d.campaign_id = c.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    WHERE d.donor_id = ?
    ORDER BY d.created_at DESC
");
$stmt->execute([$user_id]);
$donations = $stmt->fetchAll();

// Get monthly donation totals for chart
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total
    FROM donations 
    WHERE donor_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");
$stmt->execute([$user_id]);
$monthly_data = $stmt->fetchAll();
?>

<div class="admin-layout">
    <?php include __DIR__ . '/includes/donor_nav.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header" style="text-align: center;">
            <h1 style="font-size: 2.5rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;"><i class="fa-solid fa-heart" style="color: var(--primary);"></i> My Donations</h1>
            <div style="color: var(--text-muted); font-size: 1rem;">
                <?= $stats['total_donations'] ?> total donations
            </div>
        </div>

        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                <div style="display: flex; align-items: center; justify-content: center; text-align: center;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700; display: inline; margin-right: 0.5rem;">$<?= number_format($stats['total_donated'], 2) ?></h3>
                        <p style="margin: 0; opacity: 0.9; display: inline;">Total Donated</p>
                    </div>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                <div style="display: flex; align-items: center; justify-content: center; text-align: center;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700; display: inline; margin-right: 0.5rem;"><?= $stats['campaigns_supported'] ?></h3>
                        <p style="margin: 0; opacity: 0.9; display: inline;">Campaigns Supported</p>
                    </div>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                <div style="display: flex; align-items: center; justify-content: center; text-align: center;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700; display: inline; margin-right: 0.5rem;"><?= $stats['total_donations'] ?></h3>
                        <p style="margin: 0; opacity: 0.9; display: inline;">Total Donations</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Donations List -->
        <div class="donations-section" style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); overflow: hidden; width: 70vw; margin: 0 auto;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); text-align: center;">
                <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--text-main);">Donation History</h3>
            </div>
            
            <?php if(empty($donations)): ?>
                <div style="text-align: center; padding: 4rem 2rem;">
                    <i class="fa-solid fa-heart" style="font-size: 3.5rem; color: var(--primary); margin-bottom: 1.5rem; opacity: 0.7;"></i>
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1.5rem; font-weight: 700; color: var(--text-main);">Donation History</h4>
                    <h5 style="margin: 0 0 1.5rem 0; font-size: 1.2rem; font-weight: 600; color: var(--text-muted);">No Donations Yet</h5>
                    <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 1rem; line-height: 1.6; max-width: 400px; margin-left: auto; margin-right: auto;">Start making a difference by supporting campaigns you care about.</p>
                    <a href="/share_hope/campaigns.php" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 1rem; font-weight: 600;">
                        <i class="fa-solid fa-search"></i> Find Campaigns
                    </a>
                </div>
            <?php else: ?>
                <div class="donations-list">
                    <?php foreach($donations as $donation): ?>
                        <div class="donation-item" style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 1rem;">
                            <?php if($donation['image_url']): ?>
                                <img src="<?= h($donation['image_url']) ?>" alt="Campaign" style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-md);">
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; background: var(--border); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-image" style="color: var(--text-muted);"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem;">
                                    <a href="/share_hope/campaigns.php?id=<?= $donation['campaign_id'] ?>" style="color: var(--text); text-decoration: none;">
                                        <?= h($donation['campaign_title']) ?>
                                    </a>
                                </h4>
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                                    <span style="color: var(--text-muted); font-size: 0.875rem;">
                                        by <?= h($donation['ngo_name']) ?>
                                    </span>
                                    <span class="badge" style="background: var(--primary); color: white; padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.75rem;">
                                        <?= h($donation['category_name']) ?>
                                    </span>
                                </div>
                                <div style="color: var(--text-muted); font-size: 0.875rem;">
                                    <?= date('M j, Y \a\t g:i A', strtotime($donation['created_at'])) ?>
                                    <?php if($donation['message']): ?>
                                        • "<?= h(substr($donation['message'], 0, 50)) ?><?= strlen($donation['message']) > 50 ? '...' : '' ?>"
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="text-align: right;">
                                <div style="font-size: 1.25rem; font-weight: 600; color: var(--success); margin-bottom: 0.25rem;">
                                    $<?= number_format($donation['amount'], 2) ?>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span class="badge" style="background: var(--<?= $donation['status'] === 'completed' ? 'success' : ($donation['status'] === 'pending' ? 'warning' : 'danger') ?>); color: white; padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.75rem;">
                                        <?= ucfirst($donation['status']) ?>
                                    </span>
                                    <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">
                                        <?= h($donation['payment_method']) ?>
                                    </span>
                                </div>
                                <?php if($donation['status'] === 'completed'): ?>
                                    <a href="/share_hope/donation_receipt.php?id=<?= $donation['id'] ?>" class="btn btn-outline" style="margin-top: 0.5rem; font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                        <i class="fa-solid fa-download"></i> Receipt
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>