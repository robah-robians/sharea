<?php
session_start();

// Check authentication first
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header("Location: /share_hope/login.php");
    exit;
}

// Store user_id before including header (which might clear session)
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

require_once __DIR__ . '/../includes/header.php';

// Verify session is still valid after header inclusion
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] !== $user_id) {
    header("Location: /share_hope/login.php");
    exit;
}

// Get NGO details
$stmt = $pdo->prepare("SELECT * FROM ngos WHERE user_id = ?");
$stmt->execute([$user_id]);
$ngo = $stmt->fetch();

if (!$ngo || !$ngo['is_verified']) {
    echo "<div class='container' style='padding:4rem 0;'>
            <div style='background: var(--accent); color: white; padding: 2rem; border-radius: var(--radius-md); text-align: center;'>
                <i class='fa-solid fa-hourglass-half' style='font-size: 3rem; margin-bottom: 1rem;'></i>
                <h2>Account Pending Verification</h2>
                <p>Your NGO account is currently under review by our administration team.</p>
                <a href='/actions/logout_action.php' class='btn btn-outline' style='border-color: white; color: white; margin-top: 1rem;'>Log Out</a>
            </div>
          </div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$ngo_id = $ngo['id'];

// Get donation statistics - donations made BY this NGO (as a donor)
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_donations,
        COUNT(CASE WHEN d.status = 'completed' THEN d.id END) as completed_donations,
        COUNT(CASE WHEN d.status = 'pending' THEN d.id END) as pending_donations,
        COUNT(CASE WHEN d.status = 'failed' THEN d.id END) as failed_donations,
        COALESCE(SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END), 0) as total_amount,
        COUNT(DISTINCT d.campaign_id) as campaigns_donated_to
    FROM donations d
    WHERE d.donor_id = ?
");
$stmt->execute([$user_id]);
$donation_stats = $stmt->fetch();

// Get all donations made BY this NGO (as a donor) with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT 
        d.*,
        c.title as campaign_title,
        'Share Hope Admin' as ngo_owner_name
    FROM donations d
    JOIN campaigns c ON d.campaign_id = c.id
    WHERE d.donor_id = ?
    ORDER BY d.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$user_id, $per_page, $offset]);
$donations = $stmt->fetchAll();

// Get total count for pagination
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM donations d
    WHERE d.donor_id = ?
");
$stmt->execute([$user_id]);
$total_count = $stmt->fetch()['total'];
$total_pages = ceil($total_count / $per_page);

?>

<div style="padding: 4rem 0; max-width: none; margin: 0; width: 100%;">
    <div class="admin-layout" style="display: flex; gap: 0; align-items: flex-start; margin: 0; padding: 0;">
        <div style="padding-left: 0; margin-left: 0;">
            <?php require_once __DIR__ . '/includes/ngo_nav.php'; ?>
        </div>
        
        <div class="admin-main" style="flex: 1; min-width: 0; padding-left: 2.5rem; padding-right: 1.5rem; max-width: 1400px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
                <div>
                    <h1 style="font-size: 2.5rem; margin: 0; background: linear-gradient(135deg, var(--primary), var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 800;">My Donations</h1>
                    <p style="color: var(--text-muted); margin: 0.5rem 0 0 0; font-size: 1.1rem; font-weight: 500;">Track donations you've made to other campaigns</p>
                </div>
            </div>

            <!-- Donation Statistics -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); text-align: center;">
                    <div style="width: 50px; height: 50px; background: rgba(79, 70, 229, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin: 0 auto 1rem;">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;"><?= intval($donation_stats['total_donations']) ?></div>
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Total Donations</div>
                </div>

                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); text-align: center;">
                    <div style="width: 50px; height: 50px; background: rgba(16, 185, 129, 0.1); color: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin: 0 auto 1rem;">
                        <i class="fa-solid fa-check-circle"></i>
                    </div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">$<?= number_format($donation_stats['total_amount'], 2) ?></div>
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Total Raised</div>
                </div>

                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); text-align: center;">
                    <div style="width: 50px; height: 50px; background: rgba(99, 102, 241, 0.1); color: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin: 0 auto 1rem;">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;"><?= intval($donation_stats['campaigns_donated_to']) ?></div>
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Campaigns Supported</div>
                </div>
            </div>

            <!-- Donations Table -->
            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border); overflow: hidden;">
                <div style="padding: 2rem; border-bottom: 1px solid var(--border); background: linear-gradient(135deg, var(--primary), var(--primary-hover)); color: white;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="background: rgba(255,255,255,0.2); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                            <i class="fa-solid fa-list"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0; font-size: 1.4rem; font-weight: 700;">My Donation History</h3>
                            <p style="margin: 0.25rem 0 0 0; opacity: 0.9; font-size: 0.95rem;">Donations you've made to support other campaigns</p>
                        </div>
                    </div>
                </div>

                <?php if (empty($donations)): ?>
                    <div style="padding: 4rem; text-align: center;">
                        <i class="fa-solid fa-heart" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                        <h4>No donations made yet</h4>
                        <p style="color: var(--text-muted);">Your donations to other campaigns will appear here.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead style="background: var(--background); border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.875rem;">
                                <tr>
                                    <th style="padding: 1rem 1.5rem;">Amount</th>
                                    <th style="padding: 1rem 1.5rem;">Campaign Supported</th>
                                    <th style="padding: 1rem 1.5rem;">NGO Beneficiary</th>
                                    <th style="padding: 1rem 1.5rem;">Status</th>
                                    <th style="padding: 1rem 1.5rem;">Date</th>
                                    <th style="padding: 1rem 1.5rem;">Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($donations as $donation): ?>
                                    <tr style="border-bottom: 1px solid var(--border); transition: background 0.3s;" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                        <td style="padding: 1rem 1.5rem; font-weight: 700; font-size: 1.1rem; color: var(--text-main);">
                                            $<?= number_format($donation['amount'], 2) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; max-width: 200px;">
                                            <div style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= h($donation['campaign_title']) ?>">
                                                <?= h($donation['campaign_title']) ?>
                                            </div>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <div style="font-weight: 600; color: var(--text-main);">
                                                <?= h($donation['ngo_owner_name']) ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                                                NGO Organization
                                            </div>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <span style="padding: 0.4rem 0.8rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; background: <?= $donation['status'] === 'completed' ? 'var(--success)' : ($donation['status'] === 'pending' ? 'var(--warning)' : 'var(--danger)') ?>; color: white;">
                                                <?= ucfirst($donation['status']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; color: var(--text-muted);">
                                            <div><?= date('M j, Y', strtotime($donation['created_at'])) ?></div>
                                            <div style="font-size: 0.8rem;"><?= date('g:i A', strtotime($donation['created_at'])) ?></div>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; font-family: monospace; font-size: 0.8rem; color: var(--text-muted);">
                                            <?= h($donation['transaction_reference'] ?? 'N/A') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div style="padding: 1.5rem; border-top: 1px solid var(--border); display: flex; justify-content: between; align-items: center;">
                            <div style="color: var(--text-muted); font-size: 0.9rem;">
                                Showing <?= ($offset + 1) ?> to <?= min($offset + $per_page, $total_count) ?> of <?= $total_count ?> donations
                            </div>
                            <div style="display: flex; gap: 0.5rem; margin-left: auto;">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                                        <i class="fa-solid fa-chevron-left"></i> Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <a href="?page=<?= $i ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>" style="padding: 0.5rem 0.75rem; font-size: 0.8rem;">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?>" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.8rem;">
                                        Next <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>