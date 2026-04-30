<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: /share_hope/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';

$criticalLockFile = __DIR__ . '/../.critical_update_lock';
$has_role_level_col = (bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'role_level'")->fetch();


$error = '';
$success = '';

// Handle suspension toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    if (file_exists($criticalLockFile) && $_SESSION['user_role'] !== 'super_admin') {
        $error = 'Critical update lock is enabled. User status changes are temporarily disabled.';
    }
    $target_user_id = intval($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if (!empty($error)) {
        // Locked by critical update mode.
    } elseif ($target_user_id === $_SESSION['user_id']) {
        $error = "You cannot suspend yourself.";
    } elseif ($target_user_id > 0) {
        $new_status = ($action === 'suspend') ? 'suspended' : 'active';
        if ($has_role_level_col) {
            $rl_row = $pdo->query("SELECT COALESCE(role_level, 0) AS role_level FROM users WHERE id = " . (int)$target_user_id)->fetch();
            if ($rl_row && (int)$rl_row['role_level'] >= 3) { $error = 'Protected account cannot be suspended or activated.'; }
        }
        if (empty($error)) {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $target_user_id])) {
            $success = "User status updated to: " . ucfirst($new_status);
        } else {
            $error = "Failed to update user status.";
        }
        }
    }
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<div class="container" style="padding: 4rem 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <h1 style="font-size: 2rem; margin: 0;">Network Contributor Registry</h1>
        <a href="/share_hope/admin/dashboard.php" class="btn btn-outline">Back to Hub</a>
    </div>

    <?php if ($error): ?>
        <div
            style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <?= h($error) ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div
            style="background: var(--secondary); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <?= h($success) ?>
        </div>
    <?php endif; ?>

    <div
        style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr
                        style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.875rem; background: var(--background);">
                        <th style="padding: 1rem 1.5rem;">Name</th>
                        <th style="padding: 1rem 1.5rem;">Email</th>
                        <th style="padding: 1rem 1.5rem;">Role</th>
                        <th style="padding: 1rem 1.5rem;">Status</th>
                        <th style="padding: 1rem 1.5rem;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr style="border-bottom: 1px solid var(--border); transition: background 0.3s;"
                            onmouseover="this.style.background='var(--background)'"
                            onmouseout="this.style.background='transparent'">
                            <td style="padding: 1rem 1.5rem; font-weight: 500;">
                                <?= h($u['name']) ?>
                            </td>
                            <td style="padding: 1rem 1.5rem; color: var(--text-muted);">
                                <?= h($u['email']) ?>
                            </td>
                            <td style="padding: 1rem 1.5rem;">
                                <span
                                    style="text-transform: uppercase; font-size: 0.75rem; font-weight: 700; color: var(--primary); border: 1px solid var(--primary); padding: 0.1rem 0.4rem; border-radius: 4px;">
                                    <?= h($u['role']) ?>
                                </span>
                            </td>
                            <td style="padding: 1rem 1.5rem;">
                                <?php if ($u['status'] === 'active'): ?>
                                    <span style="color: var(--secondary); font-weight: 600;"><i
                                            class="fa-solid fa-circle-check"></i> Active</span>
                                <?php else: ?>
                                    <span style="color: var(--danger); font-weight: 600;"><i class="fa-solid fa-ban"></i>
                                        Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem 1.5rem;">
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" action="" onsubmit="return confirm('Change status for this user?');">
                                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <?php if ($u['status'] === 'active'): ?>
                                            <input type="hidden" name="action" value="suspend">
                                            <button type="submit" class="btn"
                                                style="padding: 0.25rem 0.75rem; font-size: 0.75rem; background: var(--danger); color: white; border: none; cursor: pointer;">Suspend</button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="btn"
                                                style="padding: 0.25rem 0.75rem; font-size: 0.75rem; background: var(--secondary); color: white; border: none; cursor: pointer;">Activate</button>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.75rem;">(You)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
