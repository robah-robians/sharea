<?php
session_start();
require_once __DIR__ . '/includes/header.php';

$campaign_id = $_GET['campaign_id'] ?? 0;
$campaign_type = $_GET['type'] ?? 'regular';

if ($campaign_type === 'awareness') {
    // Fetch awareness campaign
    $stmt = $pdo->prepare("SELECT ac.*, 'Share Hope Admin' as ngo_name FROM awareness_campaigns ac WHERE ac.id = ?");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch();
    
    if ($campaign) {
        $campaign['user_id'] = null;
        $campaign['goal_amount'] = 0;
        $campaign['current_amount'] = 0;
    }
} else {
    // Fetch regular campaign (no ngo_id join needed)
    $stmt = $pdo->prepare("SELECT c.*, 'Share Hope Admin' as ngo_name FROM campaigns c WHERE c.id = ?");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch();
}

if (!$campaign) {
    echo "<div class='container' style='padding:4rem 0;'><h2>Campaign Not Found.</h2></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Set user_id to null for regular campaigns (no NGO owner)
if (!isset($campaign['user_id'])) {
    $campaign['user_id'] = null;
}

// Fetch only APPROVED Campaign Updates (pending ones need admin approval first)
$stmt = $pdo->prepare("SELECT * FROM campaign_updates WHERE campaign_id = ? AND status = 'approved' ORDER BY created_at DESC");
$stmt->execute([$campaign_id]);
$updates = $stmt->fetchAll();

?>

<div class="hero" style="padding: 4rem 0; background: var(--surface); border-bottom: 1px solid var(--border);">
    <div class="container" style="text-align: left;">
        <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem; text-align: left;"><?= h($campaign['title']) ?></h1>
        <p style="color: var(--text-muted); text-align: left; margin: 0;">By <?= h($campaign['ngo_name']) ?> <i class="fa-solid fa-circle-check text-secondary"></i></p>
    </div>
</div>

<div class="container" style="padding: 4rem 1.5rem;">
    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <?= h($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 3fr 2fr; gap: 3rem; align-items: start;">

        <!-- Main Content Column -->
        <div>
            <?php if ($campaign['image_url']): ?>
                <img src="<?= h($campaign['image_url']) ?>" alt="Campaign Banner" style="width: 100%; border-radius: var(--radius-lg); margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
            <?php endif; ?>

            <div style="background: var(--surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); margin-bottom: 3rem;">
                <h3 style="margin-top: 0; margin-bottom: 1.5rem;">About This Campaign</h3>
                <div style="line-height: 1.8; color: var(--text-muted); font-size: 1.05rem; white-space: pre-wrap;">
                    <?= h($campaign['description']) ?>
                </div>
            </div>

            <div style="background: var(--surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                    <h3 style="margin: 0;"><i class="fa-solid fa-timeline text-primary"></i> Transparency & Impact Updates</h3>
                </div>

                <?php if (empty($updates)): ?>
                    <p class="text-muted" style="text-align: center; padding: 2rem;">No impact updates have been posted for this campaign yet.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 2rem;">
                        <?php foreach ($updates as $update): ?>
                            <div style="border-left: 3px solid var(--secondary); padding-left: 1.5rem;">
                                <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                                    <i class="fa-regular fa-clock"></i>
                                    <?= date('M j, Y, g:i A', strtotime($update['created_at'])) ?>
                                </div>
                                <p style="margin: 0 0 1rem; color: var(--text-main); font-size: 1.05rem; line-height: 1.6;">
                                    <?= nl2br(h($update['message'])) ?>
                                </p>
                                <?php if ($update['image_url']): ?>
                                    <img src="<?= h($update['image_url']) ?>" alt="Update Proof" style="max-height: 300px; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Donation Forms Column -->
        <div>
            <!-- Donation Type Tabs -->
            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border);">
                <button type="button" class="donation-tab-btn" data-tab="financial" style="background: none; border: none; padding: 1rem 0; font-size: 1rem; font-weight: 600; color: var(--primary); cursor: pointer; border-bottom: 3px solid var(--primary); position: relative; top: 2px; transition: var(--transition);">
                    <i class="fa-solid fa-dollar-sign" style="margin-right: 0.5rem;"></i>Financial Donation
                </button>
                <button type="button" class="donation-tab-btn" data-tab="pledge" style="background: none; border: none; padding: 1rem 0; font-size: 1rem; font-weight: 600; color: var(--text-muted); cursor: pointer; border-bottom: 3px solid transparent; position: relative; top: 2px; transition: var(--transition);">
                    <i class="fa-solid fa-hand-holding-heart" style="margin-right: 0.5rem;"></i>Pledge / In-Kind
                </button>
            </div>

            <!-- Financial Donation Form -->
            <form id="financial-form" action="/share_hope/payment.php" method="POST" style="background: var(--surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--primary); position: sticky; top: 2rem; display: block;">
                <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                <input type="hidden" name="campaign_id" value="<?= h($campaign['id']) ?>">

                <h3 style="margin-top: 0; margin-bottom: 1.5rem;">Make a Financial Donation</h3>

                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                    <label style="flex: 1; min-width: 80px; text-align: center; border: 2px solid var(--border); padding: 1rem; border-radius: var(--radius-md); cursor: pointer; transition: var(--transition);">
                        <input type="radio" name="amount_preset" value="25" style="display: none;" onclick="document.getElementById('custom_amount').value='25'">
                        <span style="font-size: 1.25rem; font-weight: 600;">$25</span>
                    </label>
                    <label style="flex: 1; min-width: 80px; text-align: center; border: 2px solid var(--border); padding: 1rem; border-radius: var(--radius-md); cursor: pointer; transition: var(--transition);">
                        <input type="radio" name="amount_preset" value="50" style="display: none;" onclick="document.getElementById('custom_amount').value='50'" checked>
                        <span style="font-size: 1.25rem; font-weight: 600;">$50</span>
                    </label>
                    <label style="flex: 1; min-width: 80px; text-align: center; border: 2px solid var(--border); padding: 1rem; border-radius: var(--radius-md); cursor: pointer; transition: var(--transition);">
                        <input type="radio" name="amount_preset" value="100" style="display: none;" onclick="document.getElementById('custom_amount').value='100'">
                        <span style="font-size: 1.25rem; font-weight: 600;">$100</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">Or Custom Amount ($)</label>
                    <input type="number" step="0.01" min="1" name="amount" id="custom_amount" class="form-control" value="50" style="font-size: 1.25rem; font-weight: 600;" required>
                </div>

                <h4 style="margin: 1.5rem 0 1rem;">Payment Method</h4>
                <div style="display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); cursor: pointer; background: var(--background);">
                        <input type="radio" name="payment_method" value="mpesa" checked>
                        <span style="flex-grow: 1;">
                            <div style="font-weight: 600; margin-bottom: 0.25rem;">M-Pesa Mobile Money</div>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">PayBill: <strong>247247</strong> (A/C: 342567) | Till: <strong>8556345</strong></div>
                        </span>
                        <i class="fa-solid fa-mobile-screen" style="font-size: 1.5rem; color: var(--secondary);"></i>
                    </label>
                    <label style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); cursor: pointer; background: var(--background);">
                        <input type="radio" name="payment_method" value="card">
                        <span style="flex-grow: 1;">
                            <div style="font-weight: 600; margin-bottom: 0.25rem;">Credit/Debit Card</div>
                            <div style="font-size: 0.85rem; color: var(--text-muted);">Visa, Mastercard, American Express</div>
                        </span>
                        <i class="fa-solid fa-credit-card" style="font-size: 1.5rem; color: var(--primary);"></i>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">Support Message (Optional)</label>
                    <textarea name="message" class="form-control" rows="2" placeholder="Your words of encouragement for this cause..."></textarea>
                </div>

                <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="is_anonymous" id="fin_anon" value="1">
                    <label for="fin_anon" style="user-select: none; font-size: 0.875rem;">Make donation anonymous</label>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.125rem; padding: 1rem;">Donate Now</button>
            </form>

            <!-- In-Kind / Pledge Form -->
            <form id="pledge-form" action="/share_hope/actions/process_inkind.php" method="POST" style="background: var(--surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 2px solid var(--accent); position: sticky; top: 2rem; display: none;">
                <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                <input type="hidden" name="campaign_id" value="<?= h($campaign['id']) ?>">

                <h3 style="margin-top: 0; margin-bottom: 1.5rem;"><i class="fa-solid fa-hand-holding-heart" style="color: var(--accent); margin-right: 0.5rem;"></i>Pledge In-Kind Donation</h3>

                <div style="background: rgba(245, 158, 11, 0.1); color: var(--text-main); padding: 1rem; border-left: 4px solid var(--accent); border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.9rem;">
                    <strong><i class="fa-solid fa-circle-info" style="margin-right: 0.5rem;"></i>About In-Kind Donations:</strong>
                    <p style="margin: 0.5rem 0 0;">Pledge physical items like food, clothing, medical supplies, or other goods. The NGO will contact you to coordinate delivery and pickup.</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Item Category <span style="color: var(--danger);">*</span></label>
                    <select name="item_category" class="form-control" required style="padding: 0.75rem;">
                        <option value="">Select Category...</option>
                        <option value="Food & Groceries">Food & Groceries</option>
                        <option value="Clothing">Clothing</option>
                        <option value="Medical Supplies">Medical Supplies</option>
                        <option value="Books & Educational">Books & Educational Materials</option>
                        <option value="Household Items">Household Items</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Tools & Equipment">Tools & Equipment</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Quantity & Description <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="quantity" class="form-control" placeholder="e.g., 50 kg, 20 pieces, 1 box" required style="padding: 0.75rem; margin-bottom: 0.5rem;">
                    <textarea name="item_description" class="form-control" rows="3" placeholder="Describe the items in detail (brand, condition, specifications, etc.)" required style="padding: 0.75rem;"></textarea>
                </div>

                <h4 style="margin: 1.5rem 0 1rem; border-top: 1px solid var(--border); padding-top: 1rem;">Your Contact Information</h4>

                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="donor_name" class="form-control" placeholder="Your name" style="padding: 0.75rem;">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="donor_email" class="form-control" placeholder="your@email.com" style="padding: 0.75rem;">
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="donor_phone" class="form-control" placeholder="+254 7XX XXX XXX" style="padding: 0.75rem;">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.125rem; padding: 1rem; background: var(--accent); border-color: var(--accent);">Pledge Now</button>
            </form>
        </div>
    </div>
