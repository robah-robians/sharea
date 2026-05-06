<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
$criticalLockFile = __DIR__ . '/../.critical_update_lock';
$has_role_level_col = (bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'role_level'")->fetch();


// Handle staff operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    verify_csrf_token($_POST['csrf_token'] ?? '');
    if (file_exists($criticalLockFile) && $_SESSION['user_role'] !== 'super_admin') {
        $_SESSION['error'] = 'Critical update lock is enabled. Staff role changes are temporarily disabled.';
        header("Location: staff.php");
        exit;
    }

    
    if ($action === 'promote') {
        // Promote user to admin staff with role level
        $user_id = $_POST['user_id'] ?? 0;
        $role_level = $_POST['role_level'] ?? 1;
        
        // Cannot promote to super_admin
        if ($role_level >= 3) {
            $_SESSION['error'] = "Cannot create super admin roles from this interface";
        } else {
            if ($has_role_level_col) {
                $rl = $pdo->prepare("SELECT COALESCE(role_level, 0) AS role_level FROM users WHERE id = ?");
                $rl->execute([$user_id]);
                $target = $rl->fetch();
                if ($target && (int)$target['role_level'] >= 3) {
                    $_SESSION['error'] = 'Protected account cannot be modified.';
                    header("Location: staff.php");
                    exit;
                }
            }
            try {
                $sql = $has_role_level_col ? "UPDATE users SET role = 'admin', role_level = ? WHERE id = ?" : "UPDATE users SET role = 'admin' WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                if ($has_role_level_col) { $stmt->execute([$role_level, $user_id]); } else { $stmt->execute([$user_id]); }
                $_SESSION['success'] = "User promoted to admin staff successfully";
            } catch (Exception $e) {
                $_SESSION['error'] = "Failed to promote user: " . $e->getMessage();
            }
        }
        header("Location: staff.php");
        exit;
    }
    
    if ($action === 'demote') {
        // Demote admin back to donor
        $user_id = $_POST['user_id'] ?? 0;
        
        // Cannot demote yourself
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error'] = "You cannot demote yourself";
        } else {
            if ($has_role_level_col) {
                $rl = $pdo->prepare("SELECT COALESCE(role_level, 0) AS role_level FROM users WHERE id = ?");
                $rl->execute([$user_id]);
                $target = $rl->fetch();
                if ($target && (int)$target['role_level'] >= 3) {
                    $_SESSION['error'] = 'Protected account cannot be demoted.';
                    header("Location: staff.php");
                    exit;
                }
            }
            try {
                $sql = $has_role_level_col ? "UPDATE users SET role = 'donor', role_level = 0 WHERE id = ?" : "UPDATE users SET role = 'donor' WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id]);
                $_SESSION['success'] = "User demoted to donor successfully";
            } catch (Exception $e) {
                $_SESSION['error'] = "Failed to demote user: " . $e->getMessage();
            }
        }
        header("Location: staff.php");
        exit;
    }
}

