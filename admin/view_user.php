<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$id = $_GET['user_id'] ?? $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<div class='container' style='padding:4rem 0;'><h2>User Not Found.</h2></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch user's donations if donor
$donations = [];
$total_donated = 0;
if ($user['role'] === 'donor' || $user['role'] === 'user') {
    $stmt = $pdo->prepare("SELECT d.*, c.title, n.name as ngo_name, ngo.id as ngo_id 
                           FROM donations d 
                           JOIN campaigns c ON d.campaign_id = c.id 
                           JOIN ngos ngo ON c.ngo_id = ngo.id
                           JOIN users n ON ngo.user_id = n.id
                           WHERE d.donor_id = ? ORDER BY d.created_at DESC");
    $stmt->execute([$id]);
    $donations = $stmt->fetchAll();
    foreach ($donations as $don) {
        if ($don['status'] === 'completed') {
            $total_donated += $don['amount'];
        }
    }
}
?>

<div class="container" style="padding: 2.5rem 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="margin: 0;">User Details</h1>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline">Back to Dashboard</a>
    </div>

    <div
        style="background: var(--surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--shadow-sm); margin-bottom: 3rem;">
        <div style="display: flex; gap: 2rem; align-items: center; flex-wrap: wrap;">
            <div
                style="background: rgba(79, 70, 229, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: var(--primary);">
                <i class="fa-solid fa-user"></i>
            </div>
            <div>
                <h2 style="margin: 0; margin-bottom: 0.5rem;">
                    <?= h($user['name']) ?>
                </h2>
                <div style="color: var(--text-muted); display: flex; gap: 1.5rem; flex-wrap: wrap;">
                    <span><i class="fa-solid fa-envelope"></i>
                        <?= h($user['email']) ?>
                    </span>
                    <span><i class="fa-solid fa-phone"></i>
                        <?= h($user['phone'] ?? 'N/A') ?>
                    </span>
                    <span style="text-transform: uppercase;"><i class="fa-solid fa-tag"></i>
                        <?= h($user['role']) ?>
                    </span>
                    <span><i class="fa-solid fa-calendar"></i> Joined
                        <?= date('M j, Y', strtotime($user['created_at'])) ?>
                    </span>
                </div>
            </div>
            <?php if ($user['role'] === 'donor'): ?>
                <div
                    style="margin-left: auto; background: var(--background); padding: 1rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); text-align: center;">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Total Donated
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--secondary);">KSh
                        <?= number_format($total_donated, 2) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($user['role'] === 'donor' && !empty($donations)): ?>
        <h3 style="margin-bottom: 1.5rem;">Donation History</h3>
        <div
            style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow-x: auto;">
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
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td data-label="Date" style="padding: 1rem 1.5rem; white-space: nowrap;">
                                <?= date('M j, Y', strtotime($don['created_at'])) ?>
                            </td>
                            <td data-label="Campaign" style="padding: 1rem 1.5rem;">
                                <?= h($don['title']) ?>
                            </td>
                            <td data-label="NGO" style="padding: 1rem 1.5rem;">
                                <?= h($don['ngo_name']) ?>
                            </td>
                            <td data-label="Amount" style="padding: 1rem 1.5rem; font-weight: 600;">KSh
                                <?= number_format($don['amount'], 2) ?>
                            </td>
                            <td data-label="Receipt" style="padding: 1rem 1.5rem;">
                                <a href="<?= BASE_URL ?>/receipt.php?id=<?= $don['id'] ?>" class="btn btn-outline"
                                    style="padding: 0.25rem 0.75rem; font-size: 0.75rem;"><i class="fa-solid fa-file-pdf"></i>
                                    View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($user['role'] === 'donor'): ?>
        <div
            style="padding: 3rem; text-align: center; background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border);">
            <i class="fa-solid fa-box-open" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
            <p class="text-muted">No donations recorded for this user.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