</div>

<!-- Donation Tab Switching JS -->
<script>
    // Tab switching functionality
    document.querySelectorAll('.donation-tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const tab = this.dataset.tab;
            
            // Update button styles
            document.querySelectorAll('.donation-tab-btn').forEach(b => {
                b.style.color = 'var(--text-muted)';
                b.style.borderBottomColor = 'transparent';
            });
            this.style.color = tab === 'financial' ? 'var(--primary)' : 'var(--accent)';
            this.style.borderBottomColor = tab === 'financial' ? 'var(--primary)' : 'var(--accent)';
            
            // Update form visibility
            document.getElementById('financial-form').style.display = tab === 'financial' ? 'block' : 'none';
            document.getElementById('pledge-form').style.display = tab === 'pledge' ? 'block' : 'none';
        });
    });

    // Amount preset styling
    document.querySelectorAll('input[name="amount_preset"]').forEach(radio => {
        radio.addEventListener('change', function () {
            document.querySelectorAll('input[name="amount_preset"]').forEach(r => {
                r.parentElement.style.borderColor = 'var(--border)';
                r.parentElement.style.color = 'inherit';
            });
            if (this.checked) {
                this.parentElement.style.borderColor = 'var(--primary)';
                this.parentElement.style.color = 'var(--primary)';
            }
        });
    });
    
    // Initialize first preset styling
    if (document.querySelector('input[name="amount_preset"]:checked')) {
        document.querySelector('input[name="amount_preset"]:checked').dispatchEvent(new Event('change'));
    }
