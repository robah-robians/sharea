<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/activity_logger.php';

$tab = $_GET['tab'] ?? 'all';
$error = '';
$success = '';

// Handle user status changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $user_id = intval($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $ngo_id = intval($_POST['ngo_id'] ?? 0);

    // Handle NGO verification
    if ($action === 'verify' && $ngo_id > 0) {
        $stmt = $pdo->prepare("UPDATE ngos SET is_verified = 1 WHERE id = ?");
        if ($stmt->execute([$ngo_id])) {
            $success = "NGO verified successfully.";
            log_admin_activity($pdo, $_SESSION['user_id'], 'verify_ngo', 'update', 'ngos', $ngo_id, null, "Verified NGO");
        } else {
            $error = "Failed to verify NGO.";
        }
    }
    // Handle user status changes
    elseif ($user_id === $_SESSION['user_id']) {
        $error = "You cannot suspend yourself.";
    } elseif ($user_id > 0) {
        $new_status = ($action === 'suspend') ? 'suspended' : 'active';
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        if ($stmt->execute([$new_status, $user_id])) {
            $success = "User status updated to: " . ucfirst($new_status);
            log_admin_activity($pdo, $_SESSION['user_id'], 'update_user_status', 'update', 'users', $user_id, null, "Changed user status to $new_status");
        } else {
            $error = "Failed to update user status.";
        }
    }
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$all_users = $stmt->fetchAll();

// Fetch NGOs with verification status
$ngo_stmt = $pdo->query("SELECT n.*, u.name, u.email, u.status FROM ngos n JOIN users u ON n.user_id = u.id ORDER BY n.created_at DESC");
$ngos_with_verification = $ngo_stmt->fetchAll();

// Count unverified NGOs
$unverified_count = count(array_filter($ngos_with_verification, fn($n) => !$n['is_verified']));

// Derive role-based subsets from $all_users
$ngos    = array_values(array_filter($all_users, fn($u) => $u['role'] === 'ngo'));
$donors  = array_values(array_filter($all_users, fn($u) => $u['role'] === 'donor'));
$admins  = array_values(array_filter($all_users, fn($u) => in_array($u['role'], ['admin', 'super_admin'])));

// Stats
$stats = [
    'total'     => count($all_users),
    'ngos'      => count($ngos),
    'donors'    => count($donors),
    'admins'    => count($admins),
    'active'    => count(array_filter($all_users, fn($u) => $u['status'] === 'active')),
    'suspended' => count(array_filter($all_users, fn($u) => $u['status'] === 'suspended'))
];
?>

