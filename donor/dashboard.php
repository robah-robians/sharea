<?php
session_start();

// Check authentication first
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Store user_id before including header (which might clear session)
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'] ?? 'Donor';

require_once __DIR__ . '/../includes/header.php';

// Verify session is still valid after header inclusion
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] !== $user_id) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// Total donated
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM donations WHERE donor_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$total_donated = $stmt->fetch()['total'] ?? 0;

// Donation history
$stmt = $pdo->prepare("SELECT d.*, c.title, 'Share Hope Admin' as ngo_name 
                       FROM donations d 
                       JOIN campaigns c ON d.campaign_id = c.id 
                       WHERE d.donor_id = ? ORDER BY d.created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$donations = $stmt->fetchAll();

// In-kind pledges
$stmt = $pdo->prepare("SELECT i.*, c.title, 'Share Hope Admin' as ngo_name 
                       FROM inkind_donations i 
                       JOIN campaigns c ON i.campaign_id = c.id 
                       WHERE i.donor_id = ? ORDER BY i.created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$inkind_pledges = $stmt->fetchAll();

// Fetch latest announcement
$announcement_stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 1");
$latest_announcement = $announcement_stmt->fetch();

// Create a test announcement if none exists
if (!$latest_announcement) {
    $latest_announcement = [
        'title' => 'System Maintenance Update',
        'message' => 'We will be performing scheduled maintenance on our servers this weekend. Some features may be temporarily unavailable.',
        'created_at' => date('Y-m-d H:i:s'),
        'action_link' => BASE_URL . '/campaigns.php'
    ];
}
?>

<div class="container" style="padding: 2rem 0;">
    <div class="admin-layout">
        <?php require_once __DIR__ . '/includes/donor_nav.php'; ?>
        
        <div class="admin-main">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 2.5rem; margin: 0; background: linear-gradient(135deg, var(--primary), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 800;">Donor Dashboard</h1>
                    <p style="color: var(--text-muted); margin: 0.5rem 0 0 0; font-size: 1.1rem; font-weight: 500;">Track your impact and discover new opportunities</p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 40px; font-weight: 600; color: #10b981; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <span>Welcome</span>
                        <?= h($user_name) ?>
                    </div>
                </div>
            </div>

            <?php if ($latest_announcement): ?>
                <div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(16, 185, 129, 0.05)); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 2rem; position: relative;">
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--surface); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
                            <i class="fa-solid fa-bullhorn text-secondary" style="font-size: 1.25rem;"></i>
                        </div>
                        <div>
                            <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.25rem;">
                                <h4 style="margin: 0; color: var(--text-main); font-weight: 700; font-size: 1.1rem;">Important System Update: <?= h($latest_announcement['title']) ?></h4>
                                <span style="font-size: 0.75rem; color: var(--text-muted); background: var(--surface); padding: 0.1rem 0.5rem; border-radius: 12px; border: 1px solid var(--border);"><?= date('M j, Y', strtotime($latest_announcement['created_at'])) ?></span>
                            </div>
                            <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem; line-height: 1.5;">
                                <?= h($latest_announcement['message']) ?>
                            </p>
                            <?php if (!empty($latest_announcement['action_link'])): ?>
                                <a href="<?= h($latest_announcement['action_link']) ?>" class="btn btn-primary" style="margin-top: 1rem; padding: 0.5rem 1rem; font-size: 0.875rem;">View Details</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

    <div
        style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background); text-align: center;">
            <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--text-main);">My Donations</h3>
        </div>

        <?php if (empty($donations)): ?>
            <div style="padding: 4rem; text-align: center;">
                <i class="fa-solid fa-heart" style="font-size: 3rem; color: var(--primary); margin-bottom: 1.5rem; opacity: 0.7;"></i>
                <h4 style="margin: 0 0 1rem 0; font-size: 1.5rem; font-weight: 700; color: var(--text-main);">Donation History</h4>
                <h5 style="margin: 0 0 1.5rem 0; font-size: 1.1rem; font-weight: 600; color: var(--text-muted);">No Donations Yet</h5>
                <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 1rem; line-height: 1.6; max-width: 400px; margin-left: auto; margin-right: auto;">Start making a difference by supporting campaigns you care about.</p>
                <a href="<?= BASE_URL ?>/campaigns.php" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 1rem; font-weight: 600;">Find Campaigns</a>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr
                            style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.875rem; background: var(--background);">
                            <th style="padding: 1rem 1.5rem;">Date</th>
                            <th style="padding: 1rem 1.5rem;">Initiative</th>
                            <th style="padding: 1rem 1.5rem;">Partner NGO</th>
                            <th style="padding: 1rem 1.5rem;">Amount</th>
                            <th style="padding: 1rem 1.5rem;">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donations as $don): ?>
                            <tr style="border-bottom: 1px solid var(--border); transition: background 0.3s;"
                                onmouseover="this.style.background='var(--background)'"
                                onmouseout="this.style.background='transparent'">
                                <td style="padding: 1rem 1.5rem; white-space: nowrap;">
                                    <?= date('M j, Y', strtotime($don['created_at'])) ?></td>
                                <td
                                    style="padding: 1rem 1.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;">
                                    <?= h($don['title']) ?></td>
                                <td style="padding: 1rem 1.5rem;"><?= h($don['ngo_name']) ?></td>
                                <td style="padding: 1rem 1.5rem; font-weight: 600; color: var(--text-main);">
                                    $<?= number_format($don['amount'], 2) ?></td>
                                <td style="padding: 1rem 1.5rem;">
                                    <a href="<?= BASE_URL ?>/receipt.php?id=<?= $don['id'] ?>" class="btn btn-outline"
                                        style="padding: 0.5rem 1rem; font-size: 0.875rem;"><i
                                            class="fa-solid fa-file-invoice"></i> View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($donations) >= 5): ?>
                <div style="padding: 1rem; border-top: 1px solid var(--border); text-align: center; background: var(--background);">
                    <a href="<?= BASE_URL ?>/donor/donations.php" class="btn btn-text" style="font-weight: 600; color: var(--primary);">View All Donations <i class="fa-solid fa-arrow-right" style="margin-left: 0.5rem;"></i></a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- In-Kind Pledges Section -->
    <?php if (!empty($inkind_pledges)): ?>
    <div
        style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 2px solid var(--accent); overflow: hidden; margin-top: 3rem;">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--accent); background: rgba(245, 158, 11, 0.05);">
            <h3 style="margin: 0;"><i class="fa-solid fa-hand-holding-heart" style="color: var(--accent); margin-right: 0.5rem;"></i>Your In-Kind Pledges</h3>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr
                        style="border-bottom: 1px solid var(--accent); color: var(--text-muted); font-size: 0.875rem; background: rgba(245, 158, 11, 0.05);">
                        <th style="padding: 1rem 1.5rem;">Date</th>
                        <th style="padding: 1rem 1.5rem;">Item</th>
                        <th style="padding: 1rem 1.5rem;">Quantity</th>
                        <th style="padding: 1rem 1.5rem;">Initiative</th>
                        <th style="padding: 1rem 1.5rem;">Partner NGO</th>
                        <th style="padding: 1rem 1.5rem;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inkind_pledges as $pledge): ?>
                        <tr style="border-bottom: 1px solid var(--border); transition: background 0.3s;"
                            onmouseover="this.style.background='var(--background)'"
                            onmouseout="this.style.background='transparent'">
                            <td style="padding: 1rem 1.5rem; white-space: nowrap;">
                                <?= date('M j, Y', strtotime($pledge['created_at'])) ?></td>
                            <td style="padding: 1rem 1.5rem; font-weight: 500;">
                                <?= h($pledge['item_category']) ?></td>
                            <td style="padding: 1rem 1.5rem;">
                                <?= h($pledge['quantity']) ?></td>
                            <td
                                style="padding: 1rem 1.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;">
                                <?= h($pledge['title']) ?></td>
                            <td style="padding: 1rem 1.5rem;"><?= h($pledge['ngo_name']) ?></td>
                            <td style="padding: 1rem 1.5rem;">
                                <span
                                    style="padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: <?= 
                                        $pledge['status'] === 'pledged' ? 'rgba(245, 158, 11, 0.1)' : 
                                        ($pledge['status'] === 'received' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(79, 70, 229, 0.1)') 
                                    ?>; color: <?= 
                                        $pledge['status'] === 'pledged' ? 'var(--accent)' : 
                                        ($pledge['status'] === 'received' ? 'var(--secondary)' : 'var(--primary)') 
                                    ?>;">
                                    <?= ucfirst($pledge['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($inkind_pledges) >= 5): ?>
            <div style="padding: 1rem; border-top: 1px solid var(--accent); text-align: center; background: rgba(245, 158, 11, 0.05);">
                <a href="<?= BASE_URL ?>/donor/pledges.php" class="btn btn-text" style="font-weight: 600; color: var(--accent);">View All Pledges <i class="fa-solid fa-arrow-right" style="margin-left: 0.5rem;"></i></a>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>