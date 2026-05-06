<?php
session_start();
require_once __DIR__ . '/../includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

// === GLOBALS & FILTERS ===
$filter_status = $_GET['status'] ?? '';
$filter_category = $_GET['category'] ?? '';
$sort_by = $_GET['sort'] ?? 'raised_desc';
$active_tab = $_GET['tab'] ?? 'performance'; // Default tab

// === FETCH DATA FOR PERFORMANCE TAB ===
$where_clauses = [];
$params = [];
if ($filter_status) { $where_clauses[] = "c.status = ?"; $params[] = $filter_status; }
if ($filter_category) { $where_clauses[] = "c.category_id = ?"; $params[] = $filter_category; }
$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$order_sql = 'ORDER BY c.created_at DESC';
switch ($sort_by) {
    case 'progress_desc': $order_sql = 'ORDER BY CAST(ROUND((c.current_amount / c.goal_amount) * 100) AS SIGNED) DESC'; break;
    case 'progress_asc': $order_sql = 'ORDER BY CAST(ROUND((c.current_amount / c.goal_amount) * 100) AS SIGNED) ASC'; break;
    case 'raised_desc': $order_sql = 'ORDER BY c.current_amount DESC'; break;
    case 'raised_asc': $order_sql = 'ORDER BY c.current_amount ASC'; break;
    case 'deadline_soon': $order_sql = 'ORDER BY c.deadline ASC'; break;
}