<div style="padding: 2rem 0; max-width: none; margin: 0; width: 100%;">
    <div class="admin-layout" style="display: flex; gap: 0; align-items: flex-start; margin: 0; padding: 0;">
        <div style="padding-left: 0; margin-left: 0;">
            <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>
        </div>

        <div class="admin-main" style="flex: 1; min-width: 0; padding-left: 2.5rem; padding-right: 1.5rem; max-width: 1400px;">
            <h1 style="font-size: 1.75rem; margin: 0 0 1.5rem 0;">Stakeholders Registry</h1>

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
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: linear-gradient(135deg, var(--primary), rgba(79,70,229,0.8)); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.9;">Total Users</div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?= $stats['total'] ?></div>
                </div>
                <div style="background: linear-gradient(135deg, var(--secondary), rgba(16,185,129,0.8)); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.9;">NGOs</div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?= $stats['ngos'] ?></div>
                </div>
                <div style="background: linear-gradient(135deg, var(--accent), rgba(245,158,11,0.8)); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.9;">Donors</div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?= $stats['donors'] ?></div>
                </div>
                <div style="background: linear-gradient(135deg, #8B5CF6, rgba(139,92,246,0.8)); color: white; padding: 1rem; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 0.75rem; opacity: 0.9;">Active</div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?= $stats['active'] ?></div>
                </div>
            </div>

            <!-- Tabs -->
            <div style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); overflow: hidden;">
                <div style="padding: 1rem; border-bottom: 1px solid var(--border); background: var(--background); display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button onclick="showTab('all')" id="all-tab" class="tab-btn" style="padding: 0.6rem 1rem; background: var(--primary); color: white; border: none; cursor: pointer; font-weight: 600; border-radius: var(--radius-sm); font-size: 0.9rem;">All Users (<?= $stats['total'] ?>)</button>
                    <button onclick="showTab('ngos')" id="ngos-tab" class="tab-btn" style="padding: 0.6rem 1rem; background: transparent; color: var(--text-muted); border: 1px solid var(--border); cursor: pointer; font-weight: 600; border-radius: var(--radius-sm); font-size: 0.9rem;">NGOs (<?= $stats['ngos'] ?>) <?= $unverified_count > 0 ? '<span style="background: var(--danger); color: white; padding: 0.2rem 0.5rem; border-radius: 999px; font-size: 0.7rem; margin-left: 0.5rem;">' . $unverified_count . '</span>' : '' ?></button>
                    <button onclick="showTab('donors')" id="donors-tab" class="tab-btn" style="padding: 0.6rem 1rem; background: transparent; color: var(--text-muted); border: 1px solid var(--border); cursor: pointer; font-weight: 600; border-radius: var(--radius-sm); font-size: 0.9rem;">Donors (<?= $stats['donors'] ?>)</button>
                </div>

                <!-- All Users Tab -->
                <div id="all-content" class="tab-content" style="display: block; overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); background: var(--background);">
                                <th style="padding: 1rem 1.5rem; font-weight: 700;">Name</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 700;">Email</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 700;">Role</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 700;">Status</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 700; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $user): ?>
                                <tr style="border-bottom: 1px solid var(--border);" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 1rem 1.5rem; font-weight: 600;"><?= h($user['name']) ?></td>
                                    <td style="padding: 1rem 1.5rem; color: var(--text-muted);"><?= h($user['email']) ?></td>
                                    <td style="padding: 1rem 1.5rem;">
                                        <span style="background: <?= $user['role'] === 'admin' ? 'rgba(139,92,246,0.1)' : ($user['role'] === 'ngo' ? 'rgba(16,185,129,0.1)' : 'rgba(245,158,11,0.1)') ?>; color: <?= $user['role'] === 'admin' ? '#8B5CF6' : ($user['role'] === 'ngo' ? 'var(--secondary)' : 'var(--accent)') ?>; padding: 0.3rem 0.75rem; border-radius: var(--radius-sm); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem 1.5rem;">
                                        <span style="color: <?= $user['status'] === 'active' ? 'var(--secondary)' : 'var(--danger)' ?>; font-weight: 600;">
                                            <i class="fa-solid fa-<?= $user['status'] === 'active' ? 'circle-check' : 'ban' ?>"></i> <?= ucfirst($user['status']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem 1.5rem; text-align: right;">
                                        <?php if ($user['id'] !== $_SESSION['user_id'] && $user['role'] !== 'admin'): ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Change status?');">
                                                <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <input type="hidden" name="action" value="<?= $user['status'] === 'active' ? 'suspend' : 'activate' ?>">
                                                <button type="submit" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: <?= $user['status'] === 'active' ? 'var(--danger)' : 'var(--secondary)' ?>; color: white; border: none; cursor: pointer; border-radius: var(--radius-sm);">
                                                    <?= $user['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size: 0.8rem; color: var(--text-muted);">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- NGOs Tab -->
                <div id="ngos-content" class="tab-content" style="display: none; overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); background: var(--background);">
                                <th style="padding: 1rem 1.5rem; font-weight: 700;">Name</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 700;">Email</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 700;">Status</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 700;">Verified</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 700; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ngos_with_verification as $ngo): ?>
                                <tr style="border-bottom: 1px solid var(--border);" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 1rem 1.5rem; font-weight: 600;"><?= h($ngo['name']) ?></td>
                                    <td style="padding: 1rem 1.5rem; color: var(--text-muted);"><?= h($ngo['email']) ?></td>
                                    <td style="padding: 1rem 1.5rem;">
                                        <span style="color: <?= $ngo['status'] === 'active' ? 'var(--secondary)' : 'var(--danger)' ?>; font-weight: 600;">
                                            <i class="fa-solid fa-<?= $ngo['status'] === 'active' ? 'circle-check' : 'ban' ?>"></i> <?= ucfirst($ngo['status']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem 1.5rem;">
                                        <span style="color: <?= $ngo['is_verified'] ? 'var(--secondary)' : 'var(--accent)' ?>; font-weight: 600;">
                                            <i class="fa-solid fa-<?= $ngo['is_verified'] ? 'check-circle' : 'hourglass-half' ?>"></i> <?= $ngo['is_verified'] ? 'Verified' : 'Pending' ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem 1.5rem; text-align: right;">
                                        <div style="display: flex; gap: 0.5rem; justify-content: flex-end; flex-wrap: wrap;">
                                            <?php if (!$ngo['is_verified']): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Verify this NGO?');">
                                                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                    <input type="hidden" name="ngo_id" value="<?= $ngo['id'] ?>">
                                                    <input type="hidden" name="action" value="verify">
                                                    <button type="submit" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: var(--secondary); color: white; border: none; cursor: pointer; border-radius: var(--radius-sm);">
                                                        <i class="fa-solid fa-check" style="margin-right: 0.3rem;"></i>Verify
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Change status?');">
                                                <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                <input type="hidden" name="user_id" value="<?= $ngo['user_id'] ?>">
                                                <input type="hidden" name="action" value="<?= $ngo['status'] === 'active' ? 'suspend' : 'activate' ?>">
                                                <button type="submit" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: <?= $ngo['status'] === 'active' ? 'var(--danger)' : 'var(--secondary)' ?>; color: white; border: none; cursor: pointer; border-radius: var(--radius-sm);">
                                                    <?= $ngo['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($ngos_with_verification)): ?>
                        <div style="padding: 2rem; text-align: center; color: var(--text-muted);">No NGOs registered.</div>
                    <?php endif; ?>
                </div>

                <!-- Donors Tab -->
                <div id="donors-content" class="tab-content" style="display: none; overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border); background: var(--background);">
                                <th style="padding: 1rem 1.5rem; font-weight: 700;">Name</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 700;">Email</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 700;">Status</th>
                                <th style="padding: 1rem 1.5rem; font-weight: 700; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($donors as $user): ?>
                                <tr style="border-bottom: 1px solid var(--border);" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 1rem 1.5rem; font-weight: 600;"><?= h($user['name']) ?></td>
                                    <td style="padding: 1rem 1.5rem; color: var(--text-muted);"><?= h($user['email']) ?></td>
                                    <td style="padding: 1rem 1.5rem;">
                                        <span style="color: <?= $user['status'] === 'active' ? 'var(--secondary)' : 'var(--danger)' ?>; font-weight: 600;">
                                            <i class="fa-solid fa-<?= $user['status'] === 'active' ? 'circle-check' : 'ban' ?>"></i> <?= ucfirst($user['status']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem 1.5rem; text-align: right;">
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Change status?');">
                                            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="action" value="<?= $user['status'] === 'active' ? 'suspend' : 'activate' ?>">
                                            <button type="submit" class="btn" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; background: <?= $user['status'] === 'active' ? 'var(--danger)' : 'var(--secondary)' ?>; color: white; border: none; cursor: pointer; border-radius: var(--radius-sm);">
                                                <?= $user['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($donors)): ?>
                        <div style="padding: 2rem; text-align: center; color: var(--text-muted);">No donors registered.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.style.background = 'transparent';
        b.style.color = 'var(--text-muted)';
        b.style.border = '1px solid var(--border)';
    });
    document.getElementById(tabName + '-content').style.display = 'block';
    const btn = document.getElementById(tabName + '-tab');
    btn.style.background = 'var(--primary)';
    btn.style.color = 'white';
    btn.style.border = '1px solid var(--primary)';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
