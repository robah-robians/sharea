<?php
session_start();
require_once __DIR__ . '/../includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

// === GLOBALS & FILTERS ===
$active_tab = $_GET['tab'] ?? 'ledger'; // Default tab
$filter_ngo = $_GET['ngo'] ?? '';
$view_mod = $_GET['view'] ?? 'all'; // Moderation filter

// === 1. EXPORT CSV LOGIC ===
if (!empty($_GET['export']) && $_GET['export'] === 'csv') {
    $stmt = $pdo->prepare("SELECT d.*, c.title as campaign_title, u.name as donor_name
                         FROM donations d
                         JOIN campaigns c ON d.campaign_id = c.id
                         LEFT JOIN users u ON d.donor_id = u.id
                         WHERE d.status = 'completed' ORDER BY d.created_at DESC");
    $stmt->execute();
    $export_data = $stmt->fetchAll();
    log_admin_activity($pdo, $_SESSION['user_id'], 'Exported donation records', 'export', 'donations', null, 'Donations Ledger CSV', 'Exported ' . count($export_data) . ' records');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="system_ledger_export_' . date('Y-m-d_His') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Transaction ID', 'Donor', 'Campaign', 'Amount', 'Payment Method', 'Status']);
    foreach ($export_data as $row) {
        fputcsv($output, [
            date('Y-m-d H:i:s', strtotime($row['created_at'])), $row['transaction_id'],
            $row['is_anonymous'] ? 'Anonymous' : ($row['donor_name'] ?? 'Guest'),
            $row['campaign_title'], $row['amount'], $row['payment_method'], $row['status']
        ]);
    }
    fclose($output);
    exit;
}

// === 2. FETCH LEDGER DATA ===
$stmt = $pdo->prepare("SELECT d.*, c.title as campaign_title, u.name as donor_name
                     FROM donations d
                     JOIN campaigns c ON d.campaign_id = c.id
                     LEFT JOIN users u ON d.donor_id = u.id
                     WHERE d.status = 'completed' ORDER BY d.created_at DESC LIMIT 50");
$stmt->execute();
$transactions = $stmt->fetchAll();
$total_volume = array_sum(array_column($transactions, 'amount'));

// === 3. FETCH PLEDGES DATA ===
$inkind_stmt = $pdo->prepare("SELECT i.*, c.title as campaign_title, d.name as donor_name
                              FROM inkind_donations i
                              JOIN campaigns c ON i.campaign_id = c.id
                              LEFT JOIN users d ON i.donor_id = d.id
                              ORDER BY i.created_at DESC LIMIT 50");
$inkind_stmt->execute();
$inkind_pledges = $inkind_stmt->fetchAll();

// === 4. FETCH MODERATION AUDIT DATA ===
$mod_where = '';
if ($view_mod === 'hidden') { $mod_where = 'WHERE COALESCE(d.hidden_in_ledger,0) = 1'; } 
elseif ($view_mod === 'flagged_or_voided') { $mod_where = "WHERE COALESCE(d.moderation_state,'normal') IN ('flagged','voided')"; }

$sql = "SELECT d.id, d.transaction_id, d.amount, d.status, d.payment_method, d.created_at,
              COALESCE(d.hidden_in_ledger,0) AS hidden_in_ledger, 
              COALESCE(d.moderation_state,'normal') AS moderation_state,
              c.title as campaign_title,
              COALESCE(u.name, 'Guest') as donor_name
        FROM donations d 
        LEFT JOIN campaigns c ON d.campaign_id = c.id
        LEFT JOIN users u ON d.donor_id = u.id
        " . $mod_where . " ORDER BY d.created_at DESC LIMIT 150";
$mod_txns = $pdo->query($sql)->fetchAll();

$ngo_list = []; // Campaigns are admin-managed; no NGO filter applies
?>

<div class="container" style="padding: 2.5rem 0; max-width: 1150px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        
        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
                <div>
                    <h1 style="font-size: 2.5rem; margin: 0 0 0.5rem 0; font-weight: 800; color: var(--text-main);">Finance & Security Control</h1>
                    <p style="margin: 0; color: var(--text-muted); font-size: 1.1rem;">Monitor systemic volume, track asset transfers, and securely audit flagged global transactions.</p>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div style="background: rgba(16, 185, 129, 0.1); color: var(--secondary); padding: 1rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-circle-check"></i> <?= h($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- Consolidated Module Container -->
            <div style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
                <!-- Header with Tabs -->
                <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background); display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
                    <button onclick="showTab('ledger')" id="ledger-tab" class="tab-button" style="padding: 0.75rem 1.5rem; background: <?= $active_tab === 'ledger' ? 'var(--primary)' : 'transparent' ?>; color: <?= $active_tab === 'ledger' ? 'white' : 'var(--text-muted)' ?>; border: <?= $active_tab === 'ledger' ? '1px solid var(--primary)' : '1px solid var(--border)' ?>; cursor: pointer; font-weight: 600; border-radius: var(--radius-sm); transition: all 0.3s;">
                        <i class="fa-solid fa-vault"></i> System Ledger
                    </button>
                    <button onclick="showTab('pledges')" id="pledges-tab" class="tab-button" style="padding: 0.75rem 1.5rem; background: <?= $active_tab === 'pledges' ? 'var(--primary)' : 'transparent' ?>; color: <?= $active_tab === 'pledges' ? 'white' : 'var(--text-muted)' ?>; border: <?= $active_tab === 'pledges' ? '1px solid var(--primary)' : '1px solid var(--border)' ?>; cursor: pointer; font-weight: 600; border-radius: var(--radius-sm); transition: all 0.3s;">
                        <i class="fa-solid fa-boxes-packing"></i> Assets & Pledges
                    </button>
                    <button onclick="showTab('audit')" id="audit-tab" class="tab-button" style="padding: 0.75rem 1.5rem; background: <?= $active_tab === 'audit' ? 'var(--primary)' : 'transparent' ?>; color: <?= $active_tab === 'audit' ? 'white' : 'var(--text-muted)' ?>; border: <?= $active_tab === 'audit' ? '1px solid var(--primary)' : '1px solid var(--border)' ?>; cursor: pointer; font-weight: 600; border-radius: var(--radius-sm); transition: all 0.3s;">
                        <i class="fa-solid fa-shield-halved"></i> Transaction Audit <span style="background:var(--accent); color:white; padding: 2px 6px; border-radius: 999px; font-size: 0.7rem; margin-left: 0.25rem;">SECURE</span>
                    </button>
                </div>

                <!-- 1. System Ledger Tab -->
                <div id="ledger-content" class="tab-content" style="display: <?= $active_tab === 'ledger' ? 'block' : 'none' ?>;">
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--surface); display: flex; justify-content: flex-end; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div style="display: flex; gap: 1.5rem; align-items: center;">
                            <div style="text-align: right;">
                                <div style="font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Captured Volume</div>
                                <div style="font-size: 1.5rem; font-weight: 800; color: var(--secondary);">KSh <?= number_format($total_volume, 2) ?></div>
                            </div>
                            <a href="?export=csv" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Export Matrix (CSV)</a>
                        </div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--border); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; background: var(--background);">
                                    <th style="padding: 1.25rem 1.5rem;">Time Stamp</th>
                                    <th style="padding: 1.25rem 1.5rem;">TXN Identity</th>
                                    <th style="padding: 1.25rem 1.5rem;">Campaign</th>
                                    <th style="padding: 1.25rem 1.5rem;">Amount (KSh)</th>
                                    <th style="padding: 1.25rem 1.5rem; text-align: right;">Authorization</th>
                                </tr>
                            </thead>
                            <tbody id="ledger-body">
                                <?php if (empty($transactions)): ?>
                                    <tr><td colspan="5" style="padding: 3rem; text-align: center; color: var(--text-muted);">Database empty for these parameters.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $index => $txn): ?>
                                        <tr class="data-row" style="border-bottom: 1px solid var(--border); transition: var(--transition); <?= $index >= 5 ? 'display: none;' : '' ?>" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                            <td style="padding: 1.25rem 1.5rem; font-size: 0.85rem; color: var(--text-muted);"><?= date('M j, Y - H:i', strtotime($txn['created_at'])) ?></td>
                                            <td style="padding: 1.25rem 1.5rem; font-family: monospace; font-size: 0.75rem; color: var(--primary); font-weight: 600;"><?= h($txn['transaction_id']) ?></td>
                                            <td style="padding: 1.25rem 1.5rem; font-weight: 600; color: var(--text-main);"><?= h($txn['campaign_title']) ?></td>
                                            <td style="padding: 1.25rem 1.5rem; font-weight: 700; color: var(--secondary); font-size: 1.05rem;">$<?= number_format($txn['amount'], 2) ?></td>
                                            <td style="padding: 1.25rem 1.5rem; text-align: right;"><a href="<?= BASE_URL ?>/receipt.php?id=<?= $txn['id'] ?>" target="_blank" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.75rem;"><i class="fa-solid fa-print"></i> Generate Receipt</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if (count($transactions) > 5): ?>
                            <div style="padding: 1.5rem; text-align: center; border-top: 1px solid var(--border);">
                                <button onclick="toggleRows('ledger-body', this)" class="btn btn-text" style="font-weight: 600; color: var(--primary);">Expand Full Ledger (<?= count($transactions) ?>) <i class="fa-solid fa-chevron-down" style="margin-left: 0.5rem;"></i></button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 2. Pledges Tab -->
                <div id="pledges-content" class="tab-content" style="display: <?= $active_tab === 'pledges' ? 'block' : 'none' ?>;">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--border); color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; background: var(--background);">
                                    <th style="padding: 1.25rem 1.5rem;">Asset Vector</th>
                                    <th style="padding: 1.25rem 1.5rem;">Quantity Specification</th>
                                    <th style="padding: 1.25rem 1.5rem;">Recipient Hub</th>
                                    <th style="padding: 1.25rem 1.5rem;">Transfer Status</th>
                                    <th style="padding: 1.25rem 1.5rem; text-align: right;">SysAction</th>
                                </tr>
                            </thead>
                            <tbody id="pledges-body">
                                <?php if (empty($inkind_pledges)): ?>
                                    <tr><td colspan="5" style="padding: 3rem; text-align: center; color: var(--text-muted);">No asset pledges detected in system.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($inkind_pledges as $index => $pledge): ?>
                                        <tr class="data-row" style="border-bottom: 1px solid var(--border); transition: var(--transition); <?= $index >= 5 ? 'display: none;' : '' ?>" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                            <td style="padding: 1.25rem 1.5rem; font-weight: 700; color: var(--text-main);"><?= h($pledge['item_category']) ?></td>
                                            <td style="padding: 1.25rem 1.5rem; color: var(--text-muted);"><?= h($pledge['quantity']) ?></td>
                                            <td style="padding: 1.25rem 1.5rem; font-weight: 600; color: var(--primary);"><?= h($pledge['campaign_title']) ?></td>
                                            <td style="padding: 1.25rem 1.5rem;">
                                                <span style="padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: <?= 
                                                    $pledge['status'] === 'pledged' ? 'rgba(245, 158, 11, 0.1)' : 
                                                    ($pledge['status'] === 'received' ? 'rgba(79, 70, 229, 0.1)' : 'rgba(16, 185, 129, 0.1)') 
                                                ?>; color: <?= 
                                                    $pledge['status'] === 'pledged' ? 'var(--accent)' : 
                                                    ($pledge['status'] === 'received' ? 'var(--primary)' : 'var(--secondary)') 
                                                ?>;">
                                                    <?= ucfirst($pledge['status']) ?>
                                                </span>
                                            </td>
                                            <td style="padding: 1.25rem 1.5rem; text-align: right; position: relative;">
                                                <button type="button" class="btn btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.75rem;" onclick="toggleStatusMenu(this, <?= $pledge['id'] ?>)">Update Logic</button>
                                                <div id="menu-<?= $pledge['id'] ?>" style="display: none; position: absolute; right: 2rem; top: 100%; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); box-shadow: var(--shadow-sm); z-index: 10; min-width: 150px; text-align: left;">
                                                    <form method="POST" action="<?= BASE_URL ?>/actions/update_pledge_status.php" style="margin: 0; display:flex; flex-direction: column;">
                                                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                        <input type="hidden" name="pledge_id" value="<?= $pledge['id'] ?>">
                                                        <input type="hidden" name="redirect" value="finance_controls">
                                                        <button type="submit" name="status" value="pledged" style="padding: 0.75rem 1rem; border: none; background: transparent; cursor: pointer; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.8rem; font-weight: 600; color: var(--accent);">Mark Pending</button>
                                                        <button type="submit" name="status" value="received" style="padding: 0.75rem 1rem; border: none; background: transparent; cursor: pointer; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.8rem; font-weight: 600; color: var(--primary);">Mark Received</button>
                                                        <button type="submit" name="status" value="distributed" style="padding: 0.75rem 1rem; border: none; background: transparent; cursor: pointer; text-align: left; font-size: 0.8rem; font-weight: 600; color: var(--secondary);">Mark Distributed</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if (count($inkind_pledges) > 5): ?>
                            <div style="padding: 1.5rem; text-align: center; border-top: 1px solid var(--border);">
                                <button onclick="toggleRows('pledges-body', this)" class="btn btn-text" style="font-weight: 600; color: var(--primary);">Reveal Actionable Transfers <i class="fa-solid fa-chevron-down" style="margin-left: 0.5rem;"></i></button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Transaction Audit Tab -->
                <div id="audit-content" class="tab-content" style="display: <?= $active_tab === 'audit' ? 'block' : 'none' ?>;">
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
                            <input type="hidden" name="tab" value="audit">
                            <select name="view" class="form-control" style="width: auto; font-size: 0.85rem;">
                                <option value="all" <?= $view_mod === 'all' ? 'selected' : '' ?>>🔍 Stream All Transactions</option>
                                <option value="hidden" <?= $view_mod === 'hidden' ? 'selected' : '' ?>>👁️‍🗨️ Ghosted Transactions</option>
                                <option value="flagged_or_voided" <?= $view_mod === 'flagged_or_voided' ? 'selected' : '' ?>>⚠️ Flagged Security Breaches</option>
                            </select>
                            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Run Audit Filter</button>
                        </form>
                    </div>

                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--border); background: var(--surface); font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">
                                    <th style="padding: 1.25rem 1.5rem;">Time Vector</th>
                                    <th style="padding: 1.25rem 1.5rem;">Value</th>
                                    <th style="padding: 1.25rem 1.5rem;">Moderation Tags</th>
                                    <th style="padding: 1.25rem 1.5rem; text-align: right;">Execute Security Protocol</th>
                                </tr>
                            </thead>
                            <tbody id="audit-body">
                                <?php if (empty($mod_txns)): ?>
                                    <tr><td colspan="4" style="padding: 3rem; text-align: center; color: var(--text-muted);">No flagged transactions isolated. System Secure.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($mod_txns as $index => $txn): ?>
                                        <tr class="data-row" style="border-bottom: 1px solid var(--border); transition: var(--transition); <?= $index >= 5 ? 'display: none;' : '' ?>" onmouseover="this.style.background='var(--background)'" onmouseout="this.style.background='transparent'">
                                            <td style="padding: 1.25rem 1.5rem; font-size: 0.85rem;">
                                                <div style="color: var(--text-main); font-weight: 600;"><?= date('Y-m-d H:i', strtotime($txn['created_at'])) ?></div>
                                                <div style="color: var(--text-muted); font-size: 0.75rem; font-family: monospace;"><?= $txn['transaction_id'] ?></div>
                                            </td>
                                            <td style="padding: 1.25rem 1.5rem;">
                                                <div style="font-weight: 800; color: var(--text-main); font-size: 1.05rem;">KSh <?= number_format($txn['amount'], 2) ?></div>
                                                <div style="color: var(--text-muted); font-size: 0.75rem;"><i class="fa-solid fa-user" style="margin-right: 0.25rem;"></i> <?= h($txn['donor_name']) ?></div>
                                            </td>
                                            <td style="padding: 1.25rem 1.5rem;">
                                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                                    <?php if ($txn['hidden_in_ledger'] == 1): ?>
                                                        <span style="background: rgba(30, 41, 59, 0.1); color: var(--text-muted); padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.65rem; font-weight: 700;">GHOSTED</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php 
                                                        $modColor = 'var(--secondary)';
                                                        $modBg = 'rgba(16, 185, 129, 0.1)';
                                                        $modText = 'CLEARED';
                                                        if ($txn['moderation_state'] === 'flagged') { $modColor = 'var(--accent)'; $modBg = 'rgba(245, 158, 11, 0.1)'; $modText = '⚠️ FLAGGED'; }
                                                        if ($txn['moderation_state'] === 'voided') { $modColor = 'var(--danger)'; $modBg = 'rgba(239, 68, 68, 0.1)'; $modText = '🚫 VOIDED'; }
                                                    ?>
                                                    <span style="background: <?= $modBg ?>; color: <?= $modColor ?>; padding: 0.25rem 0.65rem; border-radius: 999px; font-size: 0.65rem; font-weight: 700; border: 1px solid <?= $modColor ?>;">
                                                        <?= $modText ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td style="padding: 1.25rem 1.5rem; text-align: right;">
                                                <?php if (($_SESSION['role_level'] ?? 1) >= 2): ?>
                                                <form action="<?= BASE_URL ?>/actions/admin_moderate_donation.php" method="POST" style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end; margin: 0;">
                                                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                                    <input type="hidden" name="donation_id" value="<?= $txn['id'] ?>">
                                                    <input type="hidden" name="redirect" value="finance_controls">

                                                    <?php if ($txn['hidden_in_ledger'] == 1): ?>
                                                        <button type="submit" name="action_type" value="unhide" class="btn btn-outline" style="padding: 0.35rem 0.65rem; font-size: 0.7rem;">Unhide (Ledger)</button>
                                                    <?php else: ?>
                                                        <button type="submit" name="action_type" value="hide" class="btn btn-outline" style="padding: 0.35rem 0.65rem; font-size: 0.7rem;">Ghost (Ledger)</button>
                                                    <?php endif; ?>

                                                    <?php if ($txn['moderation_state'] !== 'normal'): ?>
                                                        <button type="submit" name="action_type" value="reset" class="btn btn-primary" style="padding: 0.35rem 0.65rem; font-size: 0.7rem; background: var(--secondary);">Restore</button>
                                                    <?php endif; ?>

                                                    <?php if ($txn['moderation_state'] !== 'flagged'): ?>
                                                        <button type="submit" name="action_type" value="flag" class="btn btn-outline" style="padding: 0.35rem 0.65rem; font-size: 0.7rem; color: var(--accent); border-color: var(--accent);">Flag Security</button>
                                                    <?php endif; ?>

                                                    <?php if ($txn['moderation_state'] !== 'voided'): ?>
                                                        <button type="submit" name="action_type" value="void" class="btn btn-outline" style="padding: 0.35rem 0.65rem; font-size: 0.7rem; color: var(--danger); border-color: var(--danger);">Void Target</button>
                                                    <?php endif; ?>
                                                </form>
                                                <?php else: ?>
                                                <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">Read-only mode</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if (count($mod_txns) > 5): ?>
                            <div style="padding: 1.5rem; text-align: center; border-top: 1px solid var(--border);">
                                <button onclick="toggleRows('audit-body', this)" class="btn btn-text" style="font-weight: 600; color: var(--danger);">Display All Flagged Vectors <i class="fa-solid fa-chevron-down" style="margin-left: 0.5rem;"></i></button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- UI Logic Scripts -->
            <script>
            function showTab(tabName) {
                document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
                document.querySelectorAll('.tab-button').forEach(b => { b.style.background = 'transparent'; b.style.color = 'var(--text-muted)'; b.style.border = '1px solid var(--border)'; });
                document.getElementById(tabName + '-content').style.display = 'block';
                const btn = document.getElementById(tabName + '-tab');
                btn.style.background = 'var(--primary)'; btn.style.color = 'white'; btn.style.border = '1px solid var(--primary)';
                const url = new URL(window.location); url.searchParams.set('tab', tabName); window.history.pushState({}, '', url);
            }

            function toggleRows(bodyId, btn) {
                const tbody = document.getElementById(bodyId);
                const rows = tbody.querySelectorAll('tr.data-row');
                if(tbody.dataset.expanded === "true") {
                    tbody.dataset.expanded = "false";
                    rows.forEach((row, index) => { if(index >= 5) row.style.display = 'none'; });
                    btn.innerHTML = btn.innerHTML.replace('Collapse', 'Expand').replace('fa-chevron-up', 'fa-chevron-down').replace('Hide', 'Display').replace('Hide', 'View');
                } else {
                    tbody.dataset.expanded = "true";
                    rows.forEach(row => row.style.display = '');
                    btn.innerHTML = btn.innerHTML.replace('Expand', 'Collapse').replace('fa-chevron-down', 'fa-chevron-up').replace('Display', 'Hide').replace('Reveal', 'Hide');
                }
            }

            function toggleStatusMenu(btn, pledgeId) {
                const menu = document.getElementById('menu-' + pledgeId);
                menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
            }

            document.addEventListener('click', function(e) {
                if (!e.target.closest('button[onclick*="toggleStatusMenu"]') && !e.target.closest('[id^="menu-"]')) {
                    document.querySelectorAll('[id^="menu-"]').forEach(m => m.style.display = 'none');
                }
            });
            </script>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
