<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: /share_hope/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$tab = $_GET['tab'] ?? 'all';
$success = '';
$error = '';

// Check if created_by column exists
$check_column = $pdo->query("SHOW COLUMNS FROM announcements LIKE 'created_by'");
$has_created_by = $check_column->rowCount() > 0;

// Handle create/update/delete for announcements
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    if ($_POST['action'] === 'post_announcement') {
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);
        $type = $_POST['type'] ?? 'standard';
        $action_link = trim($_POST['action_link'] ?? '');
        
        if (empty($title) || empty($message)) {
            $error = "Title and message are required.";
        } else {
            $is_public = ($type === 'urgent') ? 1 : 0;
            if ($has_created_by) {
                $stmt = $pdo->prepare("INSERT INTO announcements (title, message, is_public, action_link, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$title, $message, $is_public, $action_link, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO announcements (title, message, is_public, action_link, created_at) VALUES (?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$title, $message, $is_public, $action_link]);
            }
            
            if ($result) {
                $success = "Announcement posted successfully.";
                log_admin_activity($pdo, $_SESSION['user_id'], 'post_announcement', 'create', 'announcements', null, null, "Posted $type announcement: $title");
            } else {
                $error = "Failed to post announcement.";
            }
        }
    } elseif ($_POST['action'] === 'update_announcement') {
        $msg_id = intval($_POST['message_id']);
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);
        $type = $_POST['type'] ?? 'standard';
        $action_link = trim($_POST['action_link'] ?? '');
        
        if (empty($title) || empty($message)) {
            $error = "Title and message are required.";
        } else {
            $is_public = ($type === 'urgent') ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE announcements SET title = ?, message = ?, is_public = ?, action_link = ? WHERE id = ?");
            if ($stmt->execute([$title, $message, $is_public, $action_link, $msg_id])) {
                $success = "Announcement updated successfully.";
                log_admin_activity($pdo, $_SESSION['user_id'], 'update_announcement', 'update', 'announcements', $msg_id, null, "Updated announcement: $title");
            } else {
                $error = "Failed to update announcement.";
            }
        }
    } elseif ($_POST['action'] === 'delete_announcement') {
        $msg_id = intval($_POST['message_id']);
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        if ($stmt->execute([$msg_id])) {
            $success = "Announcement permanently deleted.";
            log_admin_activity($pdo, $_SESSION['user_id'], 'delete_announcement', 'delete', 'announcements', $msg_id, null, "Deleted announcement ID: $msg_id");
        } else {
            $error = "Failed to delete announcement.";
        }
    } elseif ($_POST['action'] === 'post_awareness') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $priority = $_POST['priority'] ?? 'normal';
        $action_link = trim($_POST['action_link'] ?? '');
        
        if (empty($title) || empty($description)) {
            $error = "Title and description are required.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO awareness_campaigns (title, description, priority, action_link, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
            if ($stmt->execute([$title, $description, $priority, $action_link])) {
                $success = "Awareness campaign created successfully.";
                log_admin_activity($pdo, $_SESSION['user_id'], 'post_awareness', 'create', 'awareness_campaigns', null, null, "Created awareness campaign: $title");
            } else {
                $error = "Failed to create awareness campaign.";
            }
        }
    } elseif ($_POST['action'] === 'update_awareness') {
        $campaign_id = intval($_POST['campaign_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $priority = $_POST['priority'] ?? 'normal';
        $action_link = trim($_POST['action_link'] ?? '');
        
        if (empty($title) || empty($description)) {
            $error = "Title and description are required.";
        } else {
            $stmt = $pdo->prepare("UPDATE awareness_campaigns SET title = ?, description = ?, priority = ?, action_link = ? WHERE id = ?");
            if ($stmt->execute([$title, $description, $priority, $action_link, $campaign_id])) {
                $success = "Awareness campaign updated successfully.";
                log_admin_activity($pdo, $_SESSION['user_id'], 'update_awareness', 'update', 'awareness_campaigns', $campaign_id, null, "Updated awareness campaign: $title");
            } else {
                $error = "Failed to update awareness campaign.";
            }
        }
    } elseif ($_POST['action'] === 'delete_awareness') {
        $campaign_id = intval($_POST['campaign_id']);
        $stmt = $pdo->prepare("DELETE FROM awareness_campaigns WHERE id = ?");
        if ($stmt->execute([$campaign_id])) {
            $success = "Awareness campaign permanently deleted.";
            log_admin_activity($pdo, $_SESSION['user_id'], 'delete_awareness', 'delete', 'awareness_campaigns', $campaign_id, null, "Deleted awareness campaign ID: $campaign_id");
        } else {
            $error = "Failed to delete awareness campaign.";
        }
    }
}

// Fetch announcements
if ($has_created_by) {
    $stmt = $pdo->query("SELECT a.id, a.title, a.message as description, a.is_public, a.action_link, a.created_at, u.name as creator_name, 'announcement' as source FROM announcements a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC");
} else {
    $stmt = $pdo->query("SELECT a.id, a.title, a.message as description, a.is_public, a.action_link, a.created_at, NULL as creator_name, 'announcement' as source FROM announcements a ORDER BY a.created_at DESC");
}
$announcements = $stmt->fetchAll();

// Fetch awareness campaigns
$stmt = $pdo->query("SELECT id, title, description, priority, action_link, is_active, created_at, 'awareness' as source FROM awareness_campaigns ORDER BY created_at DESC");
$awareness = $stmt->fetchAll();

// Merge and sort
$all_messages = array_merge($announcements, $awareness);
usort($all_messages, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Separate by type
$standard_msgs = array_filter($all_messages, fn($m) => !($m['is_public'] ?? 0) && $m['source'] === 'announcement');
$urgent_msgs = array_filter($all_messages, fn($m) => ($m['is_public'] ?? 0) && $m['source'] === 'announcement');
$awareness_msgs = array_filter($all_messages, fn($m) => $m['source'] === 'awareness');

// Stats
$stats = [
    'total' => count($all_messages),
    'standard' => count($standard_msgs),
    'urgent' => count($urgent_msgs),
    'awareness' => count($awareness_msgs)
];

// Check if editing
$edit_id = intval($_GET['edit'] ?? 0);
$edit_type = $_GET['type'] ?? 'announcement';
$edit_msg = null;

if ($edit_id > 0) {
    if ($edit_type === 'awareness') {
        $stmt = $pdo->prepare("SELECT * FROM awareness_campaigns WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_msg = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_msg = $stmt->fetch();
    }
}
?>

<div style="padding: 2rem 0; max-width: none; margin: 0; width: 100%;">
    <div class="admin-layout" style="display: flex; gap: 0; align-items: flex-start; margin: 0; padding: 0;">
        <div style="padding-left: 0; margin-left: 0;">
            <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>
        </div>

        <div class="admin-main" style="flex: 1; min-width: 0; padding-left: 2.5rem; padding-right: 1.5rem; max-width: 1400px;">
            <h1 style="font-size: 1.75rem; margin: 0 0 1.5rem 0;">Communications Hub</h1>

            <?php if ($success): ?>
                <div style="background: rgba(16,185,129,0.1); color: var(--secondary); padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem;">
                    <i class="fa-solid fa-circle-check"></i> <?= h($success) ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background: rgba(239,68,68,0.1); color: var(--danger); padding: 0.75rem 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem;">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?= h($error) ?>
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: linear-gradient(135deg, var(--primary), rgba(79,70,229,0.8)); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.9;">Total</div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?= $stats['total'] ?></div>
                </div>
                <div style="background: linear-gradient(135deg, var(--secondary), rgba(16,185,129,0.8)); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.9;">Standard</div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?= $stats['standard'] ?></div>
                </div>
                <div style="background: linear-gradient(135deg, var(--danger), rgba(239,68,68,0.8)); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.9;">🚨 Urgent</div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?= $stats['urgent'] ?></div>
                </div>
                <div style="background: linear-gradient(135deg, var(--accent), rgba(245,158,11,0.8)); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.9;">📢 Awareness</div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?= $stats['awareness'] ?></div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                
                <!-- Post/Edit Form -->
                <div style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 1.25rem;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 700;">
                        <?= $edit_msg ? '✏️ Edit ' . ($edit_type === 'awareness' ? 'Campaign' : 'Announcement') : '📝 Create New' ?>
                    </h3>
                    
                    <?php if (!$edit_msg): ?>
                        <!-- Type selector for new items -->
                        <div style="margin-bottom: 1rem; display: flex; gap: 0.5rem;">
                            <button type="button" onclick="switchForm('announcement')" id="btn-announcement" class="btn btn-primary" style="flex: 1; padding: 0.5rem; font-size: 0.85rem;">📢 Announcement</button>
                            <button type="button" onclick="switchForm('awareness')" id="btn-awareness" class="btn btn-outline" style="flex: 1; padding: 0.5rem; font-size: 0.85rem;">📢 Campaign</button>
                        </div>
                    <?php endif; ?>

                    <!-- Announcement Form -->
                    <form method="POST" id="form-announcement" style="display: <?= (!$edit_msg || $edit_type === 'announcement') ? 'block' : 'none' ?>;">
                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="<?= ($edit_msg && $edit_type === 'announcement') ? 'update_announcement' : 'post_announcement' ?>">
                        <?php if ($edit_msg && $edit_type === 'announcement'): ?>
                            <input type="hidden" name="message_id" value="<?= $edit_msg['id'] ?>">
                        <?php endif; ?>

                        <div style="margin-bottom: 0.75rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.85rem;">Title</label>
                            <input type="text" name="title" required placeholder="Announcement title" class="form-control" value="<?= ($edit_msg && $edit_type === 'announcement') ? h($edit_msg['title']) : '' ?>" style="padding: 0.6rem; font-size: 0.85rem;">
                        </div>

                        <div style="margin-bottom: 0.75rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.85rem;">Message</label>
                            <textarea name="message" required rows="3" placeholder="Your message..." class="form-control" style="padding: 0.6rem; font-size: 0.85rem; resize: vertical;"><?= ($edit_msg && $edit_type === 'announcement') ? h($edit_msg['description']) : '' ?></textarea>
                        </div>

                        <div style="margin-bottom: 0.75rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.85rem;">Type</label>
                            <select name="type" class="form-control" style="padding: 0.6rem; font-size: 0.85rem;">
                                <option value="standard" <?= (!$edit_msg || !($edit_msg['is_public'] ?? 0)) ? 'selected' : '' ?>>📢 Standard</option>
                                <option value="urgent" <?= ($edit_msg && ($edit_msg['is_public'] ?? 0)) ? 'selected' : '' ?>>🚨 Urgent</option>
                            </select>
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.85rem;">Action Link (Optional)</label>
                            <input type="url" name="action_link" placeholder="https://..." class="form-control" value="<?= ($edit_msg && $edit_type === 'announcement') ? h($edit_msg['action_link']) : '' ?>" style="padding: 0.6rem; font-size: 0.85rem;">
                        </div>

                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.6rem; font-size: 0.9rem; font-weight: 600;">
                                <i class="fa-solid fa-paper-plane"></i> <?= ($edit_msg && $edit_type === 'announcement') ? 'Update' : 'Post' ?>
                            </button>
                            <?php if ($edit_msg && $edit_type === 'announcement'): ?>
                                <a href="/share_hope/admin/communications.php" class="btn btn-outline" style="flex: 1; padding: 0.6rem; font-size: 0.9rem; font-weight: 600; text-decoration: none; text-align: center;">
                                    <i class="fa-solid fa-xmark"></i> Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Awareness Campaign Form -->
                    <form method="POST" id="form-awareness" style="display: <?= ($edit_msg && $edit_type === 'awareness') ? 'block' : 'none' ?>;">
                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="<?= ($edit_msg && $edit_type === 'awareness') ? 'update_awareness' : 'post_awareness' ?>">
                        <?php if ($edit_msg && $edit_type === 'awareness'): ?>
                            <input type="hidden" name="campaign_id" value="<?= $edit_msg['id'] ?>">
                        <?php endif; ?>

                        <div style="margin-bottom: 0.75rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.85rem;">Campaign Title</label>
                            <input type="text" name="title" required placeholder="Campaign title" class="form-control" value="<?= ($edit_msg && $edit_type === 'awareness') ? h($edit_msg['title']) : '' ?>" style="padding: 0.6rem; font-size: 0.85rem;">
                        </div>

                        <div style="margin-bottom: 0.75rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.85rem;">Description</label>
                            <textarea name="description" required rows="3" placeholder="Campaign description..." class="form-control" style="padding: 0.6rem; font-size: 0.85rem; resize: vertical;"><?= ($edit_msg && $edit_type === 'awareness') ? h($edit_msg['description']) : '' ?></textarea>
                        </div>

                        <div style="margin-bottom: 0.75rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.85rem;">Priority</label>
                            <select name="priority" class="form-control" style="padding: 0.6rem; font-size: 0.85rem;">
                                <option value="low" <?= ($edit_msg && $edit_msg['priority'] === 'low') ? 'selected' : '' ?>>Low</option>
                                <option value="normal" <?= (!$edit_msg || $edit_msg['priority'] === 'normal') ? 'selected' : '' ?>>Normal</option>
                                <option value="high" <?= ($edit_msg && $edit_msg['priority'] === 'high') ? 'selected' : '' ?>>High</option>
                            </select>
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.3rem; font-size: 0.85rem;">Action Link (Optional)</label>
                            <input type="url" name="action_link" placeholder="https://..." class="form-control" value="<?= ($edit_msg && $edit_type === 'awareness') ? h($edit_msg['action_link']) : '' ?>" style="padding: 0.6rem; font-size: 0.85rem;">
                        </div>

                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.6rem; font-size: 0.9rem; font-weight: 600;">
                                <i class="fa-solid fa-paper-plane"></i> <?= ($edit_msg && $edit_type === 'awareness') ? 'Update' : 'Create' ?>
                            </button>
                            <?php if ($edit_msg && $edit_type === 'awareness'): ?>
                                <a href="/share_hope/admin/communications.php" class="btn btn-outline" style="flex: 1; padding: 0.6rem; font-size: 0.9rem; font-weight: 600; text-decoration: none; text-align: center;">
                                    <i class="fa-solid fa-xmark"></i> Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- All Messages -->
                <div style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); padding: 1.25rem; max-height: 500px; overflow-y: auto;">
                    <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 700;">All Messages & Campaigns</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <?php foreach (array_slice($all_messages, 0, 15) as $msg): ?>
                            <div style="padding: 0.75rem; background: var(--background); border-radius: var(--radius-md); border-left: 3px solid <?= 
                                $msg['source'] === 'awareness' ? 'var(--accent)' : 
                                ($msg['is_public'] ?? 0 ? 'var(--danger)' : 'var(--primary)') 
                            ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.3rem;">
                                    <div style="flex: 1; min-width: 0;">
                                        <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-main); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= h($msg['title']) ?></div>
                                        <div style="font-size: 0.7rem; color: var(--text-muted);">by <?= h($msg['creator_name'] ?? 'Admin') ?></div>
                                    </div>
                                    <span style="font-size: 0.7rem; color: var(--text-muted); margin-left: 0.5rem; flex-shrink: 0;">
                                        <?= $msg['source'] === 'awareness' ? '📢' : ($msg['is_public'] ?? 0 ? '🚨' : '📝') ?>
                                    </span>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.5rem;"><?= date('M j, H:i', strtotime($msg['created_at'])) ?></div>
                                <div style="display: flex; gap: 0.4rem;">
                                    <a href="/share_hope/admin/communications.php?edit=<?= $msg['id'] ?>&type=<?= $msg['source'] ?>" class="btn" style="flex: 1; padding: 0.3rem 0.5rem; font-size: 0.7rem; background: var(--primary); color: white; border: none; cursor: pointer; border-radius: var(--radius-sm); text-decoration: none; text-align: center;">
                                        <i class="fa-solid fa-pen"></i> Edit
                                    </a>
                                    <form method="POST" style="flex: 1; display: flex;" onsubmit="return confirm('Permanently delete this item?');">
                                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                        <input type="hidden" name="action" value="<?= $msg['source'] === 'awareness' ? 'delete_awareness' : 'delete_announcement' ?>">
                                        <input type="hidden" name="<?= $msg['source'] === 'awareness' ? 'campaign_id' : 'message_id' ?>" value="<?= $msg['id'] ?>">
                                        <button type="submit" class="btn" style="flex: 1; padding: 0.3rem 0.5rem; font-size: 0.7rem; background: var(--danger); color: white; border: none; cursor: pointer; border-radius: var(--radius-sm);">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchForm(type) {
    document.getElementById('form-announcement').style.display = type === 'announcement' ? 'block' : 'none';
    document.getElementById('form-awareness').style.display = type === 'awareness' ? 'block' : 'none';
    document.getElementById('btn-announcement').className = type === 'announcement' ? 'btn btn-primary' : 'btn btn-outline';
    document.getElementById('btn-awareness').className = type === 'awareness' ? 'btn btn-primary' : 'btn btn-outline';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
