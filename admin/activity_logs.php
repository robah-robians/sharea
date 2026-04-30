<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /share_hope/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';


$filter_action = $_GET['action'] ?? '';
$filter_admin = $_GET['admin'] ?? '';
$filter_days = $_GET['days'] ?? '30';
$sort_by = $_GET['sort'] ?? 'recent';

// Build query
$where_clauses = ["al.created_at > DATE_SUB(NOW(), INTERVAL ? DAY)"];
$params = [(int)$filter_days];

if ($filter_action) {
    $where_clauses[] = "al.action_type = ?";
    $params[] = $filter_action;
}

if ($filter_admin) {
    $where_clauses[] = "al.admin_id = ?";
    $params[] = $filter_admin;
}

$where_sql = implode(' AND ', $where_clauses);

// Get sorting
$order_sql = 'ORDER BY al.created_at DESC';
if ($sort_by === 'oldest') {
    $order_sql = 'ORDER BY al.created_at ASC';
}

$stmt = $pdo->prepare("
    SELECT al.*, u.name as admin_name, u.email as admin_email
    FROM activity_logs al
    JOIN users u ON al.admin_id = u.id
    WHERE $where_sql
    $order_sql
    LIMIT 500
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get admin list for filter
$stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'admin' ORDER BY name");
$admins = $stmt->fetchAll();

// Get stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_actions,
        COUNT(DISTINCT admin_id) as active_admins,
        COUNT(DISTINCT DATE(created_at)) as active_days
    FROM activity_logs
    WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
");
$stmt->execute([(int)$filter_days]);
$stats = $stmt->fetch();

// Get action type breakdown
$stmt = $pdo->prepare("
    SELECT action_type, COUNT(*) as count
    FROM activity_logs
    WHERE created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY action_type
    ORDER BY count DESC
");
$stmt->execute([(int)$filter_days]);
$action_breakdown = $stmt->fetchAll();
?>

<div class="container" style="padding: 4rem 0; max-width: 1400px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Activity Logs & Audit Trail</h1>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Track all admin actions for security and compliance purposes.</p>

            <!-- Stats Overview -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Total Actions</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--primary);"><?= number_format($stats['total_actions']) ?></div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Active Admins</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--secondary);"><?= number_format($stats['active_admins']) ?></div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Active Days</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--accent);"><?= number_format($stats['active_days']) ?></div>
                </div>
                <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Period</div>
                    <div style="font-size: 1.75rem; font-weight: 700; color: var(--primary);">Last <?= number_format((int)$filter_days) ?> Days</div>
                </div>
            </div>

            <!-- Filters -->
            <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); margin-bottom: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <select name="days" class="form-control" style="width: auto;">
                        <option value="7" <?= $filter_days === '7' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="30" <?= $filter_days === '30' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="90" <?= $filter_days === '90' ? 'selected' : '' ?>>Last 90 Days</option>
                        <option value="365" <?= $filter_days === '365' ? 'selected' : '' ?>>Last Year</option>
                    </select>

                    <select name="action" class="form-control" style="width: auto;">
                        <option value="">All Actions</option>
                        <option value="approve" <?= $filter_action === 'approve' ? 'selected' : '' ?>>Approvals</option>
                        <option value="deny" <?= $filter_action === 'deny' ? 'selected' : '' ?>>Denials</option>
                        <option value="suspend" <?= $filter_action === 'suspend' ? 'selected' : '' ?>>Suspensions</option>
                        <option value="update" <?= $filter_action === 'update' ? 'selected' : '' ?>>Updates</option>
                        <option value="delete" <?= $filter_action === 'delete' ? 'selected' : '' ?>>Deletions</option>
                        <option value="export" <?= $filter_action === 'export' ? 'selected' : '' ?>>Exports</option>
                    </select>
                    
                    <select name="admin" class="form-control" style="width: auto;">
                        <option value="">All Admins</option>
                        <?php foreach ($admins as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= $filter_admin == $a['id'] ? 'selected' : '' ?>>
                                <?= h($a['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="sort" class="form-control" style="width: auto;">
                        <option value="recent" <?= $sort_by === 'recent' ? 'selected' : '' ?>>Most Recent</option>
                        <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                    </select>
                    
                    <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.5rem;">Filter</button>
                    <a href="activity_logs.php" class="btn btn-outline" style="padding: 0.65rem 1.5rem;">Reset</a>
                </form>
            </div>

            <!-- Action Type Breakdown -->
            <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); margin-bottom: 2rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                <?php foreach ($action_breakdown as $action): ?>
                    <div style="text-align: center; padding: 1rem; background: var(--background); border-radius: var(--radius-sm);">
                        <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary);"><?= number_format($action['count']) ?></div>
                        <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;"><?= ucfirst($action['action_type']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Activity Logs Table -->
            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); background: var(--background); font-size: 0.875rem; color: var(--text-muted);">
                                <th style="padding: 1rem 1.5rem;">Timestamp</th>
                                <th style="padding: 1rem 1.5rem;">Admin Node</th>
                                <th style="padding: 1rem 1.5rem;">Action Type</th>
                                <th style="padding: 1rem 1.5rem;">Entity Source</th>
                                <th style="padding: 1rem 1.5rem;">Payload Details</th>
                                <th style="padding: 1rem 1.5rem;">IP Address</th>
                                <th style="padding: 1rem 1.5rem; text-align: right;">Auditing</th>
                            </tr>
                        </thead>
                        <tbody id="logs-body">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="7" style="padding: 3rem; text-align: center; color: var(--text-muted);">No activity logs trace detected for these parameters.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $index => $log): ?>
                                    <?php 
                                        $action_colors = [
                                            'approve' => ['bg' => 'rgba(16, 185, 129, 0.1)', 'color' => 'var(--secondary)', 'icon' => 'fa-check'],
                                            'deny' => ['bg' => 'rgba(239, 68, 68, 0.1)', 'color' => 'var(--danger)', 'icon' => 'fa-xmark'],
                                            'suspend' => ['bg' => 'rgba(239, 68, 68, 0.1)', 'color' => 'var(--danger)', 'icon' => 'fa-ban'],
                                            'delete' => ['bg' => 'rgba(239, 68, 68, 0.1)', 'color' => 'var(--danger)', 'icon' => 'fa-trash'],
                                            'update' => ['bg' => 'rgba(79, 70, 229, 0.1)', 'color' => 'var(--primary)', 'icon' => 'fa-pen'],
                                            'create' => ['bg' => 'rgba(20, 184, 166, 0.1)', 'color' => 'var(--accent)', 'icon' => 'fa-asterisk'],
                                            'export' => ['bg' => 'rgba(245, 158, 11, 0.1)', 'color' => 'var(--warning)', 'icon' => 'fa-download'],
                                        ];
                                        $color = $action_colors[$log['action_type']] ?? ['bg' => 'rgba(107, 114, 128, 0.1)', 'color' => 'var(--text-muted)', 'icon' => 'fa-bolt'];
                                    ?>
                                    <tr class="data-row" style="border-bottom: 1px solid var(--border); transition: var(--transition); font-size: 0.85rem; <?= $index >= 10 ? 'display: none;' : '' ?>"
                                        onmouseover="this.style.background='var(--background)'"
                                        onmouseout="this.style.background='transparent'">
                                        <td style="padding: 1rem 1.5rem; white-space: nowrap; color: var(--text-muted);">
                                            <?= date('M j, Y H:i:s', strtotime($log['created_at'])) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; font-weight: 500;">
                                            <div style="color: var(--text-main); font-weight: 600;"><?= h($log['admin_name']) ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;"><?= h($log['admin_email']) ?></div>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <span style="padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.7rem; font-weight: 700; background: <?= $color['bg'] ?>; color: <?= $color['color'] ?>; border: 1px solid <?= $color['color'] ?>;">
                                                <i class="fa-solid <?= $color['icon'] ?>" style="margin-right: 0.25rem;"></i> <?= ucfirst($log['action_type']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <div style="font-weight: 700; color: var(--primary); font-size: 0.8rem; text-transform: uppercase;"><?= h($log['entity_type'] ?? 'SYS_CORE') ?></div>
                                            <?php if ($log['entity_id']): ?>
                                                <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">ID: <?= number_format($log['entity_id']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-main);" title="<?= h($log['description'] ?? $log['action'] ?? '') ?>">
                                            <?= h($log['description'] ?? $log['action'] ?? 'N/A') ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; font-family: monospace; font-size: 0.75rem; color: var(--text-muted);">
                                            <i class="fa-solid fa-network-wired" style="margin-right: 0.25rem;"></i> <?= h($log['ip_address']) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; text-align: right;">
                                            <button type="button" class="btn btn-outline" style="padding: 0.35rem 0.65rem; font-size: 0.7rem;"
                                                onclick="document.getElementById('details-<?= $log['id'] ?>').style.display = document.getElementById('details-<?= $log['id'] ?>').style.display === 'none' ? 'table-row' : 'none'">
                                                <i class="fa-solid fa-code" style="margin-right: 0.25rem;"></i> Scan Logic
                                            </button>
                                        </td>
                                    </tr>
                                    <?php if ($log['old_value'] || $log['new_value']): ?>
                                        <tr id="details-<?= $log['id'] ?>" class="data-row" style="display: none; background: var(--background);">
                                            <td colspan="7" style="padding: 1.5rem;">
                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                                    <?php if ($log['old_value']): ?>
                                                        <div>
                                                            <div style="font-weight: 700; color: var(--danger); margin-bottom: 0.5rem; font-size: 0.75rem; text-transform: uppercase;">Pre-Execution Vector:</div>
                                                            <div style="background: var(--surface); padding: 1rem; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.75rem; word-break: break-all; border: 1px solid var(--border); color: var(--text-muted);">
                                                                <?= nl2br(h($log['old_value'])) ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($log['new_value']): ?>
                                                        <div>
                                                            <div style="font-weight: 700; color: var(--secondary); margin-bottom: 0.5rem; font-size: 0.75rem; text-transform: uppercase;">Post-Execution Vector:</div>
                                                            <div style="background: var(--surface); padding: 1rem; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.75rem; word-break: break-all; border: 1px solid var(--border); color: var(--text-main);">
                                                                <?= nl2br(h($log['new_value'])) ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if (count($logs) > 10): ?>
                        <div style="padding: 1.5rem; text-align: center; border-top: 1px solid var(--border);">
                            <button onclick="toggleLogRows('logs-body', this)" class="btn btn-text" style="font-weight: 600; color: var(--primary);">Stream Full Audits Matrix (<?= count($logs) ?>) <i class="fa-solid fa-chevron-down" style="margin-left: 0.5rem;"></i></button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <script>
            function toggleLogRows(bodyId, btn) {
                const tbody = document.getElementById(bodyId);
                const rows = tbody.querySelectorAll('tr.data-row');
                
                if(tbody.dataset.expanded === "true") {
                    tbody.dataset.expanded = "false";
                    
                    let count = 0;
                    rows.forEach(row => {
                        if (!row.id.startsWith('details-')) {
                            if (count >= 10) { row.style.display = 'none'; }
                            count++;
                        } else {
                            row.style.display = 'none'; 
                        }
                    });
                    
                    btn.innerHTML = btn.innerHTML.replace('Collapse Matrix Log', 'Stream Full Audits Matrix').replace('fa-chevron-up', 'fa-chevron-down');
                } else {
                    tbody.dataset.expanded = "true";
                    rows.forEach(row => {
                        if (!row.id.startsWith('details-')) {
                            row.style.display = 'table-row';
                        }
                    });
                    btn.innerHTML = btn.innerHTML.replace('Stream Full Audits Matrix', 'Collapse Matrix Log').replace('fa-chevron-down', 'fa-chevron-up');
                }
            }
            </script>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
