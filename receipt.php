<?php
session_start();
require_once __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
    echo "<div class='container' style='padding:4rem 0;'><h2>Invalid Request.</h2></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT d.*, c.title, 'Share Hope Admin' as ngo_name
                       FROM donations d 
                       JOIN campaigns c ON d.campaign_id = c.id 
                       WHERE d.id = ?");
$stmt->execute([$id]);
$donation = $stmt->fetch();

if (!$donation) {
    echo "<div class='container' style='padding:4rem 0;'><h2>Receipt Not Found.</h2></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
?>
<style>
    @media print {
        body {
            background: white !important;
            font-family: 'Outfit', sans-serif;
        }

        .glass-header,
        .header-spacer,
        #announcements-section,
        .site-footer,
        .footer-bottom,
        .footer-grid,
        footer,
        .btn {
            display: none !important;
        }

        .container {
            padding: 1rem !important;
            margin: 0 auto;
            width: 100%;
            box-shadow: none !important;
            border: none !important;
        }

        .receipt-card {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }

        .receipt-title {
            font-size: 2.5rem !important;
            color: #000 !important;
        }
    }
</style>
<div class="container" style="padding: 4rem 1.5rem; max-width: 600px;">

    <?php if (isset($_SESSION['success'])): ?>
        <div style="background: var(--secondary); color: white; padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem; text-align: center;">
            <i class="fa-solid fa-circle-check" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
            <br>
            <span style="font-weight: 600; font-size: 1.25rem;"><?= h($_SESSION['success']) ?></span>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="receipt-card" style="background: var(--surface); padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border);">
        <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px dashed var(--border);">
            <div class="receipt-title" style="font-size: 2rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem;">Official Receipt</div>
            <p style="color: var(--text-muted);">Transaction ID: <strong style="color: var(--text-main);"><?= h($donation['transaction_id']) ?></strong></p>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
            <span style="color: var(--text-muted);">Date:</span>
            <span style="font-weight: 600;"><?= date('M j, Y H:i', strtotime($donation['created_at'])) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
            <span style="color: var(--text-muted);">Supported Organization:</span>
            <span style="font-weight: 600;"><?= h($donation['ngo_name']) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
            <span style="color: var(--text-muted);">Campaign:</span>
            <span style="font-weight: 600; text-align: right; max-width: 60%;"><?= h($donation['title']) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
            <span style="color: var(--text-muted);">Payment Method:</span>
            <span style="font-weight: 600; text-transform: uppercase;"><?= h($donation['payment_method']) ?></span>
        </div>

        <div style="display: flex; justify-content: space-between; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border); font-size: 1.5rem;">
            <span style="color: var(--text-main); font-weight: 500;">Total Amount:</span>
            <span style="font-weight: 700; color: var(--secondary);">$<?= number_format($donation['amount'], 2) ?></span>
        </div>

        <div style="text-align: center; margin-top: 3rem;">
            <button onclick="window.print()" class="btn btn-outline"><i class="fa-solid fa-print"></i> Print PDF</button>
            <a href="/share_hope/campaigns.php" class="btn btn-primary" style="margin-left: 1rem;">Back to Campaigns</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
