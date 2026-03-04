<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /share_hope/login.php");
    exit;
}

$role = $_SESSION['user_role'] ?? '';
if ($role !== 'donor') {
    $redirect = $role ? "/share_hope/{$role}/dashboard.php" : "/share_hope/login.php";
    header("Location: {$redirect}");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];

// Total donated
$stmt = $pdo->prepare("SELECT SUM(amount) as total FROM donations WHERE donor_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$total_donated = $stmt->fetch()['total'] ?? 0;

// Donation history
$stmt = $pdo->prepare("SELECT d.*, c.title, n.name as ngo_name, ngo.id as ngo_id 
                       FROM donations d 
                       JOIN campaigns c ON d.campaign_id = c.id 
                       JOIN ngos ngo ON c.ngo_id = ngo.id
                       JOIN users n ON ngo.user_id = n.id
                       WHERE d.donor_id = ? ORDER BY d.created_at DESC");
$stmt->execute([$user_id]);
$donations = $stmt->fetchAll();

// In-Kind Pledges
$stmt = $pdo->prepare("SELECT i.*, c.title, n.name as ngo_name, ngo.id as ngo_id 
                       FROM inkind_donations i 
                       JOIN campaigns c ON i.campaign_id = c.id 
                       JOIN ngos ngo ON c.ngo_id = ngo.id
                       JOIN users n ON ngo.user_id = n.id
                       WHERE i.donor_id = ? ORDER BY i.created_at DESC");
$stmt->execute([$user_id]);
$inkind_pledges = $stmt->fetchAll();
?>

<div class="container" style="padding: 4rem 0;">
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h1 style="font-size: 2rem; margin: 0;">Welcome, <?= h($_SESSION['user_name']) ?>!</h1>
        <div
            style="background: var(--surface); padding: 1rem 2rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
            <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Total Impact
                Delivered</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--secondary);"><i
                    class="fa-solid fa-hand-holding-dollar"></i> KSh <?= number_format($total_donated, 2) ?></div>
        </div>
    </div>

    <div
        style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background);">
            <h3 style="margin: 0;">Your Giving History</h3>
        </div>

        <?php if (empty($donations)): ?>
            <div style="padding: 4rem; text-align: center;">
                <i class="fa-solid fa-heart-crack" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                <h4>No donations yet.</h4>
                <p class="text-muted" style="margin-bottom: 1.5rem;">Ready to make an impact?</p>
                <a href="/share_hope/campaigns.php" class="btn btn-primary">Find a Campaign</a>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="responsive-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr
                            style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.875rem; background: var(--background);">
                            <th style="padding: 1rem 1.5rem;">Date</th>
                            <th style="padding: 1rem 1.5rem;">Campaign</th>
                            <th style="padding: 1rem 1.5rem;">NGO</th>
                            <th style="padding: 1rem 1.5rem;">Amount</th>
                            <th style="padding: 1rem 1.5rem;">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donations as $don): ?>
                            <tr style="border-bottom: 1px solid var(--border); transition: background 0.3s;"
                                onmouseover="this.style.background='var(--background)'"
                                onmouseout="this.style.background='transparent'">
                                <td data-label="Date" style="padding: 1rem 1.5rem; white-space: nowrap;">
                                    <?= date('M j, Y', strtotime($don['created_at'])) ?>
                                </td>
                                <td data-label="Campaign"
                                    style="padding: 1rem 1.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;">
                                    <?= h($don['title']) ?>
                                </td>
                                <td data-label="NGO" style="padding: 1rem 1.5rem;">
                                    <a href="/share_hope/ngo_profile.php?id=<?= $don['ngo_id'] ?>"
                                        style="color: var(--primary); font-weight: 500;">
                                        <?= h($don['ngo_name']) ?>
                                    </a>
                                </td>
                                <td data-label="Amount"
                                    style="padding: 1rem 1.5rem; font-weight: 600; color: var(--text-main);">KSh
                                    <?= number_format($don['amount'], 2) ?>
                                </td>
                                <td data-label="Receipt" style="padding: 1rem 1.5rem;">
                                    <a href="/share_hope/receipt.php?id=<?= $don['id'] ?>" class="btn btn-outline"
                                        style="padding: 0.5rem 1rem; font-size: 0.875rem;"><i
                                            class="fa-solid fa-file-invoice"></i> View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- In-Kind Pledges Section -->
    <div
        style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; margin-top: 3rem;">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background);">
            <h3 style="margin: 0;">Your Item Pledges</h3>
        </div>

        <?php if (empty($inkind_pledges)): ?>
            <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-box-open" style="font-size: 2.5rem; color: var(--border); margin-bottom: 1rem;"></i>
                <p>No item pledges yet.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr
                            style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.875rem; background: var(--background);">
                            <th style="padding: 1rem 1.5rem;">Date</th>
                            <th style="padding: 1rem 1.5rem;">Campaign & NGO</th>
                            <th style="padding: 1rem 1.5rem;">Item Category</th>
                            <th style="padding: 1rem 1.5rem;">Quantity & Details</th>
                            <th style="padding: 1rem 1.5rem;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inkind_pledges as $pledge): ?>
                            <tr style="border-bottom: 1px solid var(--border); font-size: 0.875rem;">
                                <td style="padding: 1rem 1.5rem; white-space: nowrap;">
                                    <?= date('M j, Y', strtotime($pledge['created_at'])) ?>
                                </td>
                                <td style="padding: 1rem 1.5rem;">
                                    <div style="font-weight: 600; color: var(--text-main);"><?= h($pledge['title']) ?></div>
                                    <div style="color: var(--text-muted); font-size: 0.75rem;"><?= h($pledge['ngo_name']) ?>
                                    </div>
                                </td>
                                <td style="padding: 1rem 1.5rem;"><span
                                        style="background: var(--background); padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid var(--border);"><?= h($pledge['item_category']) ?></span>
                                </td>
                                <td style="padding: 1rem 1.5rem;">
                                    <strong><?= h($pledge['quantity']) ?></strong><br>
                                    <span style="color: var(--text-muted);"><?= h($pledge['item_description']) ?></span>
                                </td>
                                <td style="padding: 1rem 1.5rem;">
                                    <span
                                        style="text-transform: uppercase; font-size: 0.7rem; font-weight: 700; padding: 0.25rem 0.5rem; border-radius: 4px; 
                                        <?= $pledge['status'] === 'pledged' ? 'background: rgba(245, 158, 11, 0.1); color: var(--accent);' : 'background: rgba(16, 185, 129, 0.1); color: var(--secondary);' ?>">
                                        <?= h($pledge['status']) ?>
                                    </span>
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