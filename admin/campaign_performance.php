<?php
session_start();
require_once __DIR__ . '/../includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: /share_hope/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';

$filter_status   = $_GET['status']   ?? '';
$filter_category = $_GET['category'] ?? '';
$sort_by         = $_GET['sort']     ?? 'raised_desc';

$where_clauses = [];
$params = [];
if ($filter_status)   { $where_clauses[] = "c.status = ?";      $params[] = $filter_status; }
if ($filter_category) { $where_clauses[] = "c.category_id = ?"; $params[] = $filter_category; }
$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$order_sql = 'ORDER BY c.created_at DESC';
switch ($sort_by) {
    case 'progress_desc': $order_sql = 'ORDER BY CAST(ROUND((c.current_amount / c.goal_amount) * 100) AS SIGNED) DESC'; break;
    case 'progress_asc':  $order_sql = 'ORDER BY CAST(ROUND((c.current_amount / c.goal_amount) * 100) AS SIGNED) ASC';  break;
    case 'raised_desc':   $order_sql = 'ORDER BY c.current_amount DESC'; break;
    case 'raised_asc':    $order_sql = 'ORDER BY c.current_amount ASC';  break;
    case 'deadline_soon': $order_sql = 'ORDER BY c.deadline ASC';        break;
}

$stmt = $pdo->prepare("
    SELECT c.*,
           COALESCE(u.name, 'Share Hope') as ngo_name,
           cat.name as category_name,
           COUNT(d.id) as donor_count,
           (SELECT COUNT(*) FROM campaign_updates cu WHERE cu.campaign_id = c.id) as update_count
    FROM campaigns c
    LEFT JOIN ngos n ON c.ngo_id = n.id
    LEFT JOIN users u ON n.user_id = u.id
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN donations d ON c.id = d.campaign_id AND d.status = 'completed'
    $where_sql
    GROUP BY c.id
    $order_sql
");
$stmt->execute($params);
$campaigns = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT
        COUNT(*) as total_campaigns,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
        AVG(CASE WHEN goal_amount > 0 THEN (current_amount / goal_amount) * 100 ELSE 0 END) as avg_progress,
        SUM(current_amount) as total_raised
    FROM campaigns
");
$stats = $stmt->fetch();
?>

<div class="container" style="padding: 4rem 0; max-width: 1400px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">
            <h1 style="font-size: 2rem; margin-bottom: 2rem;">Campaign Performance Dashboard</h1>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Total Campaigns</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--primary);"><?= number_format($stats['total_campaigns']) ?></div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Active</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--secondary);"><?= number_format($stats['active_count']) ?></div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Completed</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--accent);"><?= number_format($stats['completed_count']) ?></div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Avg Progress</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--primary);"><?= round($stats['avg_progress'] ?? 0, 1) ?>%</div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Total Raised</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--secondary);">KSh <?= number_format($stats['total_raised'] ?? 0, 2) ?></div>
                </div>
            </div>

            <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <select name="status" class="form-control" style="width: auto;">
                        <option value="">All Status</option>
                        <option value="active"    <?= $filter_status === 'active'    ? 'selected' : '' ?>>Active Only</option>
                        <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <select name="category" class="form-control" style="width: auto;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $filter_category == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="sort" class="form-control" style="width: auto;">
                        <option value="raised_desc"   <?= $sort_by === 'raised_desc'   ? 'selected' : '' ?>>Funds Raised (High to Low)</option>
                        <option value="raised_asc"    <?= $sort_by === 'raised_asc'    ? 'selected' : '' ?>>Funds Raised (Low to High)</option>
                        <option value="progress_desc" <?= $sort_by === 'progress_desc' ? 'selected' : '' ?>>Progress (High to Low)</option>
                        <option value="progress_asc"  <?= $sort_by === 'progress_asc'  ? 'selected' : '' ?>>Progress (Low to High)</option>
                        <option value="deadline_soon" <?= $sort_by === 'deadline_soon' ? 'selected' : '' ?>>Deadline (Soonest First)</option>
                    </select>
                    <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.5rem;">Filter</button>
                    <a href="campaign_performance.php" class="btn btn-outline" style="padding: 0.65rem 1.5rem;">Reset</a>
                </form>
            </div>

            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); background: var(--background); font-size: 0.875rem; color: var(--text-muted);">
                                <th style="padding: 1rem 1.5rem;">Campaign Title</th>
                                <th style="padding: 1rem 1.5rem;">NGO</th>
                                <th style="padding: 1rem 1.5rem;">Category</th>
                                <th style="padding: 1rem 1.5rem;">Goal / Raised</th>
                                <th style="padding: 1rem 1.5rem;">Progress</th>
                                <th style="padding: 1rem 1.5rem;">Donors</th>
                                <th style="padding: 1rem 1.5rem;">Updates</th>
                                <th style="padding: 1rem 1.5rem;">Status</th>
                                <th style="padding: 1rem 1.5rem;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($campaigns)): ?>
                                <tr><td colspan="9" style="padding: 2rem; text-align: center; color: var(--text-muted);">No campaigns match your filters.</td></tr>
                            <?php else: ?>
                                <?php foreach ($campaigns as $camp): ?>
                                    <?php $progress = $camp['goal_amount'] > 0 ? round(($camp['current_amount'] / $camp['goal_amount']) * 100) : 0; ?>
                                    <tr style="border-bottom: 1px solid var(--border); font-size: 0.875rem;" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                        <td style="padding: 1rem 1.5rem; font-weight: 500; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= h($camp['title']) ?></td>
                                        <td style="padding: 1rem 1.5rem;"><?= h($camp['ngo_name']) ?></td>
                                        <td style="padding: 1rem 1.5rem;"><span style="background: rgba(79,70,229,0.1); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;"><?= h($camp['category_name'] ?? 'N/A') ?></span></td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <div style="font-weight: 600;">KSh <?= number_format($camp['goal_amount']) ?></div>
                                            <div style="color: var(--text-muted); font-size: 0.8rem;">(KSh <?= number_format($camp['current_amount'], 2) ?>)</div>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <div style="width: 60px; height: 6px; background: var(--border); border-radius: 999px; overflow: hidden;">
                                                    <div style="width: <?= min(100, $progress) ?>%; height: 100%; background: <?= $progress >= 100 ? 'var(--secondary)' : 'var(--primary)' ?>; border-radius: 999px;"></div>
                                                </div>
                                                <span style="font-weight: 600; min-width: 40px;"><?= $progress ?>%</span>
                                            </div>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;"><span style="background: var(--primary); color: white; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;"><?= number_format($camp['donor_count'] ?? 0) ?></span></td>
                                        <td style="padding: 1rem 1.5rem; text-align: center;"><span style="background: var(--accent); color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600;"><?= number_format($camp['update_count'] ?? 0) ?></span></td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <span style="padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;
                                                background: <?= $camp['status'] === 'active' ? 'rgba(16,185,129,0.1)' : ($camp['status'] === 'completed' ? 'rgba(79,70,229,0.1)' : 'rgba(239,68,68,0.1)') ?>;
                                                color: <?= $camp['status'] === 'active' ? 'var(--secondary)' : ($camp['status'] === 'completed' ? 'var(--primary)' : 'var(--danger)') ?>;">
                                                <?= ucfirst($camp['status']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <a href="/share_hope/donate.php?campaign_id=<?= $camp['id'] ?>" class="text-primary" style="text-decoration: none; font-weight: 500; white-space: nowrap;">
                                                <i class="fa-solid fa-external-link"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
