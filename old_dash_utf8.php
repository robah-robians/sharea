<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /share_hope/login.php");
    exit;
}

$role = $_SESSION['user_role'] ?? '';
if ($role !== 'admin') {
    $redirect = $role ? "/share_hope/{$role}/dashboard.php" : "/share_hope/login.php";
    header("Location: {$redirect}");
    exit;
}

// ---------------------------------------------------------
// PRIVATE SECURE ZONE: ADMIN MASTER PIN REQUIRED
// ---------------------------------------------------------
if (!isset($_SESSION['admin_unlocked']) || $_SESSION['admin_unlocked'] !== true) {
    if (isset($_POST['admin_pin']) && $_POST['admin_pin'] === '1234') {
        $_SESSION['admin_unlocked'] = true;
        header("Location: /share_hope/admin/dashboard.php");
        exit;
    }

    $error = isset($_POST['admin_pin']) ? "Invalid Security PIN." : "";
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="container" style="padding: 6rem 1.5rem; max-width: 400px;">
        <div
            style="background: var(--surface); padding: 3rem 2rem; border-radius: var(--radius-lg); text-align: center; box-shadow: var(--shadow-lg); border: 2px solid var(--danger);">
            <i class="fa-solid fa-user-shield" style="font-size: 3.5rem; color: var(--danger); margin-bottom: 1rem;"></i>
            <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem; color: var(--danger);">Restricted Access</h2>
            <p class="text-muted" style="margin-bottom: 2rem; font-size: 0.875rem;">Please enter your Master Admin PIN to
                unlock the dashboard.</p>

            <?php if ($error): ?>
                <div
                    style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 0.75rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.875rem;">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="password" name="admin_pin" class="form-control" placeholder="****"
                    style="text-align: center; letter-spacing: 0.5rem; font-size: 1.5rem; margin-bottom: 1.5rem;" required
                    autofocus maxlength="4">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Unlock Portal</button>
            </form>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

require_once __DIR__ . '/../includes/header.php';

// Fetch pending NGOs
$stmt = $pdo->query("SELECT n.*, u.name, u.email, u.phone FROM ngos n JOIN users u ON n.user_id = u.id WHERE n.is_verified = 0 ORDER BY n.created_at ASC");
$pending_ngos = $stmt->fetchAll();

// Fetch recent transactions system-wide
$stmt = $pdo->query("SELECT d.*, c.title, u.name as donor_name, n.name as ngo_name, c.ngo_id 
                     FROM donations d 
                     JOIN campaigns c ON d.campaign_id = c.id 
                     JOIN ngos ngo ON c.ngo_id = ngo.id
                     JOIN users n ON ngo.user_id = n.id
                     LEFT JOIN users u ON d.donor_id = u.id
                     ORDER BY d.created_at DESC LIMIT 20");
$transactions = $stmt->fetchAll();

// System stats
$stats_stmt = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM ngos WHERE is_verified = 1) as verified_ngos,
    (SELECT SUM(amount) FROM donations WHERE status = 'completed') as total_donations
");
$stats = $stats_stmt->fetch();

// --- Chart Data Fetching ---
$daily_donations_stmt = $pdo->query("SELECT DATE(created_at) as date, SUM(amount) as total FROM donations WHERE status = 'completed' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date ASC");
$daily_donations = $daily_donations_stmt->fetchAll(PDO::FETCH_ASSOC);

$payment_methods_stmt = $pdo->query("SELECT payment_method, SUM(amount) as total FROM donations WHERE status = 'completed' GROUP BY payment_method");
$payment_methods = $payment_methods_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all system users (donors and NGOs)
$users_stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 50");
$all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container" style="padding: 4rem 0;">
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
        <h1 style="font-size: 2rem; margin: 0;">Admin Control Panel</h1>

        <!-- Maintenance Mode Toggle -->
        <div
            style="display: flex; align-items: center; gap: 1rem; background: var(--surface); padding: 0.75rem 1.5rem; border-radius: 999px; border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500;">
                <span style="font-size: 0.875rem; color: var(--text-muted);">Status:</span>
                <?php if (file_exists(__DIR__ . '/../.maintenance_lock')): ?>
                    <span style="color: var(--danger); display: flex; align-items: center; gap: 0.25rem;"><i
                            class="fa-solid fa-lock"></i> Maintenance Mode (Users Blocked)</span>
                <?php else: ?>
                    <span style="color: var(--secondary); display: flex; align-items: center; gap: 0.25rem;"><i
                            class="fa-solid fa-globe"></i> Live (Normal Operation)</span>
                <?php endif; ?>
            </div>

            <form action="/share_hope/actions/toggle_maintenance.php" method="POST"
                style="margin: 0; padding-left: 1rem; border-left: 1px solid var(--border);">
                <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                <?php if (file_exists(__DIR__ . '/../.maintenance_lock')): ?>
                    <input type="hidden" name="action" value="disable">
                    <button type="submit" class="btn btn-outline"
                        style="padding: 0.25rem 0.75rem; font-size: 0.75rem; color: var(--secondary); border-color: var(--secondary);"><i
                            class="fa-solid fa-play"></i> Go Live</button>
                <?php else: ?>
                    <input type="hidden" name="action" value="enable">
                    <button type="submit" class="btn btn-outline"
                        style="padding: 0.25rem 0.75rem; font-size: 0.75rem; color: var(--danger); border-color: var(--danger);"><i
                            class="fa-solid fa-pause"></i> Enter Maintenance Mode</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- System Stats -->
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
        <div
            style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1.5rem;">
            <div
                style="width: 60px; height: 60px; background: rgba(79, 70, 229, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Total Users</div>
                <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-main);">
                    <?= number_format($stats['total_users']) ?>
                </div>
            </div>
        </div>
        <div
            style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1.5rem;">
            <div
                style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); color: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fa-solid fa-building-circle-check"></i>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Verified NGOs
                </div>
                <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-main);">
                    <?= number_format($stats['verified_ngos']) ?>
                </div>
            </div>
        </div>
        <div
            style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1.5rem;">
            <div
                style="width: 60px; height: 60px; background: rgba(245, 158, 11, 0.1); color: var(--accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fa-solid fa-vault"></i>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">System Volume
                </div>
                <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-main);">KSh
                    <?= number_format($stats['total_donations'] ?? 0, 2) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- System Users Management -->
    <div
        style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; margin-bottom: 3rem;">
        <div
            style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">System Users Overview</h3>
            <span
                style="background: var(--primary); color: white; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;"><?= number_format($stats['total_users']) ?>
                Registered</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="responsive-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr
                        style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.875rem; background: var(--background);">
                        <th style="padding: 1rem 1.5rem;">User Name</th>
                        <th style="padding: 1rem 1.5rem;">Contact Email</th>
                        <th style="padding: 1rem 1.5rem;">Role</th>
                        <th style="padding: 1rem 1.5rem;">Date Joined</th>
                        <th style="padding: 1rem 1.5rem;">View Profile</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $usr): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td data-label="User Name" style="padding: 1rem 1.5rem; font-weight: 500;">
                                <?= h($usr['name']) ?>
                            </td>
                            <td data-label="Contact Email"
                                style="padding: 1rem 1.5rem; color: var(--text-muted); font-size: 0.875rem;">
                                <?= h($usr['email']) ?>
                            </td>
                            <td data-label="Role" style="padding: 1rem 1.5rem;">
                                <span
                                    style="text-transform: uppercase; font-size: 0.7rem; font-weight: 700; color: <?= $usr['role'] === 'admin' ? 'var(--danger)' : ($usr['role'] === 'ngo' ? 'var(--secondary)' : 'var(--primary)') ?>; border: 1px solid <?= $usr['role'] === 'admin' ? 'var(--danger)' : ($usr['role'] === 'ngo' ? 'var(--secondary)' : 'var(--primary)') ?>; padding: 0.1rem 0.4rem; border-radius: 4px;">
                                    <?= h($usr['role']) ?>
                                </span>
                            </td>
                            <td data-label="Date Joined"
                                style="padding: 1rem 1.5rem; white-space: nowrap; font-size: 0.875rem; color: var(--text-muted);">
                                <?= date('M j, Y', strtotime($usr['created_at'])) ?>
                            </td>
                            <td data-label="View Profile" style="padding: 1rem 1.5rem;">
                                <a href="/share_hope/admin/view_user.php?id=<?= $usr['id'] ?>" class="btn btn-outline"
                                    style="padding: 0.25rem 0.75rem; font-size: 0.75rem;"><i class="fa-solid fa-eye"></i>
                                    Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- NGO Approvals -->
    <div
        style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; margin-bottom: 3rem;">
        <div
            style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Pending NGO Approvals</h3>
            <?php if (count($pending_ngos) > 0): ?>
                <span
                    style="background: var(--danger); color: white; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;"><?= count($pending_ngos) ?>
                    Pending</span>
            <?php endif; ?>
        </div>

        <?php if (empty($pending_ngos)): ?>
            <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
                <i class="fa-solid fa-check-double"
                    style="font-size: 2rem; color: var(--secondary); margin-bottom: 1rem;"></i>
                <p>All clear! No NGOs waiting for approval.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="responsive-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr
                            style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.875rem; background: var(--background);">
                            <th style="padding: 1rem 1.5rem;">Organization</th>
                            <th style="padding: 1rem 1.5rem;">Contact</th>
                            <th style="padding: 1rem 1.5rem;">Verification Doc</th>
                            <th style="padding: 1rem 1.5rem;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_ngos as $ngo): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td data-label="Organization" style="padding: 1rem 1.5rem; font-weight: 500;">
                                    <a href="/share_hope/ngo_profile.php?id=<?= $ngo['id'] ?>" style="color: var(--primary);">
                                        <?= h($ngo['name']) ?>
                                    </a>
                                </td>
                                <td data-label="Contact"
                                    style="padding: 1rem 1.5rem; color: var(--text-muted); font-size: 0.875rem;">
                                    <?= h($ngo['email']) ?><br><?= h($ngo['phone']) ?>
                                </td>
                                <td data-label="Verification Doc" style="padding: 1rem 1.5rem;">
                                    <?php if ($ngo['verification_doc']): ?>
                                        <a href="<?= h($ngo['verification_doc']) ?>" target="_blank" class="text-primary"
                                            style="font-size: 0.875rem;"><i class="fa-solid fa-file-pdf"></i> View Doc</a>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Action" style="padding: 1rem 1.5rem; display: flex; gap: 0.5rem;">
                                    <form action="/share_hope/actions/admin_approve_ngo.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                        <input type="hidden" name="ngo_id" value="<?= $ngo['id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--secondary); color: white; border: none; cursor: pointer;">Approve</button>
                                    </form>
                                    <form action="/share_hope/actions/admin_approve_ngo.php" method="POST"
                                        onsubmit="return confirm('Are you sure you want to reject and delete this application?');">
                                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                        <input type="hidden" name="ngo_id" value="<?= $ngo['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button class="btn"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem; background: var(--danger); color: white; border: none; cursor: pointer;">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Reports & Analytics -->
    <div style="margin-bottom: 3rem;">
        <h3 style="margin-bottom: 1.5rem;">Reports & Analytics</h3>
        <?php if (empty($daily_donations) && empty($payment_methods)): ?>
            <div
                style="background: var(--surface); padding: 3rem; text-align: center; border-radius: var(--radius-lg); border: 1px solid var(--border);">
                <i class="fa-solid fa-chart-line" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                <p class="text-muted">Not enough donation data yet to generate reports.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div
                    style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                    <h4 style="margin-top: 0; margin-bottom: 1rem; color: var(--text-main); font-size: 1rem;">7-Day Donation
                        Volume (KSh)</h4>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="dailyDonationsChart"></canvas>
                    </div>
                </div>
                <div
                    style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                    <h4 style="margin-top: 0; margin-bottom: 1rem; color: var(--text-main); font-size: 1rem;">Revenue by
                        Payment Method</h4>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="paymentMethodChart"></canvas>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- System Transactions -->
    <div
        style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
        <div
            style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Recent Global Transactions</h3>
            <a href="/share_hope/actions/export_admin.php" class="btn btn-outline"
                style="padding: 0.5rem 1rem; font-size: 0.875rem;"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
        </div>
        <div style="overflow-x: auto;">
            <table class="responsive-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr
                        style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.875rem; background: var(--background);">
                        <th style="padding: 1rem 1.5rem;">TXN ID</th>
                        <th style="padding: 1rem 1.5rem;">Donor</th>
                        <th style="padding: 1rem 1.5rem;">NGO &amp; Campaign</th>
                        <th style="padding: 1rem 1.5rem;">Amount</th>
                        <th style="padding: 1rem 1.5rem;">Method / Status</th>
                        <th style="padding: 1rem 1.5rem;">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                        <tr style="border-bottom: 1px solid var(--border); font-size: 0.875rem;">
                            <td data-label="TXN ID"
                                style="padding: 1rem 1.5rem; color: var(--text-muted); font-family: monospace;">
                                <?= h($txn['transaction_id']) ?>
                            </td>
                            <td data-label="Donor" style="padding: 1rem 1.5rem;">
                                <?php if ($txn['is_anonymous']): ?>
                                    <em>Anonymous</em>
                                <?php elseif ($txn['donor_id']): ?>
                                    <a href="/share_hope/admin/view_user.php?id=<?= $txn['donor_id'] ?>"
                                        style="color: var(--primary); font-weight: 500;">
                                        <?= h($txn['donor_name'] ?? 'Guest') ?>
                                    </a>
                                <?php else: ?>
                                    <?= h($txn['donor_name'] ?? 'Guest') ?>
                                <?php endif; ?>
                            </td>
                            <td data-label="NGO & Campaign" style="padding: 1rem 1.5rem;">
                                <div style="font-weight: 500;">
                                    <a href="/share_hope/ngo_profile.php?id=<?= $txn['ngo_id'] ?>"
                                        style="color: var(--text-main);">
                                        <?= h($txn['ngo_name']) ?>
                                    </a>
                                </div>
                                <div style="color: var(--text-muted); font-size: 0.75rem;">
                                    <?= h(mb_substr($txn['title'], 0, 40)) ?>...
                                </div>
                            </td>
                            <td data-label="Amount" style="padding: 1rem 1.5rem; font-weight: 600;">KSh
                                <?= number_format($txn['amount'], 2) ?>
                            </td>
                            <td data-label="Method / Status" style="padding: 1rem 1.5rem;">
                                <span
                                    style="text-transform: uppercase; font-size: 0.7rem; font-weight: 700; color: var(--primary); border: 1px solid var(--primary); padding: 0.1rem 0.4rem; border-radius: 4px; margin-right: 0.5rem;">
                                    <?= h($txn['payment_method']) ?>
                                </span>
                                <span style="color: var(--secondary);"><i class="fa-solid fa-check"></i></span>
                            </td>
                            <td data-label="Receipt" style="padding: 1rem 1.5rem;">
                                <a href="/share_hope/receipt.php?id=<?= $txn['id'] ?>" class="btn btn-outline"
                                    style="padding: 0.25rem 0.75rem; font-size: 0.75rem;"><i
                                        class="fa-solid fa-file-pdf"></i> View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const dailyData = <?= json_encode($daily_donations) ?>;
        const paymentData = <?= json_encode($payment_methods) ?>;

        // Daily Donations Chart
        const ctxDaily = document.getElementById('dailyDonationsChart');
        if (ctxDaily && dailyData.length > 0) {
            new Chart(ctxDaily, {
                type: 'line',
                data: {
                    labels: dailyData.map(d => d.date),
                    datasets: [{
                        label: 'Donation Amount (KSh)',
                        data: dailyData.map(d => parseFloat(d.total)),
                        borderColor: '#4F46E5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }

        // Payment Methods Chart
        const ctxPayment = document.getElementById('paymentMethodChart');
        if (ctxPayment && paymentData.length > 0) {
            new Chart(ctxPayment, {
                type: 'doughnut',
                data: {
                    labels: paymentData.map(d => d.payment_method.toUpperCase()),
                    datasets: [{
                        data: paymentData.map(d => parseFloat(d.total)),
                        backgroundColor: ['#10B981', '#F59E0B', '#3B82F6', '#6366F1'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
