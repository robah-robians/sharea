<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: /share_hope/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';


$user_id = $_SESSION['user_id'];

// Get in-kind donations
$stmt = $pdo->prepare("
    SELECT ik.*, c.title as campaign_title, c.image_url, 'Share Hope Admin' as ngo_name
    FROM inkind_donations ik
    JOIN campaigns c ON ik.campaign_id = c.id
    WHERE ik.donor_id = ?
    ORDER BY ik.created_at DESC
");
$stmt->execute([$user_id]);
$pledges = $stmt->fetchAll();

// Get statistics
$stats = [
    'total_pledges' => count($pledges),
    'received_pledges' => count(array_filter($pledges, fn($p) => $p['status'] === 'received')),
    'distributed_pledges' => count(array_filter($pledges, fn($p) => $p['status'] === 'distributed')),
    'pending_pledges' => count(array_filter($pledges, fn($p) => $p['status'] === 'pledged'))
];
?>

<div class="admin-layout">
    <?php include __DIR__ . '/includes/donor_nav.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header" style="position: relative;">
            <h1><i class="fa-solid fa-handshake"></i> In-Kind Pledges</h1>
            <div style="position: absolute; top: 0; right: 0; background: var(--primary); color: white; padding: 0.5rem 1rem; border-radius: var(--radius-md); font-weight: 600;">
                <?= $stats['total_pledges'] ?> total pledges
            </div>
        </div>

        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: center; text-align: center;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700; display: inline; margin-right: 0.5rem;"><?= $stats['total_pledges'] ?></h3>
                        <p style="margin: 0; opacity: 0.9; display: inline;">Total Pledges</p>
                    </div>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: center; text-align: center;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700; display: inline; margin-right: 0.5rem;"><?= $stats['pending_pledges'] ?></h3>
                        <p style="margin: 0; opacity: 0.9; display: inline;">Pending</p>
                    </div>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: center; text-align: center;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700; display: inline; margin-right: 0.5rem;"><?= $stats['received_pledges'] ?></h3>
                        <p style="margin: 0; opacity: 0.9; display: inline;">Received</p>
                    </div>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: center; text-align: center;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700; display: inline; margin-right: 0.5rem;"><?= $stats['distributed_pledges'] ?></h3>
                        <p style="margin: 0; opacity: 0.9; display: inline;">Distributed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pledges List -->
        <div class="pledges-section" style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); overflow: hidden; width: 70vw; margin: 0 auto;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                <h3 style="margin: 0;">Pledge History</h3>
            </div>
            
            <?php if(empty($pledges)): ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                    <i class="fa-solid fa-handshake" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3>No In-Kind Pledges Yet</h3>
                    <p>Start making in-kind donations to support campaigns with physical items.</p>
                    <a href="/share_hope/campaigns.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fa-solid fa-search"></i> Find Campaigns
                    </a>
                </div>
            <?php else: ?>
                <div class="pledges-list">
                    <?php foreach($pledges as $pledge): ?>
                        <div class="pledge-item" style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 1rem;">
                            <?php if($pledge['image_url']): ?>
                                <img src="<?= h($pledge['image_url']) ?>" alt="Campaign" style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-md);">
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; background: var(--border); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-image" style="color: var(--text-muted);"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem;">
                                    <a href="/share_hope/campaigns.php?id=<?= $pledge['campaign_id'] ?>" style="color: var(--text); text-decoration: none;">
                                        <?= h($pledge['campaign_title']) ?>
                                    </a>
                                </h4>
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                                    <span style="color: var(--text-muted); font-size: 0.875rem;">
                                        by <?= h($pledge['ngo_name']) ?>
                                    </span>
                                    <span class="badge" style="background: var(--primary); color: white; padding: 0.25rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.75rem;">
                                        <?= h($pledge['item_category']) ?>
                                    </span>
                                </div>
                                <div style="margin-bottom: 0.5rem;">
                                    <strong>Item:</strong> <?= h($pledge['item_description']) ?>
                                    <span style="color: var(--text-muted); margin-left: 1rem;">
                                        <strong>Quantity:</strong> <?= h($pledge['quantity']) ?>
                                    </span>
                                </div>
                                <div style="color: var(--text-muted); font-size: 0.875rem;">
                                    Pledged on <?= date('M j, Y \a\t g:i A', strtotime($pledge['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div style="text-align: right;">
                                <span class="badge" style="background: var(--<?= $pledge['status'] === 'distributed' ? 'success' : ($pledge['status'] === 'received' ? 'info' : 'warning') ?>); color: white; padding: 0.5rem 1rem; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 600;">
                                    <?php
                                    $status_text = match($pledge['status']) {
                                        'pledged' => 'Pending',
                                        'received' => 'Received',
                                        'distributed' => 'Distributed',
                                        default => ucfirst($pledge['status'])
                                    };
                                    echo $status_text;
                                    ?>
                                </span>
                                <div style="margin-top: 0.5rem;">
                                    <?php if($pledge['status'] === 'pledged'): ?>
                                        <small style="color: var(--text-muted);">Awaiting pickup/delivery</small>
                                    <?php elseif($pledge['status'] === 'received'): ?>
                                        <small style="color: var(--info);">Received by NGO</small>
                                    <?php else: ?>
                                        <small style="color: var(--success);">Impact delivered!</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- How to Make In-Kind Donations -->
        <div class="info-section" style="background: var(--surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border); margin-top: 2rem; width: 70vw; margin-left: auto; margin-right: auto;">
            <h3 style="margin-top: 0;">How to Make In-Kind Donations</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div style="text-align: center; padding: 1rem;">
                    <div style="width: 60px; height: 60px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem;">
                        <i class="fa-solid fa-search"></i>
                    </div>
                    <h4>1. Find a Campaign</h4>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Browse campaigns and look for those accepting in-kind donations.</p>
                </div>
                <div style="text-align: center; padding: 1rem;">
                    <div style="width: 60px; height: 60px; background: var(--secondary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem;">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <h4>2. Make a Pledge</h4>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Specify what items you want to donate and their quantities.</p>
                </div>
                <div style="text-align: center; padding: 1rem;">
                    <div style="width: 60px; height: 60px; background: var(--success); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 1.5rem;">
                        <i class="fa-solid fa-truck"></i>
                    </div>
                    <h4>3. Coordinate Delivery</h4>
                    <p style="color: var(--text-muted); font-size: 0.875rem;">Work with the NGO to arrange pickup or delivery of your items.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>