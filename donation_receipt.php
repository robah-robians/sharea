<?php
session_start();
require_once __DIR__ . '/includes/header.php';

if (!isset($_GET['id'])) {
    die("Invalid request.");
}

$donation_id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT d.*, c.title as campaign_title, c.id as campaign_id,
               'Share Hope' as ngo_name
        FROM donations d
        JOIN campaigns c ON d.campaign_id = c.id
        WHERE d.id = ?
    ");
    $stmt->execute([$donation_id]);
    $donation = $stmt->fetch();

    if (!$donation) {
        die("Donation not found.");
    }

    if (!isset($_SESSION['user_id'])) {
        die("Please log in to view this receipt.");
    }

    $is_authorized = false;
    if (in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
        $is_authorized = true;
    } elseif ($_SESSION['user_role'] === 'donor' && $donation['donor_id'] === $_SESSION['user_id']) {
        $is_authorized = true;
    }

    if (!$is_authorized) {
        die("You are not authorized to view this receipt.");
    }

    // Generate receipt number (gracefully handle missing table)
    $receipt_number = 'REC-' . date('Y') . '-' . str_pad($donation_id, 6, '0', STR_PAD_LEFT);
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS donation_receipts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            donation_id INT NOT NULL UNIQUE,
            receipt_number VARCHAR(50) NOT NULL UNIQUE,
            generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE CASCADE
        )");
        $stmt = $pdo->prepare("SELECT receipt_number FROM donation_receipts WHERE donation_id = ?");
        $stmt->execute([$donation_id]);
        $receipt_record = $stmt->fetch();
        if (!$receipt_record) {
            $stmt = $pdo->prepare("INSERT INTO donation_receipts (donation_id, receipt_number) VALUES (?, ?)");
            $stmt->execute([$donation_id, $receipt_number]);
        } else {
            $receipt_number = $receipt_record['receipt_number'];
        }
    } catch (Exception $e) {
        // Table unavailable — use generated number
    }

} catch (Exception $e) {
    die("Error loading receipt: " . $e->getMessage());
}
?>

<div style="max-width: 800px; margin: 3rem auto; padding: 0 1rem;">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 style="margin: 0;">Donation Receipt</h1>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fa-solid fa-print"></i> Print / Save as PDF
        </button>
    </div>

    <div style="background: white; border: 2px solid var(--border); border-radius: var(--radius-md); padding: 3rem; page-break-after: always;">

        <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 2px solid var(--primary);">
            <h1 style="color: var(--primary); margin: 0 0 0.5rem 0; font-size: 2rem;">
                <i class="fa-solid fa-receipt"></i> DONATION RECEIPT
            </h1>
            <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">Thank you for your generous contribution</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <div>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0 0 0.25rem 0; font-weight: 600;">RECEIPT NUMBER</p>
                <p style="color: var(--text-main); font-size: 1.1rem; margin: 0;"><?= h($receipt_number) ?></p>
            </div>
            <div>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0 0 0.25rem 0; font-weight: 600;">DATE</p>
                <p style="color: var(--text-main); font-size: 1.1rem; margin: 0;"><?= date('M j, Y', strtotime($donation['created_at'])) ?></p>
            </div>
        </div>

        <div style="background: var(--background); padding: 1.5rem; border-radius: var(--radius-sm); margin-bottom: 2rem;">
            <h3 style="margin: 0 0 1rem 0; color: var(--text-main);">Donation Details</h3>

            <div style="margin-bottom: 1rem;">
                <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0 0 0.25rem 0; font-weight: 600;">CAMPAIGN</p>
                <p style="color: var(--text-main); margin: 0; font-size: 1rem;"><?= h($donation['campaign_title']) ?></p>
            </div>

            <div style="margin-bottom: 1rem;">
                <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0 0 0.25rem 0; font-weight: 600;">BENEFICIARY ORGANIZATION</p>
                <p style="color: var(--text-main); margin: 0; font-size: 1rem;"><?= h($donation['ngo_name']) ?></p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0 0 0.25rem 0; font-weight: 600;">DONATION TYPE</p>
                    <p style="color: var(--text-main); margin: 0; font-size: 1rem; text-transform: capitalize;"><?= h($donation['payment_method']) ?></p>
                </div>
                <div>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0 0 0.25rem 0; font-weight: 600;">STATUS</p>
                    <p style="color: var(--text-main); margin: 0; font-size: 1rem; text-transform: capitalize;">
                        <span style="background: <?= $donation['status'] === 'completed' ? 'var(--secondary)' : ($donation['status'] === 'pending' ? 'var(--accent)' : 'var(--danger)') ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem;">
                            <?= h($donation['status']) ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <div style="background: linear-gradient(135deg, var(--primary), var(--primary-hover)); color: white; padding: 2rem; border-radius: var(--radius-md); text-align: center; margin-bottom: 2rem;">
            <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; opacity: 0.9;">DONATION AMOUNT</p>
            <p style="margin: 0; font-size: 2.5rem; font-weight: 700;">KES <?= number_format($donation['amount'], 2) ?></p>
        </div>

        <div style="background: rgba(79, 70, 229, 0.05); border-left: 4px solid var(--primary); padding: 1.5rem; margin-bottom: 2rem; border-radius: var(--radius-sm);">
            <h4 style="margin: 0 0 0.75rem 0; color: var(--primary);">💡 Tax Information</h4>
            <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem; line-height: 1.6;">
                This donation may be tax-deductible depending on your local tax laws.
                Please consult with your tax advisor. Receipt number: <?= h($receipt_number) ?>
            </p>
        </div>

        <div style="text-align: center; padding-top: 2rem; border-top: 1px solid var(--border); margin-top: 2rem;">
            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0;">
                This receipt is generated automatically and is valid proof of your donation.<br>
                For more information, visit <strong>www.sharehope.com</strong>
            </p>
            <p style="color: var(--text-muted); font-size: 0.75rem; margin: 0.5rem 0 0 0;">
                Generated on <?= date('M j, Y \a\t H:i A') ?>
            </p>
        </div>
    </div>
</div>

<style media="print">
    body { background: white; }
    .btn { display: none !important; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
