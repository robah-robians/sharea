<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: /share_hope/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_emergency') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        $message = trim($_POST['message']);
        $severity = trim($_POST['severity'] ?? 'high');

        if (empty($message)) {
            $error = "Emergency message is required.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, message, is_public, created_at) VALUES (?, ?, ?, NOW())");
            $title = "🚨 EMERGENCY ALERT - " . strtoupper($severity);
            if ($stmt->execute([$title, $message, 1])) {
                $success = "Emergency alert broadcasted to all users immediately.";
            } else {
                $error = "Failed to send emergency alert.";
            }
        }
    }
}

// Fetch recent emergency alerts
$stmt = $pdo->query("SELECT * FROM announcements WHERE is_public = 1 ORDER BY created_at DESC LIMIT 10");
$emergencies = $stmt->fetchAll();
?>

<div style="padding: 4rem 0; max-width: none; margin: 0; width: 100%;">
    <div class="admin-layout" style="display: flex; gap: 0; align-items: flex-start; margin: 0; padding: 0;">

        <!-- Sidebar -->
        <div style="padding-left: 0; margin-left: 0;">
            <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>
        </div>

        <div class="admin-main" style="flex: 1; min-width: 0; padding-left: 2.5rem; padding-right: 1.5rem; max-width: 1400px;">
            <!-- Emergency Header -->
            <div style="text-align: center; margin-bottom: 3rem; padding: 2rem; background: linear-gradient(135deg, rgba(220,38,38,0.15), rgba(239,68,68,0.15)); border-radius: var(--radius-lg); border: 2px solid var(--danger); position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(220,38,38,0.05) 10px, rgba(220,38,38,0.05) 20px); pointer-events: none;"></div>
                <div style="position: relative; z-index: 10;">
                    <h1 style="font-size: 2.5rem; margin: 0; color: var(--danger); font-weight: 900; text-transform: uppercase; letter-spacing: 2px; animation: pulse 2s infinite;">
                        🚨 EMERGENCY BROADCAST CENTER 🚨
                    </h1>
                    <p style="color: var(--danger); margin: 1rem 0 0 0; font-size: 1rem; font-weight: 600; opacity: 0.9;">Instant system-wide alert distribution</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= h($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= h($success) ?>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">

                <!-- Emergency Alert Form -->
                <div style="background: linear-gradient(135deg, var(--surface), rgba(255,255,255,0.95)); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); border: 2px solid var(--danger); padding: 2rem; height: fit-content; position: relative;">
                    <div style="position: absolute; top: -15px; left: 20px; background: var(--danger); color: white; padding: 0.5rem 1rem; border-radius: var(--radius-md); font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px;">
                        ⚡ INSTANT BROADCAST
                    </div>

                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                        <input type="hidden" name="action" value="send_emergency">

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.75rem; font-size: 1rem;">
                                <i class="fa-solid fa-triangle-exclamation" style="color: var(--danger); font-size: 1.1rem;"></i>
                                Severity Level
                            </label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem; border: 2px solid var(--border); border-radius: var(--radius-md); transition: all 0.3s ease;" onMouseOver="this.style.borderColor='var(--danger)'; this.style.background='rgba(220,38,38,0.05)'" onMouseOut="this.style.borderColor='var(--border)'; this.style.background='transparent'">
                                    <input type="radio" name="severity" value="critical" checked style="width: 1.25rem; height: 1.25rem; accent-color: var(--danger);">
                                    <span style="font-weight: 600; color: var(--danger);">🔴 CRITICAL</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem; border: 2px solid var(--border); border-radius: var(--radius-md); transition: all 0.3s ease;" onMouseOver="this.style.borderColor='var(--warning)'; this.style.background='rgba(245,158,11,0.05)'" onMouseOut="this.style.borderColor='var(--border)'; this.style.background='transparent'">
                                    <input type="radio" name="severity" value="high" style="width: 1.25rem; height: 1.25rem; accent-color: var(--warning);">
                                    <span style="font-weight: 600; color: var(--warning);">🟠 HIGH</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.75rem; font-size: 1rem;">
                                <i class="fa-solid fa-message" style="color: var(--danger);"></i>
                                Emergency Message
                            </label>
                            <textarea name="message" required rows="5" placeholder="Type your emergency message here. Be clear and concise. This will be sent to ALL users immediately." class="form-control" style="border: 2px solid var(--danger); padding: 1rem; font-size: 1rem; resize: vertical; transition: all 0.3s ease;" onFocus="this.style.boxShadow='0 0 0 3px rgba(220,38,38,0.2)'" onBlur="this.style.boxShadow='none'"></textarea>
                            <small style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem; display: block;">
                                <i class="fa-solid fa-info-circle"></i> Maximum 500 characters recommended for clarity.
                            </small>
                        </div>

                        <div style="background: rgba(220,38,38,0.1); border: 2px dashed var(--danger); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.5rem;">
                            <p style="margin: 0; color: var(--danger); font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fa-solid fa-bolt"></i>
                                This alert will be instantly visible to ALL registered users on their dashboards.
                            </p>
                        </div>

                        <button type="submit" class="btn" style="width: 100%; background: linear-gradient(135deg, var(--danger), #dc2626); color: white; padding: 1rem; font-size: 1.1rem; font-weight: 800; border: none; border-radius: var(--radius-md); box-shadow: 0 0 20px rgba(220,38,38,0.4); transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; justify-content: center; gap: 0.75rem;" onMouseOver="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 0 30px rgba(220,38,38,0.6)'" onMouseOut="this.style.transform='translateY(0)'; this.style.boxShadow='0 0 20px rgba(220,38,38,0.4)'" onclick="return confirm('⚠️ CONFIRM: Send this emergency alert to ALL users immediately? This action cannot be undone.');">
                            <i class="fa-solid fa-paper-plane"></i>
                            SEND EMERGENCY ALERT NOW
                        </button>
                    </form>
                </div>

                <!-- Recent Emergencies -->
                <div style="background: linear-gradient(135deg, var(--surface), rgba(255,255,255,0.95)); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); border: 1px solid var(--border); overflow: hidden;">
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: linear-gradient(135deg, var(--danger), #dc2626); color: white;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="background: rgba(255,255,255,0.2); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                <i class="fa-solid fa-history"></i>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800;">Recent Alerts</h3>
                                <p style="margin: 0; opacity: 0.9; font-size: 0.85rem;">Last 10 emergency broadcasts</p>
                            </div>
                        </div>
                    </div>
                    <div style="padding: 1.5rem; max-height: 600px; overflow-y: auto;">
                        <?php if (empty($emergencies)): ?>
                            <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                <i class="fa-solid fa-inbox" style="font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                <p style="font-size: 1rem; font-weight: 600;">No emergency alerts sent yet.</p>
                            </div>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <?php foreach ($emergencies as $e): ?>
                                    <div style="border-left: 4px solid var(--danger); background: rgba(220,38,38,0.05); padding: 1rem; border-radius: var(--radius-md);">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--text-main);">
                                                <?= h($e['title']) ?>
                                            </h4>
                                            <span style="font-size: 0.75rem; color: var(--text-muted); background: var(--surface); padding: 0.3rem 0.6rem; border-radius: var(--radius-sm);">
                                                <?= date('M j, H:i', strtotime($e['created_at'])) ?>
                                            </span>
                                        </div>
                                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted); line-height: 1.4;">
                                            <?= substr(h($e['message']), 0, 100) ?>...
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