$stmt = $pdo->prepare("
    SELECT c.*, 
           cat.name as category_name,
           COUNT(DISTINCT d.donor_id) as donor_count,
           (SELECT COUNT(*) FROM campaign_updates cu WHERE cu.campaign_id = c.id) as update_count
    FROM campaigns c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN donations d ON c.id = d.campaign_id AND d.status = 'completed'
    $where_sql
    GROUP BY c.id
    $order_sql
");
$stmt->execute($params);
$campaigns = $stmt->fetchAll();

// Pre-fill deploy form from an approved campaign request
$prefill = null;
$from_request = intval($_GET['from_request'] ?? 0);
if ($from_request) {
    $pr = $pdo->prepare("SELECT cr.*, n.id as ngo_id FROM campaign_requests cr JOIN ngos n ON cr.ngo_id = n.id WHERE cr.id = ?");
    $pr->execute([$from_request]);
    $prefill = $pr->fetch();
}

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

<div class="container" style="padding: 2.5rem 0; max-width: 1150px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        
        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
                <div>
                    <h1 style="font-size: 2.5rem; margin: 0 0 0.5rem 0; font-weight: 800; color: var(--text-main);">Global Campaigns Hub</h1>
                    <p style="margin: 0; color: var(--text-muted); font-size: 1.1rem;">Manage network deployments, track institutional metrics, and oversee impact velocity.</p>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: var(--secondary); padding: 1rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-circle-check"></i> <?= h($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div style="background: rgba(245, 158, 11, 0.1); color: var(--danger); padding: 1rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= h($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Consolidated Module Container -->
            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
                <!-- Header with Tabs and Search -->
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
                    
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button onclick="showTab('performance')" id="performance-tab" class="tab-button" style="padding: 0.75rem 1.5rem; background: <?= $active_tab === 'performance' ? 'var(--primary)' : 'transparent' ?>; color: <?= $active_tab === 'performance' ? 'white' : 'var(--text-muted)' ?>; border: <?= $active_tab === 'performance' ? '1px solid var(--primary)' : '1px solid var(--border)' ?>; cursor: pointer; font-weight: 600; border-radius: var(--radius-sm); transition: all 0.3s;">
                            <i class="fa-solid fa-chart-line" style="margin-right: 0.5rem;"></i> System Performance (<?= count($campaigns) ?>)
                        </button>
                        <button onclick="showTab('deploy')" id="deploy-tab" class="tab-button" style="padding: 0.75rem 1.5rem; background: <?= $active_tab === 'deploy' ? 'var(--primary)' : 'transparent' ?>; color: <?= $active_tab === 'deploy' ? 'white' : 'var(--text-muted)' ?>; border: <?= $active_tab === 'deploy' ? '1px solid var(--primary)' : '1px solid var(--border)' ?>; cursor: pointer; font-weight: 600; border-radius: var(--radius-sm); transition: all 0.3s;">
                            <i class="fa-solid fa-rocket" style="margin-right: 0.5rem;"></i> Deploy New Initiative
                        </button>
                    </div>
                </div>

                <!-- 1. Performance Tab Content -->
                <div id="performance-content" class="tab-content" style="display: <?= $active_tab === 'performance' ? 'block' : 'none' ?>;">
                    
                    <!-- Analytics Grid -->
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div style="background: var(--background); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Active NGOs</div>
                            <div style="font-size: 1.85rem; font-weight: 800; color: var(--secondary);"><?= number_format($stats['active_count']) ?> System Live</div>
                        </div>
                        <div style="background: var(--background); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Global Acquisition</div>
                            <div style="font-size: 1.85rem; font-weight: 800; color: var(--primary);">KSh <?= number_format($stats['total_raised'] ?? 0, 2) ?></div>
                        </div>
                        <div style="background: var(--background); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Completed Goals</div>
                            <div style="font-size: 1.85rem; font-weight: 800; color: var(--accent);"><?= number_format($stats['completed_count']) ?> Initiatives</div>
                        </div>
                    </div>

                    <!-- Smart Filters -->
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--surface);">
                        <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="tab" value="performance">
                            <select name="status" class="form-control" style="width: auto; font-size: 0.85rem;">
                                <option value="">Target Status (All)</option>
                                <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Live NGOs</option>
                                <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                            
                            <select name="category" class="form-control" style="width: auto; font-size: 0.85rem;">
                                <option value="">Target Category (All)</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $filter_category == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="sort" class="form-control" style="width: auto; font-size: 0.85rem;">
                                <option value="raised_desc" <?= $sort_by === 'raised_desc' ? 'selected' : '' ?>>Sort: Acquisition High</option>
                                <option value="raised_asc" <?= $sort_by === 'raised_asc' ? 'selected' : '' ?>>Sort: Acquisition Low</option>
                                <option value="progress_desc" <?= $sort_by === 'progress_desc' ? 'selected' : '' ?>>Sort: Trajectory High</option>
                                <option value="deadline_soon" <?= $sort_by === 'deadline_soon' ? 'selected' : '' ?>>Sort: Critical Time</option>
                            </select>
                            
                            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Apply Logic</button>
                            <a href="campaigns_hub.php?tab=performance" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Reset Matrix</a>
                        </form>
                    </div>

                    <!-- Density Limited Table -->
                    <div style="overflow-x: auto;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; padding: 1.5rem;">
                            <?php if (empty($campaigns)): ?>
                                <div style="grid-column: 1 / -1; padding: 3rem; text-align: center; color: var(--text-muted);">
                                    <i class="fa-solid fa-inbox" style="font-size: 2.5rem; opacity: 0.3; margin-bottom: 1rem;"></i>
                                    <p>No deployment data matches tracking filters.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($campaigns as $index => $camp): ?>
                                    <?php $progress = $camp['goal_amount'] > 0 ? round(($camp['current_amount'] / $camp['goal_amount']) * 100) : 0; ?>
                                    <div class="data-row" style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 1.5rem; transition: all 0.3s ease; cursor: pointer; <?= $index >= 4 ? 'display: none;' : '' ?>" onmouseover="this.style.boxShadow='var(--shadow-float)'; this.style.transform='translateY(-4px)';" onmouseout="this.style.boxShadow='var(--shadow-sm)'; this.style.transform='translateY(0)';">
                                        
                                        <!-- Header -->
                                        <div style="margin-bottom: 1rem;">
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 0.75rem;">
                                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; color: var(--text-main); line-height: 1.3;"><?= h(substr($camp['title'], 0, 50)) ?><?= strlen($camp['title']) > 50 ? '...' : '' ?></h3>
                                                <span style="padding: 0.35rem 0.85rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; background: <?= 
                                                    $camp['status'] === 'active' ? 'rgba(16, 185, 129, 0.1)' : 
                                                    ($camp['status'] === 'completed' ? 'rgba(79, 70, 229, 0.1)' : 'rgba(239, 68, 68, 0.1)') 
                                                ?>; color: <?= 
                                                    $camp['status'] === 'active' ? 'var(--secondary)' : 
                                                    ($camp['status'] === 'completed' ? 'var(--primary)' : 'var(--danger)') 
                                                ?>; white-space: nowrap;">
                                                    <i class="fa-solid <?= $camp['status'] === 'active' ? 'fa-circle-dot' : 'fa-flag-checkered' ?>" style="margin-right: 0.25rem;"></i> <?= ucfirst($camp['status']) ?>
                                                </span>
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);"><i class="fa-solid fa-hashtag" style="margin-right: 0.3rem;"></i> Sync ID: #OP-<?= $camp['id'] ?></div>
                                        </div>

                                        <!-- Metrics Grid -->
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem; padding: 1rem; background: var(--background); border-radius: var(--radius-md);">
                                            <div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.35rem;">Fiscal Target</div>
                                                <div style="font-size: 1.1rem; font-weight: 800; color: var(--text-main);">KSh <?= number_format($camp['goal_amount']) ?></div>
                                            </div>
                                            <div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.35rem;">Current Acquisition</div>
                                                <div style="font-size: 1.1rem; font-weight: 800; color: var(--secondary);">KSh <?= number_format($camp['current_amount'], 2) ?></div>
                                            </div>
                                        </div>

                                        <!-- Progress Section -->
                                        <div style="margin-bottom: 1.25rem;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                                <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Trajectory</span>
                                                <span style="font-size: 0.9rem; font-weight: 800; color: <?= $progress >= 100 ? 'var(--secondary)' : 'var(--primary)' ?>;"><?= $progress ?>%</span>
                                            </div>
                                            <div style="width: 100%; height: 8px; background: var(--border); border-radius: 999px; overflow: hidden;">
                                                <div style="width: <?= min(100, $progress) ?>%; height: 100%; background: <?= $progress >= 100 ? 'var(--secondary)' : 'var(--primary)' ?>; border-radius: 999px; transition: width 0.5s ease;"></div>
                                            </div>
                                        </div>

                                        <!-- Supporters -->
                                        <div style="padding: 0.75rem; background: rgba(0, 102, 255, 0.05); border-radius: var(--radius-sm); margin-bottom: 1.25rem; text-align: center;">
                                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Verified Supporters</div>
                                            <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary);"><?= $camp['donor_count'] ?></div>
                                        </div>

                                        <!-- Actions -->
                                        <div style="display: flex; gap: 0.5rem; justify-content: space-between;">
                                            <a href="<?= BASE_URL ?>/donate.php?campaign_id=<?= $camp['id'] ?>" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.8rem; flex: 1; text-align: center;" target="_blank" title="View public page">
                                                <i class="fa-solid fa-eye"></i> View
                                            </a>
                                            <a href="<?= BASE_URL ?>/admin/edit_campaign.php?id=<?= $camp['id'] ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.8rem; flex: 1; text-align: center;" title="Edit initiative parameters">
                                                <i class="fa-solid fa-pen-to-square"></i> Edit
                                            </a>
                                            <form method="POST" action="<?= BASE_URL ?>/actions/undeploy_campaign_action.php" style="display:inline; flex: 1;" onsubmit="return confirm('TERMINATE this initiative? If donations exist it will be archived. If none, it will be deleted permanently.');">
                                                <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                <input type="hidden" name="campaign_id" value="<?= $camp['id'] ?>">
                                                <input type="hidden" name="redirect_url" value=BASE_URL . "/admin/campaigns_hub.php?tab=performance">
                                                <button type="submit" class="btn" style="padding: 0.5rem 1rem; font-size: 0.8rem; background: rgba(239,68,68,0.1); color: var(--danger); border: 1px solid rgba(239,68,68,0.3); cursor: pointer; width: 100%; title='Terminate/Undeploy initiative';">
                                                    <i class="fa-solid fa-circle-xmark"></i> End
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php if (count($campaigns) > 4): ?>
                            <div style="padding: 1.5rem; text-align: center; border-top: 1px solid var(--border);">
                                <button onclick="toggleRows('metrics-body', this)" class="btn btn-text" style="font-weight: 600; color: var(--primary);">View Complete Tracking Matrix <i class="fa-solid fa-chevron-down" style="margin-left: 0.5rem;"></i></button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. Deploy Tab Content -->
                <div id="deploy-content" class="tab-content" style="display: <?= $active_tab === 'deploy' ? 'block' : 'none' ?>; background: var(--background); padding: 2rem;">
                    
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 2.5rem; max-width: 900px; margin: 0 auto;">
                        <h3 style="margin-top: 0; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;"><i class="fa-solid fa-bolt text-accent" style="margin-right: 0.5rem;"></i> Parameter Configuration</h3>
                        
                        <form action="<?= BASE_URL ?>/actions/create_campaign_action.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                            <input type="hidden" name="redirect_url" value=BASE_URL . "/admin/campaigns_hub.php?tab=performance">
                            <?php if ($prefill): ?>
                            <input type="hidden" name="ngo_id" value="<?= $prefill['ngo_id'] ?>">
                            <input type="hidden" name="from_request_id" value="<?= $prefill['id'] ?>">
                            <div style="background: rgba(16,185,129,0.08); border-left: 4px solid var(--secondary); padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.9rem; color: var(--secondary); font-weight: 600;">
                                <i class="fa-solid fa-circle-check"></i> Pre-filled from NGO campaign request #<?= $prefill['id'] ?>. Review and publish.
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label class="form-label" style="font-weight: 700;">Operation Title</label>
                                <input type="text" name="title" class="form-control" required placeholder="E.g., Global Hydration Implementation Phase 1" value="<?= $prefill ? h($prefill['title']) : '' ?>" style="padding: 0.85rem; background: var(--background);">
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 700;">Fiscal Requirement (KSh)</label>
                                    <input type="number" step="0.01" name="goal_amount" class="form-control" required placeholder="50000" value="<?= $prefill ? h($prefill['goal_amount']) : '' ?>" style="padding: 0.85rem; background: var(--background);">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 700;">Logic Closure Timeline (Deadline)</label>
                                    <input type="date" name="deadline" class="form-control" required value="<?= $prefill ? h($prefill['deadline']) : '' ?>" style="padding: 0.85rem; background: var(--background);">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 700;">Deployment Date</label>
                                    <input type="date" name="deployment_date" class="form-control" required style="padding: 0.85rem; background: var(--background);">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 700;">Deployment Time</label>
                                    <input type="time" name="deployment_time" class="form-control" required style="padding: 0.85rem; background: var(--background);">
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label class="form-label" style="font-weight: 700;">Sector Classification</label>
                                <select name="category_id" class="form-control" required style="padding: 0.85rem; background: var(--background);">
                                    <option value="">Designate System Category</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($prefill && $prefill['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label class="form-label" style="font-weight: 700;">Mission Telemetry (Description)</label>
                                <textarea name="description" class="form-control" rows="6" required placeholder="Transmit full operation thesis, infrastructure required, and output estimates..." style="padding: 0.85rem; background: var(--background);"><?= $prefill ? h($prefill['description']) : '' ?></textarea>
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label class="form-label" style="font-weight: 700;">Deployment Details</label>
                                <textarea name="deployment_details" class="form-control" rows="4" placeholder="Provide specific deployment information, location, resources allocated, team involved, etc..." style="padding: 0.85rem; background: var(--background);"></textarea>
                            </div>

                            <div class="form-group" style="margin-bottom: 2.5rem;">
                                <label class="form-label" style="font-weight: 700;">System Asset (Cover Image)</label>
                                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png" style="padding: 0.75rem; background: var(--background); border: 2px dashed var(--border);">
                            </div>

                            <div style="display: flex; gap: 1rem; justify-content: flex-end; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                                <button type="button" onclick="showTab('performance')" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">Abort Initialization</button>
                                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;"><i class="fa-solid fa-satellite-dish" style="margin-right: 0.5rem;"></i> Publish Campaign</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>

            <!-- UI Logic Scripts -->
            <script>
            function showTab(tabName) {
                // Hide all
                document.querySelectorAll('.tab-content').forEach(content => content.style.display = 'none');
                document.querySelectorAll('.tab-button').forEach(button => {
                    button.style.background = 'transparent';
                    button.style.color = 'var(--text-muted)';
                    button.style.border = '1px solid var(--border)';
                });
                
                // Show requested tab
                document.getElementById(tabName + '-content').style.display = 'block';
                const activeBtn = document.getElementById(tabName + '-tab');
                activeBtn.style.background = 'var(--primary)';
                activeBtn.style.color = 'white';
                activeBtn.style.border = '1px solid var(--primary)';
                
                // Update URL parameters natively
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.pushState({}, '', url);
            }

            function toggleRows(bodyId, btn) {
                const rows = document.querySelectorAll('.data-row');
                
                if(btn.dataset.expanded === "true") {
                    btn.dataset.expanded = "false";
                    rows.forEach((row, index) => { if(index >= 4) row.style.display = 'none'; });
                    btn.innerHTML = btn.innerHTML.replace('Collapse Full Matrix', 'View Complete Tracking Matrix').replace('fa-chevron-up', 'fa-chevron-down');
                } else {
                    btn.dataset.expanded = "true";
                    rows.forEach(row => row.style.display = '');
                    btn.innerHTML = btn.innerHTML.replace('View Complete Tracking Matrix', 'Collapse Full Matrix').replace('fa-chevron-down', 'fa-chevron-up');
                }
            }
            </script>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
