<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';


$user_id = $_SESSION['user_id'];

// Get NGO ID
$stmt = $pdo->prepare("SELECT id FROM ngos WHERE user_id = ?");
$stmt->execute([$user_id]);
$ngo = $stmt->fetch();

if (!$ngo) {
    $_SESSION['error'] = "NGO profile not found.";
    header("Location: " . BASE_URL . "/ngo/dashboard.php");
    exit;
}

// Get donors with their donation history
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(u.name, 'Anonymous') as donor_name,
        u.email as donor_email,
        u.phone as donor_phone,
        COUNT(d.id) as donation_count,
        SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END) as total_donated,
        MAX(d.created_at) as last_donation,
        MIN(d.created_at) as first_donation,
        u.id as user_id
    FROM donations d
    JOIN campaigns c ON d.campaign_id = c.id
    LEFT JOIN users u ON d.donor_id = u.id
    WHERE c.ngo_id = ?
    GROUP BY COALESCE(u.id, 'anonymous')
    ORDER BY total_donated DESC
");
$stmt->execute([$ngo['id']]);
$donors = $stmt->fetchAll();

// Get donor statistics
$total_donors = count($donors);
$total_raised = array_sum(array_column($donors, 'total_donated'));
$avg_donation = $total_donors > 0 ? $total_raised / $total_donors : 0;
?>

<div class="admin-layout">
    <?php include __DIR__ . '/includes/ngo_nav.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1><i class="fa-solid fa-users"></i> Donor Management</h1>
            <div style="display: flex; gap: 1rem;">
                <button onclick="exportDonors()" class="btn btn-outline">
                    <i class="fa-solid fa-download"></i> Export CSV
                </button>
                <span style="color: var(--text-muted);"><?= $total_donors ?> total donors</span>
            </div>
        </div>

        <!-- Donor Statistics -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700;"><?= $total_donors ?></h3>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Total Donors</p>
                    </div>
                    <i class="fa-solid fa-users" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700;">$<?= number_format($total_raised, 2) ?></h3>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Total Raised</p>
                    </div>
                    <i class="fa-solid fa-dollar-sign" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 2rem; border-radius: var(--radius-lg);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="margin: 0; font-size: 2rem; font-weight: 700;">$<?= number_format($avg_donation, 2) ?></h3>
                        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Avg per Donor</p>
                    </div>
                    <i class="fa-solid fa-chart-line" style="font-size: 2.5rem; opacity: 0.7;"></i>
                </div>
            </div>
        </div>

        <!-- Donors Table -->
        <div class="donors-section" style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                <h3 style="margin: 0;">Donor Directory</h3>
            </div>
            
            <?php if(empty($donors)): ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                    <i class="fa-solid fa-users" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3>No Donors Yet</h3>
                    <p>Start promoting your campaigns to attract your first donors.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="background: var(--background);">
                            <tr>
                                <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--border); font-weight: 600;">Donor</th>
                                <th style="padding: 1rem; text-align: left; border-bottom: 1px solid var(--border); font-weight: 600;">Contact</th>
                                <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--border); font-weight: 600;">Donations</th>
                                <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--border); font-weight: 600;">Total Amount</th>
                                <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--border); font-weight: 600;">First Donation</th>
                                <th style="padding: 1rem; text-align: center; border-bottom: 1px solid var(--border); font-weight: 600;">Last Donation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($donors as $donor): ?>
                                <tr style="border-bottom: 1px solid var(--border);">
                                    <td style="padding: 1rem;">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div style="width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                                                <?= strtoupper(substr($donor['donor_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600;"><?= h($donor['donor_name']) ?></div>
                                                <?php if($donor['user_id']): ?>
                                                    <span class="badge" style="background: var(--success); color: white; padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.7rem;">Registered</span>
                                                <?php else: ?>
                                                    <span class="badge" style="background: var(--text-muted); color: white; padding: 0.2rem 0.5rem; border-radius: var(--radius-sm); font-size: 0.7rem;">Guest</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <div style="font-size: 0.875rem;">
                                            <?php if($donor['donor_email']): ?>
                                                <div style="margin-bottom: 0.25rem;">
                                                    <i class="fa-solid fa-envelope" style="color: var(--text-muted); margin-right: 0.5rem;"></i>
                                                    <?= h($donor['donor_email']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if($donor['donor_phone']): ?>
                                                <div>
                                                    <i class="fa-solid fa-phone" style="color: var(--text-muted); margin-right: 0.5rem;"></i>
                                                    <?= h($donor['donor_phone']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <span style="font-weight: 600; color: var(--primary);"><?= $donor['donation_count'] ?></span>
                                    </td>
                                    <td style="padding: 1rem; text-align: center;">
                                        <span style="font-weight: 600; color: var(--success);">$<?= number_format($donor['total_donated'], 2) ?></span>
                                    </td>
                                    <td style="padding: 1rem; text-align: center; font-size: 0.875rem; color: var(--text-muted);">
                                        <?= date('M j, Y', strtotime($donor['first_donation'])) ?>
                                    </td>
                                    <td style="padding: 1rem; text-align: center; font-size: 0.875rem; color: var(--text-muted);">
                                        <?= date('M j, Y', strtotime($donor['last_donation'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function exportDonors() {
    // Create CSV content
    let csvContent = "Donor Name,Email,Phone,Donation Count,Total Donated,First Donation,Last Donation\n";
    
    <?php foreach($donors as $donor): ?>
    csvContent += "<?= addslashes($donor['donor_name']) ?>," +
                  "<?= addslashes($donor['donor_email'] ?? '') ?>," +
                  "<?= addslashes($donor['donor_phone'] ?? '') ?>," +
                  "<?= $donor['donation_count'] ?>," +
                  "<?= $donor['total_donated'] ?>," +
                  "<?= date('Y-m-d', strtotime($donor['first_donation'])) ?>," +
                  "<?= date('Y-m-d', strtotime($donor['last_donation'])) ?>\n";
    <?php endforeach; ?>
    
    // Create and download file
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'donors_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>