// Get staff list (schema-safe for partial migrations)
$has_role_level = (bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'role_level'")->fetch();
$has_last_login = (bool) $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'")->fetch();

$role_level_sql = $has_role_level ? "COALESCE(role_level, 1) AS role_level" : "1 AS role_level";
$last_login_sql = $has_last_login ? ", last_login" : ", NULL AS last_login";
$order_sql = $has_role_level ? "ORDER BY role_level DESC, created_at ASC" : "ORDER BY created_at ASC";

$staff = $pdo->query("
    SELECT id, name, email, $role_level_sql, created_at $last_login_sql
    FROM users
    WHERE role = 'admin'
    $order_sql
")->fetchAll();

// Get potential candidates for promotion (donors + NGOs)
$donors = $pdo->query("
    SELECT id, name, email, role, created_at
    FROM users
    WHERE role IN ('donor', 'ngo')
    ORDER BY role ASC, name ASC
    LIMIT 100
")->fetchAll();

// Role information
$roles = [
    1 => [
        'title' => 'Assistant Admin',
        'icon' => 'user-gear',
        'color' => 'secondary',
        'description' => 'Can manage campaigns, moderate content, and view reports',
        'permissions' => ['View reports', 'Moderate campaigns', 'View activity logs', 'Manage announcements']
    ],
    2 => [
        'title' => 'Admin',
        'icon' => 'shield',
        'color' => 'accent',
        'description' => 'Can manage all admin staff, NGOs, and export data',
        'permissions' => ['Promote assistant admins', 'Manage NGO approvals', 'Export data', 'Create announcements', 'Access all reports']
    ],
    3 => [
        'title' => 'Super Admin',
        'icon' => 'crown',
        'color' => 'gold',
        'description' => 'Full system access and control',
        'permissions' => ['All admin permissions', 'System settings', 'Database management', 'Staff hierarchy control']
    ]
];
?>


<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container" style="padding: 2.5rem 0; max-width: 1150px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">
            <?php if (isset($_SESSION['success'])): ?>
                <div style="background: #DCFCE7; border-left: 4px solid #10B981; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 2rem; color: #166534;">
                    <strong> Success:</strong> <?= h($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div style="background: #FEE2E2; border-left: 4px solid #EF4444; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 2rem; color: #991B1B;">
                    <strong> Error:</strong> <?= h($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div style="margin-bottom: 2.5rem;">
                <h1 style="font-size: 2rem; margin: 0 0 0.5rem 0;"><i class="fa-solid fa-users text-accent"></i> Staff Manager</h1>
                <p style="color: var(--text-muted); margin: 0;">Manage admin roles and staff hierarchy</p>
            </div>

            <!-- Role Levels Explained -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                <?php 
                $display_roles = array_filter($roles, fn($k) => $k < 3, ARRAY_FILTER_USE_KEY);
                foreach ($display_roles as $level => $role_info): 
                ?>
                <div style="background: white; border-radius: var(--radius-lg); border: 2px solid var(--border); padding: 1.5rem;">
                    <div style="display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1rem;">
                        <i class="fa-solid fa-<?= $role_info['icon'] ?>" style="font-size: 1.5rem; color: var(--<?= $role_info['color'] ?>); flex-shrink: 0;"></i>
                        <div>
                            <h3 style="margin: 0 0 0.25rem 0;"><?= $role_info['title'] ?></h3>
                            <p style="color: var(--text-muted); margin: 0; font-size: 0.85rem;"><?= $role_info['description'] ?></p>
                        </div>
                    </div>
                    <div style="border-top: 1px solid var(--border); padding-top: 1rem;">
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0 0 0.75rem 0; font-weight: 600;">Key Permissions:</p>
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.85rem;">
                            <?php foreach ($role_info['permissions'] as $perm): ?>
                            <li style="padding: 0.3rem 0; color: var(--text-muted);"> <?= $perm ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Current Staff -->
            <div style="background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); margin-bottom: 3rem; overflow: hidden;">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                    <h2 style="margin: 0; font-size: 1.25rem;"><i class="fa-solid fa-users-gear"></i> Active Admin Staff</h2>
                </div>
                
                <?php if (count($staff) > 0): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--bg-secondary); border-bottom: 2px solid var(--border);">
                                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">Name</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">Email</th>
                                <th style="padding: 1rem; text-align: center; font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">Role Level</th>
                                <th style="padding: 1rem; text-align: left; font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">Joined</th>
                                <th style="padding: 1rem; text-align: center; font-weight: 600; color: var(--text-muted); font-size: 0.85rem;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $member): 
                                $role_info = $roles[$member['role_level']] ?? $roles[1];
                                $is_super = $member['role_level'] >= 3;
                                $is_self = $member['id'] == $_SESSION['user_id'];
                            ?>
                            <tr style="border-bottom: 1px solid var(--border); transition: background 0.2s;">
                                <td style="padding: 1rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <i class="fa-solid fa-<?= $role_info['icon'] ?>" style="color: var(--<?= $role_info['color'] ?>);"></i>
                                        <strong><?= h($member['name']) ?></strong>
                                        <?php if ($is_self): ?>
                                        <span style="background: var(--primary); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600;">YOU</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="padding: 1rem; color: var(--text-muted);"><?= h($member['email']) ?></td>
                                <td style="padding: 1rem; text-align: center;">
                                    <span style="background: rgba(var(--<?= $role_info['color'] ?>-rgb), 0.1); color: var(--<?= $role_info['color'] ?>); padding: 0.4rem 0.8rem; border-radius: 0.25rem; font-size: 0.8rem; font-weight: 600;">
                                        <?= $role_info['title'] ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem; color: var(--text-muted); font-size: 0.85rem;">
                                    <?= date('M d, Y', strtotime($member['created_at'])) ?>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <?php if (!$is_super && !$is_self): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                        <input type="hidden" name="action" value="demote">
                                        <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline text-danger" onclick="return confirm('Demote this user to donor?')">
                                            <i class="fa-solid fa-arrow-down"></i> Demote
                                        </button>
                                    </form>
                                    <?php elseif ($is_super): ?>
                                    <span style="padding: 0.4rem 0.8rem; border-radius: 0.25rem; background: rgba(217, 119, 6, 0.1); color: var(--accent); font-size: 0.8rem; font-weight: 600;">
                                        <i class="fa-solid fa-lock"></i> Protected
                                    </span>
                                    <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;"></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div style="padding: 2rem; text-align: center; color: var(--text-muted);">
                    <p style="margin: 0;">No staff members yet</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Promote Staff -->
            <div style="background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                    <h2 style="margin: 0; font-size: 1.25rem;"><i class="fa-solid fa-arrow-up-from-ground-water"></i> Promote Users to Admin Staff</h2>
                </div>
                
                <form method="POST" style="padding: 1.5rem;">
                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                    <input type="hidden" name="action" value="promote">
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;"><i class="fa-solid fa-user"></i> Select User *</label>
                        <select name="user_id" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-md); font-family: inherit; font-size: inherit;">
                            <option value="">-- Choose a user to promote --</option>
                            <?php foreach ($donors as $donor): ?>
                            <option value="<?= $donor['id'] ?>">
                                [<?= strtoupper(h($donor['role'])) ?>] <?= h($donor['name']) ?> (<?= h($donor['email']) ?>) — Joined <?= date('M Y', strtotime($donor['created_at'])) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 1rem; font-weight: 600;"><i class="fa-solid fa-shield"></i> Admin Role Level *</label>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <label style="display: flex; align-items: center; padding: 1rem; border: 2px solid var(--border); border-radius: var(--radius-md); cursor: pointer; transition: all 0.2s;"
                                onmouseover="this.style.borderColor='var(--secondary)'"
                                onmouseout="this.style.borderColor='var(--border)'">
                                <input type="radio" name="role_level" value="1" checked style="margin-right: 0.75rem; cursor: pointer;">
                                <div>
                                    <div style="font-weight: 600;">Assistant Admin</div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">Can manage campaigns</div>
                                </div>
                            </label>
                            <label style="display: flex; align-items: center; padding: 1rem; border: 2px solid var(--border); border-radius: var(--radius-md); cursor: pointer; transition: all 0.2s;"
                                onmouseover="this.style.borderColor='var(--accent)'"
                                onmouseout="this.style.borderColor='var(--border)'">
                                <input type="radio" name="role_level" value="2" style="margin-right: 0.75rem; cursor: pointer;">
                                <div>
                                    <div style="font-weight: 600;">Admin</div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">Full admin access</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Promote to Admin Staff
                    </button>
                </form>
            </div>

            <!-- Info Box -->
            <div style="background: rgba(79, 70, 229, 0.05); border-left: 4px solid var(--primary); padding: 1.5rem; border-radius: var(--radius-md); margin-top: 2rem;">
                <h4 style="margin: 0 0 0.75rem 0; color: var(--primary);"> Staff Management Notes</h4>
                <ul style="margin: 0; padding-left: 1.5rem; color: var(--text-muted); font-size: 0.9rem; line-height: 1.8;">
                    <li><strong>Assistant Admin:</strong> Can view and moderate campaigns, manage announcements, and view activity logs</li>
                    <li><strong>Admin:</strong> Full access to promote assistant admins, manage NGO approvals, export data, and system settings</li>
                    <li><strong>Super Admin:</strong> Reserved for system administrators  cannot be modified from this interface</li>
                    <li>You cannot demote yourself or modify super admin accounts</li>
                    <li>All staff promotions and demotions are logged in the activity audit trail</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>




