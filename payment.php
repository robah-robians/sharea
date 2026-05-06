<?php
session_start();
require_once __DIR__ . '/includes/header.php';

// Validate form data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/");
    exit;
}

$campaign_id = $_POST['campaign_id'] ?? 0;
$amount = $_POST['amount'] ?? 0;
$payment_method = $_POST['payment_method'] ?? '';
$message = $_POST['message'] ?? '';
$is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
$campaign_type = $_GET['type'] ?? 'regular';

// Fetch campaign details
if ($campaign_type === 'awareness') {
    $stmt = $pdo->prepare("SELECT ac.*, 'Share Hope Admin' as ngo_name FROM awareness_campaigns ac WHERE ac.id = ?");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch();
} else {
    $stmt = $pdo->prepare("SELECT c.*, 'Share Hope Admin' as ngo_name FROM campaigns c WHERE c.id = ?");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch();
}

if (!$campaign) {
    $_SESSION['error'] = 'Campaign not found.';
    header("Location: " . BASE_URL . "/");
    exit;
}

// Store payment data in session for processing
$_SESSION['payment_data'] = [
    'campaign_id' => $campaign_id,
    'amount' => $amount,
    'payment_method' => $payment_method,
    'message' => $message,
    'is_anonymous' => $is_anonymous,
    'campaign_type' => $campaign_type
];
?>

<div class="hero" style="padding: 2rem 0; background: var(--surface); border-bottom: 1px solid var(--border);">
    <div class="container" style="text-align: center;">
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Complete Your Payment</h1>
        <p style="color: var(--text-muted);">You're supporting: <strong><?= h($campaign['title']) ?></strong></p>
    </div>
</div>

<div class="container" style="padding: 3rem 1.5rem; max-width: 600px;">
    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <?= h($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Payment Summary -->
    <div style="background: var(--surface); padding: 2rem; border-radius: var(--radius-lg); margin-bottom: 2rem; border: 1px solid var(--border);">
        <h3 style="margin: 0 0 1rem; color: var(--text-main);">Payment Summary</h3>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <span>Donation Amount:</span>
            <span style="font-weight: 600;">$<?= number_format($amount, 2) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <span>Payment Method:</span>
            <span style="font-weight: 600;"><?= $payment_method === 'mpesa' ? 'M-Pesa' : 'Credit/Debit Card' ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <span>Campaign:</span>
            <span style="font-weight: 600;"><?= h($campaign['title']) ?></span>
        </div>
        <?php if ($message): ?>
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);">
            <span style="color: var(--text-muted); font-size: 0.9rem;">Your Message:</span>
            <p style="margin: 0.5rem 0 0; font-style: italic;">"<?= h($message) ?>"</p>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($payment_method === 'mpesa'): ?>
        <!-- M-Pesa Payment Form -->
        <form action="<?= BASE_URL ?>/actions/process_payment.php" method="POST" style="background: var(--surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--secondary);">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
            
            <h3 style="margin: 0 0 1.5rem; color: var(--secondary);">
                <i class="fa-solid fa-mobile-screen" style="margin-right: 0.5rem;"></i>M-Pesa Payment Details
            </h3>

            <!-- Payment Options -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: var(--background); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border); text-align: center;">
                    <h6 style="margin: 0 0 0.5rem; color: var(--text-main); font-size: 0.9rem;">PayBill</h6>
                    <div style="font-family: monospace; font-size: 1.2rem; font-weight: 600; color: var(--secondary);">247247</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Account: 342567</div>
                </div>
                
                <div style="background: var(--background); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border); text-align: center;">
                    <h6 style="margin: 0 0 0.5rem; color: var(--text-main); font-size: 0.9rem;">Till Number</h6>
                    <div style="font-family: monospace; font-size: 1.2rem; font-weight: 600; color: var(--secondary);">8556345</div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Your M-Pesa Phone Number <span style="color: var(--danger);">*</span></label>
                <input type="tel" name="phone_number" class="form-control" placeholder="+254 7XX XXX XXX" required 
                       style="font-size: 1.1rem; padding: 0.75rem;" pattern="[+]?[0-9\s\-()]+">
                <small style="color: var(--text-muted); font-size: 0.85rem;">Enter the phone number registered with M-Pesa</small>
            </div>

            <div class="form-group">
                <label class="form-label">Choose Payment Method</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; background: var(--background);">
                        <input type="radio" name="mpesa_method" value="paybill" checked>
                        <span>Use PayBill</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer; background: var(--background);">
                        <input type="radio" name="mpesa_method" value="till">
                        <span>Use Till Number</span>
                    </label>
                </div>
            </div>

            <div style="background: rgba(16, 185, 129, 0.1); padding: 1rem; border-radius: var(--radius-sm); border-left: 3px solid var(--secondary); margin-bottom: 1.5rem;">
                <h6 style="margin: 0 0 0.5rem; color: var(--secondary);">Payment Instructions:</h6>
                <ol style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
                    <li>Click "Send Payment Request" below</li>
                    <li>You'll receive an M-Pesa prompt on your phone</li>
                    <li>Enter your M-Pesa PIN to complete the payment</li>
                    <li>You'll receive a confirmation SMS from M-Pesa</li>
                </ol>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.125rem; padding: 1rem; background: var(--secondary); border-color: var(--secondary);">
                <i class="fa-solid fa-paper-plane" style="margin-right: 0.5rem;"></i>Send Payment Request
            </button>
        </form>

    <?php else: ?>
        <!-- Card Payment Form -->
        <form action="<?= BASE_URL ?>/actions/process_payment.php" method="POST" style="background: var(--surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--primary);">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
            
            <h3 style="margin: 0 0 1.5rem; color: var(--primary);">
                <i class="fa-solid fa-credit-card" style="margin-right: 0.5rem;"></i>Card Payment Details
            </h3>

            <div class="form-group">
                <label class="form-label">Card Number <span style="color: var(--danger);">*</span></label>
                <input type="text" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" required 
                       style="font-size: 1.1rem; padding: 0.75rem;" maxlength="19">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Expiry Date <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="expiry_date" class="form-control" placeholder="MM/YY" required 
                           style="font-size: 1.1rem; padding: 0.75rem;" maxlength="5">
                </div>
                <div class="form-group">
                    <label class="form-label">CVV <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="cvv" class="form-control" placeholder="123" required 
                           style="font-size: 1.1rem; padding: 0.75rem;" maxlength="4">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Cardholder Name <span style="color: var(--danger);">*</span></label>
                <input type="text" name="cardholder_name" class="form-control" placeholder="John Doe" required 
                       style="font-size: 1.1rem; padding: 0.75rem;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.125rem; padding: 1rem;">
                <i class="fa-solid fa-lock" style="margin-right: 0.5rem;"></i>Process Payment
            </button>
        </form>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="<?= BASE_URL ?>/donate.php?campaign_id=<?= $campaign_id ?><?= $campaign_type === 'awareness' ? '&type=awareness' : '' ?>" 
           class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
            <i class="fa-solid fa-arrow-left" style="margin-right: 0.5rem;"></i>Back to Campaign
        </a>
    </div>
</div>

<script>
// Format card number input
document.querySelector('input[name="card_number"]')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
    e.target.value = formattedValue;
});

// Format expiry date input
document.querySelector('input[name="expiry_date"]')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    e.target.value = value;
});

// Format phone number input
document.querySelector('input[name="phone_number"]')?.addEventListener('input', function(e) {
    let value = e.target.value.replace(/[^0-9+\s\-()]/g, '');
    e.target.value = value;
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>