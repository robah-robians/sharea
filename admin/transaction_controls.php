<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';


$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$view = $_GET['view'] ?? 'all';
$allowedViews = ['all', 'hidden', 'flagged_or_voided'];
if (!in_array($view, $allowedViews, true)) {
    $view = 'all';
}

$where = '';
if ($view === 'hidden') {
    $where = 'WHERE COALESCE(d.hidden_in_ledger,0) = 1';
} elseif ($view === 'flagged_or_voided') {
    $where = "WHERE COALESCE(d.moderation_state,'normal') IN ('flagged','voided')";
}

$sql = "SELECT d.id, d.transaction_id, d.amount, d.status, d.payment_method, d.created_at,
              COALESCE(d.hidden_in_ledger,0) AS hidden_in_ledger, 
              COALESCE(d.moderation_state,'normal') AS moderation_state,
              c.title as campaign_title,
              u.name as donor_name
        FROM donations d 
        LEFT JOIN campaigns c ON d.campaign_id = c.id
        LEFT JOIN users u ON d.donor_id = u.id
        " . $where . " ORDER BY d.created_at DESC LIMIT 150";
$txns = $pdo->query($sql)->fetchAll();
?>

<div class="container" style="padding: 2.5rem 0; max-width: 1150px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>
        
        <div class="admin-main" style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
                <h1 style="font-size: 2rem; margin: 0;"><i class="fa-solid fa-shield-halved text-primary"></i> Transaction Controls</h1>
                <div style="background: var(--surface); padding: 0.75rem 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                    <span style="font-size: 0.875rem; color: var(--text-muted);">Total Transactions: </span>
                    <span style="font-weight: 700; color: var(--primary);"><?= count($txns) ?></span>
                </div>
            </div>

            <?php if ($success): ?>
                <div style="background: var(--secondary); color: white; padding: 1rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-check-circle"></i> <?= h($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div style="background: var(--danger); color: white; padding: 1rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-exclamation-triangle"></i> <?= h($error) ?>
                </div>
            <?php endif; ?>

            <!-- Filter Controls -->
            <div style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); margin-bottom: 2rem;">
                <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <label style="font-weight: 600; color: var(--text-main);">Filter View:</label>
                    <select name="view" class="form-control" style="width: auto; min-width: 200px;">
                        <option value="all" <?= $view === 'all' ? 'selected' : '' ?>>🔍 Show All Transactions</option>
                        <option value="hidden" <?= $view === 'hidden' ? 'selected' : '' ?>>👁️‍🗨️ Show Hidden Only</option>
                        <option value="flagged_or_voided" <?= $view === 'flagged_or_voided' ? 'selected' : '' ?>>⚠️ Show Flagged/Voided</option>
                    </select>
                    <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.5rem;">Apply Filter</button>
                    <a href="transaction_controls.php" class="btn btn-outline" style="padding: 0.65rem 1.5rem;">Reset</a>
                </form>
            </div>

            <!-- Transactions Table -->
            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-list-check text-accent"></i> Transaction Moderation
                    </h3>
                    <span style="font-size: 0.875rem; color: var(--text-muted);">Showing <?= count($txns) ?> transactions</span>
                </div>
                
                <?php if (empty($txns)): ?>
                    <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>No transactions found for the selected filter.</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="background: var(--background); border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.875rem;">
                                    <th style="padding: 1rem 1.5rem;">Date</th>
                                    <th style="padding: 1rem 1.5rem;">Donor</th>
                                    <th style="padding: 1rem 1.5rem;">Amount</th>
                                    <th style="padding: 1rem 1.5rem;">Status Summary</th>
                                    <th style="padding: 1rem 1.5rem; text-align: right;">Options</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($txns as $t): ?>
                                    <!-- Primary Row -->
                                    <tr style="border-bottom: 1px solid var(--border); transition: background 0.3s;" 
                                        onmouseover="this.style.background='var(--background)'" 
                                        onmouseout="this.style.background='transparent'">
                                        <td style="padding: 1rem 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
                                            <?= date('M j, Y g:i A', strtotime($t['created_at'])) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; font-weight: 500;">
                                            <?= $t['donor_name'] ? h($t['donor_name']) : '<em style="color: var(--text-muted);">Anonymous</em>' ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; font-weight: 700; color: var(--primary);">
                                            $<?= number_format((float)$t['amount'], 2) ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <span style="padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;
                                                background: <?= $t['moderation_state'] === 'flagged' ? 'var(--warning)' : ($t['moderation_state'] === 'voided' ? 'var(--danger)' : 'var(--border)') ?>;
                                                color: <?= $t['moderation_state'] === 'normal' ? 'var(--text-muted)' : 'white' ?>;">
                                                <?= ucfirst($t['moderation_state']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; text-align: right;">
                                            <button type="button" class="btn btn-outline toggle-btn" data-target="txn-<?= (int)$t['id'] ?>" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                                View Details <i class="fa-solid fa-chevron-down" style="pointer-events: none;"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Detail Row (Hidden by default) -->
                                    <tr id="txn-<?= (int)$t['id'] ?>" class="txn-detail-row" style="display: none; background: var(--secondary);">
                                        <td colspan="5" style="padding: 1.5rem; border-bottom: 2px solid var(--primary);">
                                            <div style="display: flex; flex-wrap: wrap; gap: 2rem; align-items: flex-start;">
                                                <div style="flex: 1; min-width: 200px;">
                                                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Transaction ID</div>
                                                    <div style="font-family: monospace; font-size: 0.95rem; color: var(--primary);"><?= h($t['transaction_id']) ?></div>
                                                    
                                                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem; margin-top: 1rem;">Target Campaign</div>
                                                    <div style="font-size: 0.95rem; font-weight: 500;"><?= $t['campaign_title'] ? h($t['campaign_title']) : '<em style="color: var(--text-muted);">N/A</em>' ?></div>
                                                </div>
                                                
                                                <div style="flex: 1; min-width: 200px;">
                                                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.25rem;">Visibility Status</div>
                                                    <div style="margin-bottom: 1rem;">
                                                        <span style="padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: <?= ((int)$t['hidden_in_ledger'] === 1) ? 'var(--danger)' : 'var(--primary)' ?>; color: white;">
                                                            <?= ((int)$t['hidden_in_ledger'] === 1) ? '👁️‍🗨️ Hidden from ledgers' : '👁️ Visible to public' ?>
                                                        </span>
                                                    </div>

                                                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 0.5rem;">Moderation Actions</div>
                                                    <form action="<?= BASE_URL ?>/actions/admin_moderate_donation.php" method="POST" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                        <input type="hidden" name="donation_id" value="<?= (int)$t['id'] ?>">
                                                        
                                                        <?php if ($t['moderation_state'] !== 'flagged'): ?>
                                                            <button class="btn btn-outline" name="mod_action" value="flag" style="padding: 0.5rem 0.75rem; font-size: 0.75rem; background: var(--warning); color: white; border: none;" title="Flag Transaction">
                                                                <i class="fa-solid fa-flag"></i> Flag
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ((int)$t['hidden_in_ledger'] !== 1): ?>
                                                            <button class="btn btn-outline" name="mod_action" value="hide" style="padding: 0.5rem 0.75rem; font-size: 0.75rem; background: var(--danger); color: white; border: none;" title="Hide Transaction">
                                                                <i class="fa-solid fa-eye-slash"></i> Hide
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn btn-outline" name="mod_action" value="restore" style="padding: 0.5rem 0.75rem; font-size: 0.75rem; background: var(--primary); color: white; border: none;" title="Restore Transaction">
                                                                <i class="fa-solid fa-eye"></i> Restore
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($t['moderation_state'] !== 'voided'): ?>
                                                            <button class="btn btn-outline" name="mod_action" value="void" style="padding: 0.5rem 0.75rem; font-size: 0.75rem; background: var(--text-main); color: white; border: none;" title="Void Transaction" onclick="return confirm('Are you sure you want to void this transaction?')">
                                                                <i class="fa-solid fa-ban"></i> Void
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var buttons = document.querySelectorAll('.toggle-btn');
                buttons.forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        var targetId = this.getAttribute('data-target');
                        var targetRow = document.getElementById(targetId);
                        if (targetRow.style.display === 'none' || targetRow.style.display === '') {
                            targetRow.style.display = 'table-row';
                            this.innerHTML = 'Close Details <i class="fa-solid fa-chevron-up"></i>';
                        } else {
                            targetRow.style.display = 'none';
                            this.innerHTML = 'View Details <i class="fa-solid fa-chevron-down"></i>';
                        }
                    });
                });
            });
            </script>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
