<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /share_hope/login.php");
    exit;
}

$role = $_SESSION['user_role'] ?? '';
if ($role !== 'ngo') {
    $redirect = $role ? "/share_hope/{$role}/dashboard.php" : "/share_hope/login.php";
    header("Location: {$redirect}");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get NGO details
$stmt = $pdo->prepare("SELECT * FROM ngos WHERE user_id = ?");
$stmt->execute([$user_id]);
$ngo = $stmt->fetch();

if (!$ngo || !$ngo['is_verified']) {
    echo "<div class='container' style='padding:4rem 0;'>
            <div style='background: var(--accent); color: white; padding: 2rem; border-radius: var(--radius-md); text-align: center;'>
                <i class='fa-solid fa-hourglass-half' style='font-size: 3rem; margin-bottom: 1rem;'></i>
                <h2>Account Pending Verification</h2>
                <p>Your NGO account is currently under review by our administration team. You will be able to create campaigns once verified.</p>
                <a href='/actions/logout_action.php' class='btn btn-outline' style='border-color: white; color: white; margin-top: 1rem;'>Log Out</a>
            </div>
          </div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$ngo_id = $ngo['id'];

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total_camps, SUM(current_amount) as total_raised FROM campaigns WHERE ngo_id = ?");
$stmt->execute([$ngo_id]);
$stats = $stmt->fetch();

// Campaigns list
$stmt = $pdo->prepare("SELECT * FROM campaigns WHERE ngo_id = ? ORDER BY created_at DESC");
$stmt->execute([$ngo_id]);
$campaigns = $stmt->fetchAll();

// --- Chart Data Fetching ---
$daily_donations_stmt = $pdo->prepare("
    SELECT DATE(d.created_at) as date, SUM(d.amount) as total 
    FROM donations d 
    JOIN campaigns c ON d.campaign_id = c.id 
    WHERE c.ngo_id = ? AND d.status = 'completed' AND d.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
    GROUP BY DATE(d.created_at) ORDER BY date ASC
");
$daily_donations_stmt->execute([$ngo_id]);
$daily_donations = $daily_donations_stmt->fetchAll(PDO::FETCH_ASSOC);

$campaign_perf_stmt = $pdo->prepare("
    SELECT title, current_amount 
    FROM campaigns 
    WHERE ngo_id = ? AND current_amount > 0
");
$campaign_perf_stmt->execute([$ngo_id]);
$campaign_performance = $campaign_perf_stmt->fetchAll(PDO::FETCH_ASSOC);


// Incoming In-Kind Pledges
$stmt = $pdo->prepare("SELECT i.*, c.title as campaign_title 
                       FROM inkind_donations i 
                       JOIN campaigns c ON i.campaign_id = c.id 
                       WHERE c.ngo_id = ? ORDER BY i.status ASC, i.created_at DESC");
$stmt->execute([$ngo_id]);
$inkind_pledges = $stmt->fetchAll();
?>

<div class="container" style="padding: 4rem 0;">
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
        <h1 style="font-size: 2rem; margin: 0;">Organization Dashboard</h1>
        <a href="/share_hope/ngo/create_campaign.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New
            Campaign</a>
    </div>

    <!-- Quick Stats -->
    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
        <div
            style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1.5rem;">
            <div
                style="width: 60px; height: 60px; background: rgba(79, 70, 229, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Total Campaigns
                </div>
                <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-main);">
                    <?= intval($stats['total_camps']) ?>
                </div>
            </div>
        </div>

        <div
            style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 1.5rem;">
            <div
                style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); color: var(--secondary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-muted); text-transform: uppercase;">Total Funds
                    Raised</div>
                <div style="font-size: 1.75rem; font-weight: 700; color: var(--text-main);">
                    KSh <?= number_format($stats['total_raised'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Reports & Analytics -->
    <div style="margin-bottom: 3rem;">
        <h3 style="margin-bottom: 1.5rem;">Campaign Analytics</h3>
        <?php if (empty($daily_donations) && empty($campaign_performance)): ?>
            <div
                style="background: var(--surface); padding: 3rem; text-align: center; border-radius: var(--radius-lg); border: 1px solid var(--border);">
                <i class="fa-solid fa-chart-pie" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                <p class="text-muted">Not enough donation data yet to generate reports.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                <div
                    style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                    <h4 style="margin-top: 0; margin-bottom: 1rem; color: var(--text-main); font-size: 1rem;">7-Day Donation
                        Volume (KSh)</h4>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="ngoDailyChart"></canvas>
                    </div>
                </div>
                <div
                    style="background: var(--surface); padding: 1.5rem; border-radius: var(--radius-md); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                    <h4 style="margin-top: 0; margin-bottom: 1rem; color: var(--text-main); font-size: 1rem;">Funds by
                        Campaign</h4>
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="ngoCampaignChart"></canvas>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Campaigns Management -->
    <div
        style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden;">
        <div
            style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Manage Campaigns</h3>
            <a href="/share_hope/actions/export_ngo.php" class="btn btn-outline"
                style="padding: 0.5rem 1rem; font-size: 0.875rem;"><i class="fa-solid fa-file-csv"></i> Export Donors
                Data</a>
        </div>

        <?php if (empty($campaigns)): ?>
            <div style="padding: 4rem; text-align: center;">
                <i class="fa-solid fa-folder-open" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                <h4>No campaigns created yet.</h4>
                <a href="/share_hope/ngo/create_campaign.php" class="btn btn-outline" style="margin-top: 1rem;">Start your
                    first
                    campaign</a>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="responsive-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr
                            style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.875rem; background: var(--background);">
                            <th style="padding: 1rem 1.5rem;">Title</th>
                            <th style="padding: 1rem 1.5rem;">Goal</th>
                            <th style="padding: 1rem 1.5rem;">Raised</th>
                            <th style="padding: 1rem 1.5rem;">Status</th>
                            <th style="padding: 1rem 1.5rem;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $camp): ?>
                            <?php $percent = ($camp['goal_amount'] > 0) ? min(100, round(($camp['current_amount'] / $camp['goal_amount']) * 100)) : 0; ?>
                            <tr style="border-bottom: 1px solid var(--border); transition: background 0.3s;"
                                onmouseover="this.style.background='var(--background)'"
                                onmouseout="this.style.background='transparent'">
                                <td data-label="Title"
                                    style="padding: 1rem 1.5rem; font-weight: 500; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= h($camp['title']) ?>
                                </td>
                                <td data-label="Goal" style="padding: 1rem 1.5rem; color: var(--text-muted);">
                                    KSh <?= number_format($camp['goal_amount']) ?></td>
                                <td data-label="Raised" style="padding: 1rem 1.5rem;">
                                    <div style="font-weight: 600;">KSh <?= number_format($camp['current_amount']) ?>
                                        (<?= $percent ?>%)</div>
                                    <div
                                        style="width: 100%; height: 4px; background: var(--border); border-radius: 2px; margin-top: 5px;">
                                        <div
                                            style="width: <?= $percent ?>%; height: 100%; background: var(--primary); border-radius: 2px;">
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Status" style="padding: 1rem 1.5rem;">
                                    <span
                                        style="padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; background: <?= $camp['status'] === 'active' ? 'rgba(16, 185, 129, 0.1)' : 'var(--border)' ?>; color: <?= $camp['status'] === 'active' ? 'var(--secondary)' : 'var(--text-muted)' ?>;">
                                        <?= ucfirst($camp['status']) ?>
                                    </span>
                                </td>
                                <td data-label="Actions"
                                    style="padding: 1rem 1.5rem; display: flex; gap: 0.5rem; align-items: center;">
                                    <a href="/share_hope/donate.php?campaign_id=<?= $camp['id'] ?>" class="text-primary"
                                        style="font-size: 0.875rem; font-weight: 500;"><i class="fa-solid fa-eye"></i> View</a>
                                    <a href="/share_hope/ngo/edit_campaign.php?id=<?= $camp['id'] ?>" class="text-muted"
                                        style="font-size: 0.875rem; font-weight: 500;"><i class="fa-solid fa-pen-to-square"></i>
                                        Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Incoming In-Kind Pledges -->
    <div
        style="background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); overflow: hidden; margin-top: 3rem;">
        <div
            style="padding: 1.5rem; border-bottom: 1px solid var(--border); background: var(--background); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Received Item Pledges</h3>
        </div>

        <?php if (empty($inkind_pledges)): ?>
            <div style="padding: 4rem; text-align: center;">
                <i class="fa-solid fa-box-open" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                <h4>No physical items pledged yet.</h4>
                <p class="text-muted">When donors pledge food, clothes or supplies, they will appear here.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="responsive-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr
                            style="border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 0.875rem; background: var(--background);">
                            <th style="padding: 1rem 1.5rem;">Date</th>
                            <th style="padding: 1rem 1.5rem;">Campaign</th>
                            <th style="padding: 1rem 1.5rem;">Donor Info</th>
                            <th style="padding: 1rem 1.5rem;">Item Details</th>
                            <th style="padding: 1rem 1.5rem;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inkind_pledges as $pledge): ?>
                            <tr style="border-bottom: 1px solid var(--border); transition: background 0.3s; font-size: 0.875rem;"
                                onmouseover="this.style.background='var(--background)'"
                                onmouseout="this.style.background='transparent'">
                                <td data-label="Date" style="padding: 1rem 1.5rem; white-space: nowrap;">
                                    <?= date('M j, Y', strtotime($pledge['created_at'])) ?>
                                </td>
                                <td data-label="Campaign"
                                    style="padding: 1rem 1.5rem; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500;">
                                    <?= h($pledge['campaign_title']) ?>
                                </td>
                                <td data-label="Donor Info" style="padding: 1rem 1.5rem;">
                                    <strong><?= h($pledge['donor_name'] ?: 'Anonymous') ?></strong><br>
                                    <a href="mailto:<?= h($pledge['donor_email']) ?>" style="color: var(--primary);"><i
                                            class="fa-solid fa-envelope"></i> <?= h($pledge['donor_email']) ?></a><br>
                                    <a href="tel:<?= h($pledge['donor_phone']) ?>" style="color: var(--text-muted);"><i
                                            class="fa-solid fa-phone"></i> <?= h($pledge['donor_phone']) ?></a>
                                </td>
                                <td data-label="Item Details" style="padding: 1rem 1.5rem;">
                                    <span
                                        style="background: var(--background); padding: 0.15rem 0.4rem; border-radius: 4px; border: 1px solid var(--border); display: inline-block; margin-bottom: 0.25rem; font-size: 0.75rem;"><i
                                            class="fa-solid fa-tag"></i> <?= h($pledge['item_category']) ?></span><br>
                                    <strong>Qty: <?= h($pledge['quantity']) ?></strong><br>
                                    <span style="color: var(--text-muted);"><?= h($pledge['item_description']) ?></span>
                                </td>
                                <td data-label="Status" style="padding: 1rem 1.5rem;">
                                    <!-- Simple inline form to update status -->
                                    <form action="/share_hope/actions/update_pledge_status.php" method="POST"
                                        style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                        <input type="hidden" name="pledge_id" value="<?= $pledge['id'] ?>">
                                        <select name="status" class="form-control"
                                            style="font-size: 0.75rem; padding: 0.25rem 0.5rem; width: auto; max-width: 120px;"
                                            onchange="this.form.submit()">
                                            <option value="pledged" <?= $pledge['status'] === 'pledged' ? 'selected' : '' ?>>
                                                Pledged</option>
                                            <option value="received" <?= $pledge['status'] === 'received' ? 'selected' : '' ?>>
                                                Received</option>
                                            <option value="distributed" <?= $pledge['status'] === 'distributed' ? 'selected' : '' ?>>Distributed</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const dailyData = <?= json_encode($daily_donations) ?>;
        const campaignData = <?= json_encode($campaign_performance) ?>;

        const ctxDaily = document.getElementById('ngoDailyChart');
        if (ctxDaily && dailyData.length > 0) {
            new Chart(ctxDaily, {
                type: 'line',
                data: {
                    labels: dailyData.map(d => d.date),
                    datasets: [{
                        label: 'Donation Amount (KSh)',
                        data: dailyData.map(d => parseFloat(d.total)),
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
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

        const ctxCamp = document.getElementById('ngoCampaignChart');
        if (ctxCamp && campaignData.length > 0) {
            new Chart(ctxCamp, {
                type: 'pie',
                data: {
                    labels: campaignData.map(d => d.title),
                    datasets: [{
                        data: campaignData.map(d => parseFloat(d.current_amount)),
                        backgroundColor: ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
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