</script>

<!-- M-Pesa Payment Details -->
<div class="container" style="padding: 2rem 1.5rem;">
    <div id="mpesa-details" style="background: var(--background); padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid var(--secondary); max-width: 600px; margin-left: auto; margin-right: auto;">
        <h5 style="margin: 0 0 1rem; color: var(--secondary); text-align: center;"><i class="fa-solid fa-mobile-screen" style="margin-right: 0.5rem;"></i>M-Pesa Payment Options</h5>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div style="background: var(--surface); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border); text-align: center;">
                <h6 style="margin: 0 0 0.5rem; color: var(--text-main); font-size: 0.9rem;">PayBill</h6>
                <div style="font-family: monospace; font-size: 1.1rem; font-weight: 600; color: var(--secondary);">247247</div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Account: 342567</div>
            </div>
            
            <div style="background: var(--surface); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border); text-align: center;">
                <h6 style="margin: 0 0 0.5rem; color: var(--text-main); font-size: 0.9rem;">Till Number</h6>
                <div style="font-family: monospace; font-size: 1.1rem; font-weight: 600; color: var(--secondary);">8556345</div>
            </div>
        </div>
        
        <div style="background: rgba(16, 185, 129, 0.1); padding: 0.75rem; border-radius: var(--radius-sm); border-left: 3px solid var(--secondary); font-size: 0.85rem; color: var(--text-muted);">
            <strong>Instructions:</strong> Use either PayBill or Till Number. Enter your phone number as reference for donation tracking.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
