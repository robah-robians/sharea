<?php
session_start();
require_once __DIR__ . '/../includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /share_hope/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';


$filter_verified = $_GET['verified'] ?? '';
$sort_by = $_GET['sort'] ?? 'score_desc';

// Get all NGOs with health data
$where_sql = '';
$params = [];

if ($filter_verified !== '') {
    $where_sql = 'WHERE n.is_verified = ?';
    $params = [$filter_verified];
}

$stmt = $pdo->prepare("
    SELECT n.*, u.name, u.email, u.phone,
           COUNT(c.id) as campaign_count,
           SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END) as total_raised,
           COUNT(DISTINCT d.donor_id) as unique_donors
    FROM ngos n
    JOIN users u ON n.user_id = u.id
    LEFT JOIN campaigns c ON n.id = c.ngo_id
    LEFT JOIN donations d ON c.id = d.campaign_id
    $where_sql
    GROUP BY n.id
");
$stmt->execute($params);
$ngos = $stmt->fetchAll();

// Calculate health scores and sort
$ngo_scores = [];
foreach ($ngos as $ngo) {
    $score = calculate_ngo_health_score($pdo, $ngo['id']);
    $ngo['score'] = $score;
    $ngo_scores[] = $ngo;
}

// Sort
usort($ngo_scores, function($a, $b) {
    global $sort_by;
    switch ($sort_by) {
        case 'score_asc':
            return $a['score'] <=> $b['score'];
        case 'name_asc':
            return $a['name'] <=> $b['name'];
        case 'name_desc':
            return $b['name'] <=> $a['name'];
        case 'score_desc':
        default:
            return $b['score'] <=> $a['score'];
    }
});

$ngos = $ngo_scores;

// Overall stats
$avg_score = count($ngos) > 0 ? array_sum(array_column($ngos, 'score')) / count($ngos) : 0;
?>

