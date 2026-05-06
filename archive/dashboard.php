<?php
session_start();
require_once __DIR__ . '/includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: /share_hope/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Total donated
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM donations WHERE donor_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$total_donated = $stmt->fetch()['total'] ?? 0;

// Donation history
$stmt = $pdo->prepare("SELECT d.*, c.title, n.name as ngo_name 
                       FROM donations d 
                       JOIN campaigns c ON d.campaign_id = c.id 
                       JOIN ngos ngo ON c.ngo_id = ngo.id
                       JOIN users n ON ngo.user_id = n.id
                       WHERE d.donor_id = ? ORDER BY d.created_at DESC");
$stmt->execute([$user_id]);
$donations = $stmt->fetchAll();
?>

<div class="container" style="padding: 4rem 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h1 style="font-size: 2rem; margin: 0;">Welcome, <?= h($_SESSION['user_name']) ?>!</h1>
        <div style="background: var(--surface); padding: 1rem 2rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
            <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Total Impact Delivered</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--secondary);"><i class="fa-solid fa-hand-holding-dollar"></i> $<?= number_format($total_donated, 2) ?></div>
        </div>
    </div>

    <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background);">
            <h3 style="margin: 0;">Your Giving History</h3>
        </div>
        
        <?php if (empty($donations)): ?>
            <div style="padding: 4rem; text-align: center;">
                <i class="fa-solid fa-heart-crack" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                <h4>No donations yet.</h4>
                <p class="text-muted" style="margin-bottom: 1.5rem;">Ready to make an impact?</p>
                <a href="<?= BASE_URL ?>/campaigns.php" class="btn btn-primary">Find a Campaign</a>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.875rem; background: var(--background);">
                            <th style="padding: 1rem 1.5rem;">Date</th>
                            <th style="padding: 1rem 1.5rem;">Campaign</th>
                            <th style="padding: 1rem 1.5rem;">NGO</th>
                            <th style="padding: 1rem 1.5rem;">Amount</th>
                            <th style="padding: 1rem 1.5rem;">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($donations as $don): ?>
                            <tr style="border-bottom: 1px solid var(--border); transition: background 0.3s;" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 1rem 1.5rem; white-space: nowrap;"><?= date('M j, Y', strtotime($don['created_at'])) ?></td>
                                <td style="padding: 1rem 1.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;"><?= h($don['title']) ?></td>
                                <td style="padding: 1rem 1.5rem;"><?= h($don['ngo_name']) ?></td>
                                <td style="padding: 1rem 1.5rem; font-weight: 600; color: var(--text-main);">$<?= number_format($don['amount'], 2) ?></td>
                                <td style="padding: 1rem 1.5rem;">
                                    <a href="<?= BASE_URL ?>/receipt.php?id=<?= $don['id'] ?>" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem;"><i class="fa-solid fa-file-invoice"></i> View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
