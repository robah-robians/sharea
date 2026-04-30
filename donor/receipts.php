<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: /share_hope/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';


$user_id = $_SESSION['user_id'];

// Get completed donations for receipts
$stmt = $pdo->prepare("
    SELECT d.*, c.title as campaign_title, 'Share Hope' as ngo_name
    FROM donations d
    JOIN campaigns c ON d.campaign_id = c.id
    WHERE d.donor_id = ? AND d.status = 'completed'
    ORDER BY d.created_at DESC
");
$stmt->execute([$user_id]);
$donations = $stmt->fetchAll();

// Group by year for tax purposes
$donations_by_year = [];
$yearly_totals = [];
foreach($donations as $donation) {
    $year = date('Y', strtotime($donation['created_at']));
    $donations_by_year[$year][] = $donation;
    $yearly_totals[$year] = ($yearly_totals[$year] ?? 0) + $donation['amount'];
}

// Get user info for receipts
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<div class="admin-layout">
    <?php include __DIR__ . '/includes/donor_nav.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1><i class="fa-solid fa-receipt"></i> Tax Receipts</h1>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <button onclick="downloadAllReceipts()" class="btn btn-outline">
                    <i class="fa-solid fa-download"></i> Download All
                </button>
                <span style="color: var(--text-muted);"><?= count($donations) ?> receipts available</span>
            </div>
        </div>

        <!-- Yearly Summary -->
        <?php if(!empty($yearly_totals)): ?>
            <div class="yearly-summary" style="background: var(--surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border); margin-bottom: 2rem;">
                <h3 style="margin-top: 0;">Annual Donation Summary</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                    <?php foreach($yearly_totals as $year => $total): ?>
                        <div class="year-card" style="background: linear-gradient(135deg, var(--primary), var(--accent)); color: white; padding: 1.5rem; border-radius: var(--radius-lg); text-align: center;">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 1.5rem; font-weight: 700;"><?= $year ?></h4>
                            <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">$<?= number_format($total, 2) ?></div>
                            <div style="opacity: 0.9; font-size: 0.875rem;"><?= count($donations_by_year[$year]) ?> donations</div>
                            <button onclick="downloadYearlyReceipt(<?= $year ?>)" class="btn" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); margin-top: 1rem; width: 100%;">
                                <i class="fa-solid fa-download"></i> Download <?= $year ?> Summary
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Individual Receipts -->
        <div class="receipts-section" style="background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); overflow: hidden;">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                <h3 style="margin: 0;">Individual Donation Receipts</h3>
            </div>
            
            <?php if(empty($donations)): ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                    <i class="fa-solid fa-receipt" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                    <h3>No Receipts Available</h3>
                    <p>Complete donations will generate tax-deductible receipts here.</p>
                    <a href="/share_hope/donor/find_campaigns.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fa-solid fa-search"></i> Find Campaigns to Support
                    </a>
                </div>
            <?php else: ?>
                <div class="receipts-list">
                    <?php foreach($donations as $donation): ?>
                        <div class="receipt-item" style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                                    <div style="width: 40px; height: 40px; background: var(--success); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                        <i class="fa-solid fa-receipt"></i>
                                    </div>
                                    <div>
                                        <h4 style="margin: 0; font-size: 1.1rem;"><?= h($donation['campaign_title']) ?></h4>
                                        <div style="color: var(--text-muted); font-size: 0.875rem;">
                                            Donated to <?= h($donation['ngo_name']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 2rem; font-size: 0.875rem; color: var(--text-muted); margin-left: 3rem;">
                                    <span><strong>Date:</strong> <?= date('M j, Y', strtotime($donation['created_at'])) ?></span>
                                    <span><strong>Method:</strong> <?= ucfirst($donation['payment_method']) ?></span>
                                    <?php if($donation['transaction_id']): ?>
                                        <span><strong>Transaction:</strong> <?= h($donation['transaction_id']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="text-align: right; display: flex; align-items: center; gap: 1rem;">
                                <div>
                                    <div style="font-size: 1.25rem; font-weight: 700; color: var(--success);">
                                        $<?= number_format($donation['amount'], 2) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                                        Tax Deductible
                                    </div>
                                </div>
                                <a href="/share_hope/donation_receipt.php?id=<?= $donation['id'] ?>" target="_blank" class="btn btn-outline">
                                    <i class="fa-solid fa-download"></i> Download
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tax Information -->
        <div class="tax-info" style="background: var(--surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border); margin-top: 2rem;">
            <h3 style="margin-top: 0;">Tax Deduction Information</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <h4>Important Notes</h4>
                    <ul style="color: var(--text-muted); line-height: 1.6;">
                        <li>All donations to verified NGOs are tax-deductible</li>
                        <li>Keep these receipts for your tax records</li>
                        <li>Receipts are generated automatically for completed donations</li>
                        <li>Annual summaries help with tax preparation</li>
                    </ul>
                </div>
                <div>
                    <h4>Need Help?</h4>
                    <div style="background: var(--background); padding: 1.5rem; border-radius: var(--radius-md); border-left: 4px solid var(--primary);">
                        <p style="margin: 0; color: var(--text-muted);">
                            If you need assistance with your donation receipts or have questions about tax deductions, 
                            please contact our support team.
                        </p>
                        <a href="mailto:support@sharehope.org" class="btn btn-outline" style="margin-top: 1rem;">
                            <i class="fa-solid fa-envelope"></i> Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function downloadAllReceipts() {
    // This would typically generate a ZIP file of all receipts
    alert('Feature coming soon: Download all receipts as ZIP file');
}

function downloadYearlyReceipt(year) {
    // Generate yearly summary receipt
    const donations = <?= json_encode($donations_by_year) ?>;
    const yearDonations = donations[year] || [];
    
    if (yearDonations.length === 0) {
        alert('No donations found for ' + year);
        return;
    }
    
    // Create a simple yearly summary
    let content = `SHARE HOPE - Annual Donation Summary ${year}\n\n`;
    content += `Donor: <?= h($user['name']) ?>\n`;
    content += `Email: <?= h($user['email']) ?>\n\n`;
    content += `Donations Made in ${year}:\n`;
    content += `${'='.repeat(50)}\n`;
    
    let total = 0;
    yearDonations.forEach(donation => {
        const date = new Date(donation.created_at).toLocaleDateString();
        const amount = parseFloat(donation.amount);
        total += amount;
        content += `${date} - ${donation.campaign_title} - $${amount.toFixed(2)}\n`;
    });
    
    content += `${'='.repeat(50)}\n`;
    content += `Total Donations: $${total.toFixed(2)}\n`;
    content += `Number of Donations: ${yearDonations.length}\n\n`;
    content += `All donations are tax-deductible.\n`;
    content += `Generated on: ${new Date().toLocaleDateString()}\n`;
    
    // Download as text file
    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `donation_summary_${year}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>