<div class="container" style="padding: 4rem 0; max-width: 1400px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">NGO Health Score & Performance</h1>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">AI-powered metric showing NGO reliability, transparency, and impact performance.</p>

            <!-- Stats Overview -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Total NGOs</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--primary);"><?= count($ngos) ?></div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Average Score</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--secondary);"><?= round($avg_score, 1) ?>/100</div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Excellent (85+)</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--secondary);"><?= count(array_filter($ngos, fn($n) => $n['score'] >= 85)) ?></div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Needs Work (<50)</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--danger);"><?= count(array_filter($ngos, fn($n) => $n['score'] < 50)) ?></div>
                </div>
            </div>

            <!-- Filters -->
            <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <select name="verified" class="form-control" style="width: auto;">
                        <option value="">All NGOs</option>
                        <option value="1" <?= $filter_verified === '1' ? 'selected' : '' ?>>Verified Only</option>
                        <option value="0" <?= $filter_verified === '0' ? 'selected' : '' ?>>Pending Verification</option>
                    </select>
                    
                    <select name="sort" class="form-control" style="width: auto;">
                        <option value="score_desc" <?= $sort_by === 'score_desc' ? 'selected' : '' ?>>Health Score (High to Low)</option>
                        <option value="score_asc" <?= $sort_by === 'score_asc' ? 'selected' : '' ?>>Health Score (Low to High)</option>
                        <option value="name_asc" <?= $sort_by === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                        <option value="name_desc" <?= $sort_by === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.5rem;">Filter</button>
                    <a href="ngo_health_score.php" class="btn btn-outline" style="padding: 0.65rem 1.5rem;">Reset</a>
                </form>
            </div>

            <!-- Health Scores Table -->
            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); background: var(--background); font-size: 0.875rem; color: var(--text-muted);">
                                <th style="padding: 1rem 1.5rem;">Organization Name</th>
                                <th style="padding: 1rem 1.5rem;">Contact</th>
                                <th style="padding: 1rem 1.5rem;">Campaigns</th>
                                <th style="padding: 1rem 1.5rem;">Funds Raised</th>
                                <th style="padding: 1rem 1.5rem;">Donors</th>
                                <th style="padding: 1rem 1.5rem;">Health Score</th>
                                <th style="padding: 1rem 1.5rem;">Status</th>
                                <th style="padding: 1rem 1.5rem;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ngos)): ?>
                                <tr>
                                    <td colspan="8" style="padding: 2rem; text-align: center; color: var(--text-muted);">No NGOs found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ngos as $ngo): ?>
                                    <?php $badge = get_health_score_badge($ngo['score']); ?>
                                    <tr style="border-bottom: 1px solid var(--border); transition: background 0.3s; font-size: 0.875rem;"
                                        onmouseover="this.style.background='var(--background)'"
                                        onmouseout="this.style.background='transparent'">
                                        <td style="padding: 1rem 1.5rem; font-weight: 500;">
                                            <?= h($ngo['name']) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                                <div><?= h($ngo['email']) ?></div>
                                                <div><?= h($ngo['phone'] ?? 'N/A') ?></div>
                                            </div>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; text-align: center;">
                                            <span style="display: inline-block; background: rgba(79, 70, 229, 0.1); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 4px; font-weight: 600;">
                                                <?= number_format($ngo['campaign_count'] ?? 0) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; font-weight: 600;">
                                            $<?= number_format($ngo['total_raised'] ?? 0, 2) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; text-align: center;">
                                            <span style="display: inline-block; background: rgba(16, 185, 129, 0.1); color: var(--secondary); padding: 0.25rem 0.75rem; border-radius: 4px; font-weight: 600;">
                                                <?= number_format($ngo['unique_donors'] ?? 0) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <div style="flex-shrink: 0;">
                                                    <div style="font-weight: 700; font-size: 1.1rem; color: <?= $badge['color'] ?>;">
                                                        <?= number_format($ngo['score'], 1) ?>
                                                    </div>
                                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= $badge['label'] ?></div>
                                                </div>
                                                <div style="flex: 1; min-width: 80px; height: 8px; background: var(--border); border-radius: 999px; overflow: hidden;">
                                                    <div style="width: <?= $ngo['score'] ?>%; height: 100%; background: <?= $badge['color'] ?>; border-radius: 999px; transition: width 0.3s;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <span style="padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: <?= $ngo['is_verified'] ? 'rgba(16, 185, 129, 0.1)' : 'rgba(245, 158, 11, 0.1)' ?>; color: <?= $ngo['is_verified'] ? 'var(--secondary)' : 'var(--accent)' ?>;">
                                                <?= $ngo['is_verified'] ? 'Verified' : 'Pending' ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <a href="/share_hope/admin/view_user.php?user_id=<?= $ngo['user_id'] ?>" class="text-primary" style="text-decoration: none; font-weight: 500; white-space: nowrap;">
                                                <i class="fa-solid fa-arrow-right"></i> View Profile
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Score Breakdown Legend -->
            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); padding: 1.5rem; margin-top: 2rem;">
                <h3 style="margin-top: 0; margin-bottom: 1rem;">Health Score Calculation</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <div style="padding: 1rem; background: rgba(79, 70, 229, 0.05); border-left: 3px solid var(--primary); border-radius: var(--radius-sm);">
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">Campaign Success (25%)</div>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">Percentage of campaigns that met their funding goal</div>
                    </div>
                    <div style="padding: 1rem; background: rgba(16, 185, 129, 0.05); border-left: 3px solid var(--secondary); border-radius: var(--radius-sm);">
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">Impact Updates (15%)</div>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">Frequency of transparency updates posted</div>
                    </div>
                    <div style="padding: 1rem; background: rgba(245, 158, 11, 0.05); border-left: 3px solid var(--accent); border-radius: var(--radius-sm);">
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">Donor Completion (20%)</div>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">Rate of successful donation completions</div>
                    </div>
                    <div style="padding: 1rem; background: rgba(79, 70, 229, 0.05); border-left: 3px solid var(--primary); border-radius: var(--radius-sm);">
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">Funds Raised (20%)</div>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">Total amount raised (5000+ = full score)</div>
                    </div>
                    <div style="padding: 1rem; background: rgba(16, 185, 129, 0.05); border-left: 3px solid var(--secondary); border-radius: var(--radius-sm);">
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">Account Reliability (10%)</div>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">Time active on platform (1 year = full score)</div>
                    </div>
                    <div style="padding: 1rem; background: rgba(245, 158, 11, 0.05); border-left: 3px solid var(--accent); border-radius: var(--radius-sm);">
                        <div style="font-weight: 600; margin-bottom: 0.5rem;">Response Time (10%)</div>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">Updates posted within 2 weeks of campaign launch</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>