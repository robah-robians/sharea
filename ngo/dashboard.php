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
$user_name = $_SESSION['user_name'] ?? 'NGO';

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
                <p>Your NGO account is currently under review by our administration team. You will be able to contribute to campaigns once verified.</p>
                <a href='/share_hope/actions/logout_action.php' class='btn btn-outline' style='border-color: white; color: white; margin-top: 1rem;'>Log Out</a>
            </div>
          </div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$ngo_id = $ngo['id'];

// Get this NGO's submitted campaign requests (only theirs)
$stmt = $pdo->prepare("
    SELECT cr.*, cat.name as category_name
    FROM campaign_requests cr
    LEFT JOIN categories cat ON cr.category_id = cat.id
    WHERE cr.ngo_id = ?
    ORDER BY cr.created_at DESC
");
$stmt->execute([$ngo_id]);
$my_requests = $stmt->fetchAll();

// Fetch latest announcement
$announcement_stmt = $pdo->prepare("SELECT * FROM announcements WHERE is_public = 1 ORDER BY created_at DESC LIMIT 1");
$announcement_stmt->execute();
$latest_announcement = $announcement_stmt->fetch();

?>

<div class="container" style="padding: 2rem 0;">
    <div class="admin-layout">
        <?php require_once __DIR__ . '/includes/ngo_nav.php'; ?>
        
        <div class="admin-main">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="margin:0 0 0.25rem 0;font-size:1.75rem;font-weight:800;color:var(--text-main);">NGO Dashboard</h1>
                    <p style="margin:0;color:var(--text-muted);font-size:0.95rem;">Welcome, <?= h($ngo['name'] ?? $user_name) ?></p>
                </div>
                <a href="/share_hope/ngo/submit_campaign.php" class="btn btn-primary" style="display:flex;align-items:center;gap:0.5rem;">
                    <i class="fa-solid fa-paper-plane"></i> Submit Campaign Request
                </a>
            </div>

            <!-- Platform Updates -->
            <?php if ($latest_announcement): ?>
            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; margin-bottom: 2.5rem;">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; display: flex; align-items: center; gap: 1rem;">
                    <i class="fa-solid fa-bell" style="font-size: 1.5rem;"></i>
                    <h3 style="margin: 0; font-size: 1.25rem;">Platform Updates</h3>
                </div>
                <div style="padding: 1.5rem; background: var(--surface);">
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: var(--text-main);"><?= h($latest_announcement['title']) ?></h4>
                    <p style="margin: 0 0 1rem 0; color: var(--text-muted); font-size: 0.95rem; line-height: 1.6;"><?= nl2br(h($latest_announcement['message'])) ?></p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.85rem; color: var(--text-muted);"><i class="fa-regular fa-calendar" style="margin-right: 0.3rem;"></i> <?= date('M j, Y', strtotime($latest_announcement['created_at'])) ?></span>
                        <?php if (!empty($latest_announcement['action_link'])): ?>
                            <a href="<?= h($latest_announcement['action_link']) ?>" class="btn btn-outline btn-sm" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Learn more</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
                <?php if (empty($assigned_initiatives)): ?>
                    <div style="padding: 4rem; text-align: center;">
                        <div style="width: 80px; height: 80px; background: var(--background); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2rem; color: var(--text-muted); border: 1px solid var(--border);">
                            <i class="fa-solid fa-radar"></i>
                        </div>
                        <h4 style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 700; color: var(--text-main);">No Synchronized Operations</h4>
                        <p style="color: var(--text-muted); margin-bottom: 0; font-size: 1rem; line-height: 1.6; max-width: 450px; margin-left: auto; margin-right: auto;">Your Node is currently in 'Standby' mode. The Central Administration will assign initiatives to your node based on field capability.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; background: var(--background);">
                                    <th style="padding: 1.25rem 1.5rem;">Operation Title</th>
                                    <th style="padding: 1.25rem 1.5rem;">Fiscal Target</th>
                                    <th style="padding: 1.25rem 1.5rem;">Current Acquisition</th>
                                    <th style="padding: 1.25rem 1.5rem;">Trajectory</th>
                                    <th style="padding: 1.25rem 1.5rem;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assigned_initiatives as $ini): ?>
                                    <tr style="border-bottom: 1px solid var(--border); transition: background 0.3s;" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                        <td style="padding: 1.25rem 1.5rem;">
                                            <div style="font-weight: 700; color: var(--text-main); font-size: 1rem;"><?= h($ini['title']) ?></div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);">Sync ID: #OP-<?= $ini['id'] ?></div>
                                        </td>
                                        <td style="padding: 1.25rem 1.5rem; font-weight: 600;">KSh <?= number_format($ini['goal_amount'], 2) ?></td>
                                        <td style="padding: 1.25rem 1.5rem;">
                                            <div style="font-weight: 700; color: var(--secondary);">KSh <?= number_format($ini['current_amount'], 2) ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= $ini['donor_count'] ?> Verified Supporters</div>
                                        </td>
                                        <td style="padding: 1.25rem 1.5rem;">
                                            <?php $progress = $ini['goal_amount'] > 0 ? min(100, round(($ini['current_amount'] / $ini['goal_amount']) * 100)) : 0; ?>
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="flex: 1; min-width: 100px; height: 6px; background: var(--border); border-radius: 999px; overflow: hidden;">
                                                    <div style="width: <?= $progress ?>%; height: 100%; background: var(--primary); border-radius: 999px;"></div>
                                                </div>
                                                <span style="font-weight: 800; font-size: 0.85rem;"><?= $progress ?>%</span>
                                            </div>
                                        </td>
                                        <td style="padding: 1.25rem 1.5rem;">
                                            <span style="padding: 0.35rem 0.85rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: <?= $ini['status'] === 'active' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(79, 70, 229, 0.1)' ?>; color: <?= $ini['status'] === 'active' ? 'var(--secondary)' : 'var(--primary)' ?>;">
                                                <?= strtoupper($ini['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- In-Kind Pledges Section -->
            <?php if (!empty($inkind_pledges)): ?>
            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 2px solid var(--accent); overflow: hidden; margin-top: 3rem;">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--accent); background: rgba(245, 158, 11, 0.05);">
                    <h3 style="margin: 0;"><i class="fa-solid fa-hand-holding-heart" style="color: var(--accent); margin-right: 0.5rem;"></i>Your In-Kind Pledges</h3>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--accent); color: var(--text-muted); font-size: 0.875rem; background: rgba(245, 158, 11, 0.05);">
                                <th style="padding: 1rem 1.5rem;">Date</th>
                                <th style="padding: 1rem 1.5rem;">Item</th>
                                <th style="padding: 1rem 1.5rem;">Quantity</th>
                                <th style="padding: 1rem 1.5rem;">Campaign</th>
                                <th style="padding: 1rem 1.5rem;">NGO</th>
                                <th style="padding: 1rem 1.5rem;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inkind_pledges as $pledge): ?>
                                <tr style="border-bottom: 1px solid var(--border); transition: background 0.3s;" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 1rem 1.5rem; white-space: nowrap;"><?= date('M j, Y', strtotime($pledge['created_at'])) ?></td>
                                    <td style="padding: 1rem 1.5rem; font-weight: 500;"><?= h($pledge['item_category']) ?></td>
                                    <td style="padding: 1rem 1.5rem;"><?= h($pledge['quantity']) ?></td>
                                    <td style="padding: 1rem 1.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;"><?= h($pledge['title']) ?></td>
                                    <td style="padding: 1rem 1.5rem;"><?= h($pledge['ngo_name']) ?></td>
                                    <td style="padding: 1rem 1.5rem;">
                                        <span style="padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: <?= $pledge['status'] === 'pledged' ? 'rgba(245, 158, 11, 0.1)' : ($pledge['status'] === 'received' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(79, 70, 229, 0.1)') ?>; color: <?= $pledge['status'] === 'pledged' ? 'var(--accent)' : ($pledge['status'] === 'received' ? 'var(--secondary)' : 'var(--primary)') ?>;">
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
                        <a href="/share_hope/ngo/pledges.php" class="btn btn-text" style="font-weight: 600; color: var(--accent);">View All Pledges <i class="fa-solid fa-arrow-right" style="margin-left: 0.5rem;"></i></a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ===== MY CAMPAIGN REQUESTS ===== -->
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);margin-top:2.5rem;">
                <div style="padding:1.25rem 1.5rem;border-bottom:1px solid var(--border);background:var(--background);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
                    <h3 style="margin:0;font-size:1.1rem;font-weight:700;display:flex;align-items:center;gap:0.5rem;">
                        <i class="fa-solid fa-inbox text-primary"></i> My Campaign Requests
                    </h3>
                    <a href="/share_hope/ngo/submit_campaign.php" class="btn btn-primary" style="padding:0.5rem 1rem;font-size:0.85rem;">
                        <i class="fa-solid fa-plus"></i> New Request
                    </a>
                </div>
                <?php if (empty($my_requests)): ?>
                    <div style="padding:2.5rem;text-align:center;color:var(--text-muted);">
                        <i class="fa-solid fa-paper-plane" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:0.75rem;"></i>
                        You haven't submitted any campaign requests yet.
                        <br><a href="/share_hope/ngo/submit_campaign.php" class="btn btn-primary" style="margin-top:1rem;display:inline-block;">Submit Your First Request</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="border-bottom:2px solid var(--border);background:var(--background);color:var(--text-muted);font-size:0.78rem;text-transform:uppercase;letter-spacing:0.5px;">
                                    <th style="padding:1rem 1.5rem;text-align:left;">Campaign</th>
                                    <th style="padding:1rem 1.5rem;text-align:left;">Category</th>
                                    <th style="padding:1rem 1.5rem;text-align:left;">Goal</th>
                                    <th style="padding:1rem 1.5rem;text-align:left;">Submitted</th>
                                    <th style="padding:1rem 1.5rem;text-align:left;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_requests as $req):
                                    $sc = match($req['status']) {
                                        'approved' => ['rgba(16,185,129,0.1)','var(--secondary)','✅ Approved — Admin will deploy'],
                                        'rejected' => ['rgba(239,68,68,0.1)','var(--danger)','❌ Rejected'],
                                        default    => ['rgba(245,158,11,0.1)','var(--accent)','⏳ Pending Admin Review'],
                                    };
                                ?>
                                <tr style="border-bottom:1px solid var(--border);transition:background 0.2s;" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding:1rem 1.5rem;">
                                        <div style="font-weight:700;color:var(--text-main);"><?= h($req['title']) ?></div>
                                        <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.2rem;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= h($req['description']) ?></div>
                                    </td>
                                    <td style="padding:1rem 1.5rem;"><span style="background:rgba(99,102,241,0.1);color:var(--primary);padding:0.2rem 0.65rem;border-radius:999px;font-size:0.75rem;font-weight:600;"><?= h($req['category_name'] ?? 'General') ?></span></td>
                                    <td style="padding:1rem 1.5rem;font-weight:700;color:var(--secondary);">KSh <?= number_format($req['goal_amount'], 0) ?></td>
                                    <td style="padding:1rem 1.5rem;font-size:0.82rem;color:var(--text-muted);"><?= date('M j, Y', strtotime($req['created_at'])) ?></td>
                                    <td style="padding:1rem 1.5rem;">
                                        <span style="background:<?= $sc[0] ?>;color:<?= $sc[1] ?>;padding:0.3rem 0.85rem;border-radius:999px;font-size:0.78rem;font-weight:700;"><?= $sc[2] ?></span>
                                        <?php if ($req['status'] === 'rejected' && $req['rejection_reason']): ?>
                                            <div style="font-size:0.75rem;color:var(--danger);margin-top:0.35rem;font-style:italic;"><?= h($req['rejection_reason']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>