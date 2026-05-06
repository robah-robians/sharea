<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$active_tab = $_GET['tab'] ?? 'list';

// === HANDLE: Create New Awareness Campaign ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_campaign') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    try {
        $image_url = null;
        if (isset($_FILES['campaign_image']) && $_FILES['campaign_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../assets/uploads/images/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['campaign_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $filename = 'awareness_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['campaign_image']['tmp_name'], $upload_dir . $filename)) {
                    $image_url = BASE_URL . '/assets/uploads/images/' . $filename;
                }
            }
        }
        $stmt = $pdo->prepare("INSERT INTO awareness_campaigns (title, description, target_audience, campaign_type, priority, start_date, end_date, action_link, image_url, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['title'], $_POST['description'], $_POST['target_audience'],
            $_POST['campaign_type'], $_POST['priority'],
            $_POST['start_date'] ?: null, $_POST['end_date'] ?: null,
            $_POST['action_link'] ?: null, $image_url,
            isset($_POST['is_active']) ? 1 : 0, $_SESSION['user_id']
        ]);
        log_admin_activity($pdo, $_SESSION['user_id'], "Create awareness campaign", 'create', 'awareness_campaign', $pdo->lastInsertId(), null, "Admin created awareness campaign: {$_POST['title']}");
        $_SESSION['success'] = "Awareness campaign launched successfully.";
        header("Location: " . BASE_URL . "/admin/awareness_campaigns.php?tab=list");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}

// === HANDLE: Toggle / Delete ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campaign_action'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $cid    = intval($_POST['campaign_id']);
    $action = $_POST['campaign_action'];
    try {
        if ($action === 'toggle_status') {
            $pdo->prepare("UPDATE awareness_campaigns SET is_active = NOT is_active WHERE id = ?")->execute([$cid]);
            $_SESSION['success'] = "Campaign status updated.";
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM awareness_campaigns WHERE id = ?")->execute([$cid]);
            $_SESSION['success'] = "Campaign deleted from the network.";
        }
        log_admin_activity($pdo, $_SESSION['user_id'], "Campaign $action", 'update', 'awareness_campaign', $cid, null, "Admin {$action}d awareness campaign ID: $cid");
    } catch (PDOException $e) {
        $_SESSION['error'] = "Operation failed: " . $e->getMessage();
    }
    header("Location: " . BASE_URL . "/admin/awareness_campaigns.php?tab=list");
    exit;
}

