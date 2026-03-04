<?php
require_once __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT d.*, c.title, c.ngo_id, n.name as ngo_name
                       FROM donations d 
                       JOIN campaigns c ON d.campaign_id = c.id 
                       JOIN ngos ngo ON c.ngo_id = ngo.id
                       JOIN users n ON ngo.user_id = n.id
                       WHERE d.id = ?");
$stmt->execute([$id]);
$donation = $stmt->fetch();

if (!$donation) {
    echo "<div class='container' style='padding:4rem 0;'><h2>Receipt Not Found.</h2></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// SECURITY: Ensure that only the actual donor OR an admin can view this receipt
if (!isset($_SESSION['user_id'])) {
    header("Location: /share_hope/login.php");
    exit;
}

if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_id'] != $donation['donor_id']) {
    echo "<div class='container' style='padding:4rem 0;'><h2>Unauthorized Access. You can only view your own receipts.</h2></div>";
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
        <div
            style="background: #10B981; color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; text-align: center;">
            <i class="fa-solid fa-circle-check" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
            <br>
            <span style="font-weight: 600; font-size: 1.25rem;"><?= h($_SESSION['success']) ?></span>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="receipt-card" id="receipt-card"
        style="background: #FFFFFF; padding: 3rem; border-radius: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #E2E8F0;">
        <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px dashed #E2E8F0;">
            <div class="receipt-title"
                style="font-size: 2rem; font-weight: 700; color: #4F46E5; margin-bottom: 0.5rem;">Official
                Receipt</div>
            <p style="color: #64748B;">Transaction ID: <strong
                    style="color: #0F172A;"><?= h($donation['transaction_id']) ?></strong></p>
        </div>

        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
            <span style="color: #64748B;">Date:</span>
            <span
                style="font-weight: 600; color: #0F172A;"><?= date('M j, Y H:i', strtotime($donation['created_at'])) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
            <span style="color: #64748B;">Supported NGO:</span>
            <span style="font-weight: 600; color: #0F172A;"><?= h($donation['ngo_name']) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
            <span style="color: #64748B;">Campaign:</span>
            <span
                style="font-weight: 600; text-align: right; max-width: 60%; color: #0F172A;"><?= h($donation['title']) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
            <span style="color: #64748B;">Payment Method:</span>
            <span
                style="font-weight: 600; text-transform: uppercase; color: #0F172A;"><?= h($donation['payment_method']) ?></span>
        </div>

        <div
            style="display: flex; justify-content: space-between; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #E2E8F0; font-size: 1.5rem;">
            <span style="color: #0F172A; font-weight: 500;">Total Amount:</span>
            <span style="font-weight: 700; color: #10B981;">KSh
                <?= number_format($donation['amount'], 2) ?></span>
        </div>

        <div style="text-align: center; margin-top: 3rem;" class="no-print">
            <button onclick="downloadPDF()" class="btn btn-primary"><i class="fa-solid fa-download"></i> Download PDF
                Receipt</button>
            <a href="/share_hope/campaigns.php" class="btn btn-outline" style="margin-left: 1rem;">Back</a>
        </div>
    </div>
</div>

<script>
    function downloadPDF() {
        window.print();
    }
</script>

<!-- Confetti Celebration -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<?php if (isset($_SESSION['goal_reached']) && $_SESSION['goal_reached']): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var duration = 3 * 1000;
            var animationEnd = Date.now() + duration;
            var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 };

            function randomInRange(min, max) {
                return Math.random() * (max - min) + min;
            }

            var interval = setInterval(function () {
                var timeLeft = animationEnd - Date.now();

                if (timeLeft <= 0) {
                    return clearInterval(interval);
                }

                var particleCount = 50 * (timeLeft / duration);
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
            }, 250);
        });
    </script>
    <?php unset($_SESSION['goal_reached']); ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>