// === FETCH DATA ===
$awareness_campaigns = $pdo->query("
    SELECT ac.*, u.name as creator_name
    FROM awareness_campaigns ac
    LEFT JOIN users u ON ac.created_by = u.id
    ORDER BY ac.created_at DESC
")->fetchAll();

$stats = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(is_active = 1) as active_count,
        SUM(is_active = 0) as inactive_count,
        SUM(priority = 'urgent') as urgent_count
    FROM awareness_campaigns
")->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 2.5rem 0; max-width: 1150px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">

        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">

            <!-- Page Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
                <div>
                    <h1 style="font-size: 2.5rem; margin: 0 0 0.5rem 0; font-weight: 800; color: var(--text-main);">Awareness Campaigns</h1>
                    <p style="margin: 0; color: var(--text-muted); font-size: 1.1rem;">Broadcast admin-authored awareness initiatives to the global contributor network.</p>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div style="background: rgba(16,185,129,0.1); color: var(--secondary); padding: 1rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-circle-check"></i> <?= h($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div style="background: rgba(239,68,68,0.1); color: var(--danger); padding: 1rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= h($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Consolidated Module Container -->
            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">

                <!-- Tab Bar -->
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background); display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button onclick="showTab('list')" id="list-tab" class="tab-button" style="padding: 0.75rem 1.5rem; background: <?= $active_tab === 'list' ? 'var(--primary)' : 'transparent' ?>; color: <?= $active_tab === 'list' ? 'white' : 'var(--text-muted)' ?>; border: <?= $active_tab === 'list' ? '1px solid var(--primary)' : '1px solid var(--border)' ?>; cursor: pointer; font-weight: 600; border-radius: var(--radius-sm); transition: all 0.3s;">
                        <i class="fa-solid fa-list" style="margin-right: 0.5rem;"></i>Active Broadcasts (<?= count($awareness_campaigns) ?>)
                    </button>
                    <button onclick="showTab('create')" id="create-tab" class="tab-button" style="padding: 0.75rem 1.5rem; background: <?= $active_tab === 'create' ? 'var(--primary)' : 'transparent' ?>; color: <?= $active_tab === 'create' ? 'white' : 'var(--text-muted)' ?>; border: <?= $active_tab === 'create' ? '1px solid var(--primary)' : '1px solid var(--border)' ?>; cursor: pointer; font-weight: 600; border-radius: var(--radius-sm); transition: all 0.3s;">
                        <i class="fa-solid fa-rocket" style="margin-right: 0.5rem;"></i>Launch New Campaign
                    </button>
                </div>

                <!-- ====== TAB 1: Campaign List ====== -->
                <div id="list-content" class="tab-content" style="display: <?= $active_tab === 'list' ? 'block' : 'none' ?>;">

                    <!-- Stats Strip -->
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
                        <div style="background: var(--background); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Total Broadcasts</div>
                            <div style="font-size: 1.85rem; font-weight: 800; color: var(--primary);"><?= number_format($stats['total']) ?></div>
                        </div>
                        <div style="background: var(--background); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Live on Network</div>
                            <div style="font-size: 1.85rem; font-weight: 800; color: var(--secondary);"><?= number_format($stats['active_count']) ?> Active</div>
                        </div>
                        <div style="background: var(--background); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                            <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Urgent Priority</div>
                            <div style="font-size: 1.85rem; font-weight: 800; color: var(--danger);"><?= number_format($stats['urgent_count']) ?> Alerts</div>
                        </div>
                    </div>

                    <!-- Campaign Table -->
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--border); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; background: var(--background);">
                                    <th style="padding: 1.25rem 1.5rem;">Campaign Title</th>
                                    <th style="padding: 1.25rem 1.5rem;">Type / Audience</th>
                                    <th style="padding: 1.25rem 1.5rem;">Priority</th>
                                    <th style="padding: 1.25rem 1.5rem;">Status</th>
                                    <th style="padding: 1.25rem 1.5rem;">Dates</th>
                                    <th style="padding: 1.25rem 1.5rem; text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($awareness_campaigns)): ?>
                                    <tr>
                                        <td colspan="6" style="padding: 3rem; text-align: center; color: var(--text-muted);">
                                            <i class="fa-solid fa-megaphone" style="font-size: 2rem; opacity: 0.3; display: block; margin-bottom: 0.75rem;"></i>
                                            No awareness campaigns launched yet. Click "Launch New Campaign" to begin.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($awareness_campaigns as $camp): ?>
                                        <?php
                                        $priority_color = match($camp['priority']) {
                                            'urgent' => 'var(--danger)',
                                            'high'   => 'var(--accent)',
                                            'medium' => 'var(--primary)',
                                            default  => 'var(--text-muted)'
                                        };
                                        $priority_bg = match($camp['priority']) {
                                            'urgent' => 'rgba(239,68,68,0.1)',
                                            'high'   => 'rgba(245,158,11,0.1)',
                                            'medium' => 'rgba(99,102,241,0.1)',
                                            default  => 'rgba(156,163,175,0.1)'
                                        };
                                        ?>
                                        <tr style="border-bottom: 1px solid var(--border); transition: var(--transition);" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                            <td style="padding: 1.25rem 1.5rem; max-width: 260px;">
                                                <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= h($camp['title']) ?></div>
                                                <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.2rem;">By: <?= h($camp['creator_name'] ?? 'Admin') ?></div>
                                            </td>
                                            <td style="padding: 1.25rem 1.5rem;">
                                                <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-main);"><?= ucfirst($camp['campaign_type']) ?></div>
                                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;"><i class="fa-solid fa-users" style="margin-right: 0.25rem;"></i><?= ucfirst($camp['target_audience']) ?></div>
                                            </td>
                                            <td style="padding: 1.25rem 1.5rem;">
                                                <span style="padding: 0.3rem 0.8rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: <?= $priority_bg ?>; color: <?= $priority_color ?>;">
                                                    <?= ucfirst($camp['priority']) ?>
                                                </span>
                                            </td>
                                            <td style="padding: 1.25rem 1.5rem;">
                                                <span style="padding: 0.35rem 0.85rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: <?= $camp['is_active'] ? 'rgba(16,185,129,0.1)' : 'rgba(156,163,175,0.1)' ?>; color: <?= $camp['is_active'] ? 'var(--secondary)' : 'var(--text-muted)' ?>;">
                                                    <i class="fa-solid fa-<?= $camp['is_active'] ? 'satellite-dish' : 'circle-pause' ?>" style="margin-right: 0.25rem;"></i><?= $camp['is_active'] ? 'Live' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td style="padding: 1.25rem 1.5rem; font-size: 0.8rem; color: var(--text-muted);">
                                                <?php if ($camp['start_date']): ?>
                                                    <div><i class="fa-regular fa-calendar-check" style="margin-right: 0.25rem;"></i><?= date('M j, Y', strtotime($camp['start_date'])) ?></div>
                                                <?php endif; ?>
                                                <?php if ($camp['end_date']): ?>
                                                    <div style="margin-top: 0.15rem;"><i class="fa-regular fa-calendar-xmark" style="margin-right: 0.25rem;"></i><?= date('M j, Y', strtotime($camp['end_date'])) ?></div>
                                                <?php else: ?>
                                                    <div style="color: var(--text-muted); font-style: italic;">No end date</div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 1.25rem 1.5rem; text-align: right;">
                                                <div style="display: flex; gap: 0.4rem; justify-content: flex-end; flex-wrap: wrap;">
                                                    <a href="<?= BASE_URL ?>/admin/edit_awareness_campaign.php?id=<?= $camp['id'] ?>" class="btn btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.72rem;" title="Edit">
                                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                                    </a>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                        <input type="hidden" name="campaign_id" value="<?= $camp['id'] ?>">
                                                        <button type="submit" name="campaign_action" value="toggle_status" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.72rem;" title="<?= $camp['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                            <i class="fa-solid fa-<?= $camp['is_active'] ? 'pause' : 'play' ?>"></i> <?= $camp['is_active'] ? 'Deactivate' : 'Activate' ?>
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this campaign?');">
                                                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                        <input type="hidden" name="campaign_id" value="<?= $camp['id'] ?>">
                                                        <button type="submit" name="campaign_action" value="delete" class="btn" style="padding: 0.35rem 0.75rem; font-size: 0.72rem; background: rgba(239,68,68,0.1); color: var(--danger); border: 1px solid rgba(239,68,68,0.3); cursor: pointer;" title="Delete">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ====== TAB 2: Create New Campaign ====== -->
                <div id="create-content" class="tab-content" style="display: <?= $active_tab === 'create' ? 'block' : 'none' ?>; background: var(--background); padding: 2rem;">
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 2.5rem; max-width: 900px; margin: 0 auto;">
                        <h3 style="margin-top: 0; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                            <i class="fa-solid fa-bolt text-accent" style="margin-right: 0.5rem;"></i>New Broadcast Configuration
                        </h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                            <input type="hidden" name="action" value="create_campaign">

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Campaign Title</label>
                                <input type="text" name="title" class="form-control" required placeholder="E.g., Global Hydration Awareness Drive" style="padding: 0.85rem; background: var(--background);">
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Mission Description</label>
                                <textarea name="description" class="form-control" rows="5" required placeholder="Describe the campaign message and objective..." style="padding: 0.85rem; background: var(--background);"></textarea>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Target Audience</label>
                                    <select name="target_audience" class="form-control" required style="padding: 0.85rem; background: var(--background);">
                                        <option value="">Select Audience</option>
                                        <option value="donors">Acquisition Contributors (Donors)</option>
                                        <option value="ngos">Implementation Contributors (NGOs)</option>
                                        <option value="both">All Contributors</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Campaign Type</label>
                                    <select name="campaign_type" class="form-control" required style="padding: 0.85rem; background: var(--background);">
                                        <option value="">Select Type</option>
                                        <option value="awareness">General Awareness</option>
                                        <option value="fundraising">Fundraising Drive</option>
                                        <option value="education">Educational</option>
                                        <option value="emergency">Emergency Alert</option>
                                        <option value="seasonal">Seasonal Campaign</option>
                                    </select>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Priority Level</label>
                                    <select name="priority" class="form-control" style="padding: 0.85rem; background: var(--background);">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" style="padding: 0.85rem; background: var(--background);">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">End Date</label>
                                    <input type="date" name="end_date" class="form-control" style="padding: 0.85rem; background: var(--background);">
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Action Link (Optional)</label>
                                <input type="url" name="action_link" class="form-control" placeholder="https://example.com/take-action" style="padding: 0.85rem; background: var(--background);">
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Cover Image (Optional)</label>
                                <input type="file" name="campaign_image" class="form-control" accept="image/*" style="padding: 0.75rem; background: var(--background); border: 2px dashed var(--border);">
                            </div>

                            <div class="form-group" style="margin-bottom: 2rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" name="is_active" value="1" checked style="width: 1.1rem; height: 1.1rem;">
                                    <span style="font-weight: 700; color: var(--text-main);">Activate immediately upon launch</span>
                                </label>
                            </div>

                            <div style="display: flex; gap: 1rem; justify-content: flex-end; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                                <button type="button" onclick="showTab('list')" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">Abort</button>
                                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                                    <i class="fa-solid fa-satellite-dish" style="margin-right: 0.5rem;"></i>Launch Campaign
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div><!-- end module container -->

            <script>
            function showTab(tabName) {
                document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
                document.querySelectorAll('.tab-button').forEach(b => {
                    b.style.background = 'transparent';
                    b.style.color = 'var(--text-muted)';
                    b.style.border = '1px solid var(--border)';
                });
                document.getElementById(tabName + '-content').style.display = 'block';
                const btn = document.getElementById(tabName + '-tab');
                btn.style.background = 'var(--primary)';
                btn.style.color = 'white';
                btn.style.border = '1px solid var(--primary)';
                const url = new URL(window.location);
                url.searchParams.set('tab', tabName);
                window.history.pushState({}, '', url);
            }
            </script>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
