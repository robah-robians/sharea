<?php
require_once __DIR__ . '/includes/header.php';

$campaign_id = $_GET['campaign_id'] ?? 0;
$stmt = $pdo->prepare("SELECT c.*, n.user_id, u.name as ngo_name FROM campaigns c JOIN ngos n ON c.ngo_id = n.id JOIN users u ON n.user_id = u.id WHERE c.id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    echo "<div class='container' style='padding:4rem 0;'><h2>Campaign Not Found.</h2></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch Campaign Updates
$stmt = $pdo->prepare("SELECT * FROM campaign_updates WHERE campaign_id = ? ORDER BY created_at DESC");
$stmt->execute([$campaign_id]);
$updates = $stmt->fetchAll();
?>

<div class="hero" style="padding: 4rem 0; background: var(--surface); border-bottom: 1px solid var(--border);">
    <div class="hero" style="padding: 4rem 0; background: var(--surface); border-bottom: 1px solid var(--border);">
        <div class="container" style="text-align: left;">
            <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem; text-align: left;"><?= h($campaign['title']) ?></h1>
            <p style="color: var(--text-muted); text-align: left; margin: 0;">By <?= h($campaign['ngo_name']) ?> <i
                    class="fa-solid fa-circle-check text-secondary"></i></p>
        </div>
    </div>

    <div class="container" style="padding: 4rem 1.5rem;">
        <?php if (isset($_SESSION['error'])): ?>
            <div
                style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                <?= h($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <style>
            @media (min-width: 768px) {
                .donate-grid {
                    display: grid;
                    grid-template-columns: 3fr 2fr;
                    gap: 3rem;
                    align-items: start;
                }
            }

            @media (max-width: 767px) {
                .donate-grid {
                    display: flex;
                    flex-direction: column;
                    gap: 2rem;
                }
            }
        </style>
        <div class="donate-grid">

            <!-- Main Content Column -->
            <div>
                <?php if ($campaign['image_url']): ?>
                    <img src="<?= h($campaign['image_url']) ?>" alt="Campaign Banner"
                        style="width: 100%; border-radius: var(--radius-lg); margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
                <?php endif; ?>

                <div
                    style="background: var(--surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); margin-bottom: 3rem;">
                    <h3 style="margin-top: 0; margin-bottom: 1.5rem;">About This Campaign</h3>
                    <div style="line-height: 1.8; color: var(--text-muted); font-size: 1.05rem; white-space: pre-wrap;">
                        <?= h($campaign['description']) ?>
                    </div>
                </div>

                <!-- Social Share -->
                <div style="margin-bottom: 3rem; display: flex; align-items: center; gap: 1rem;">
                    <span style="font-weight: 600; color: var(--text-muted);">Share to help:</span>
                    <?php
                    $shareUrl = "http://localhost/share_hope/donate.php?campaign_id=" . $campaign['id'];
                    $shareText = "Support this amazing cause: " . $campaign['title'];
                    ?>
                    <a href="https://wa.me/?text=<?= urlencode($shareText . " " . $shareUrl) ?>" target="_blank"
                        class="btn"
                        style="background: #25D366; color: white; padding: 0.5rem 1rem; font-size: 0.875rem;"><i
                            class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                    <a href="https://twitter.com/intent/tweet?text=<?= urlencode($shareText) ?>&url=<?= urlencode($shareUrl) ?>"
                        target="_blank" class="btn"
                        style="background: #1DA1F2; color: white; padding: 0.5rem 1rem; font-size: 0.875rem;"><i
                            class="fa-brands fa-twitter"></i> Twitter</a>
                </div>

                <div
                    style="background: var(--surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                        <h3 style="margin: 0;"><i class="fa-solid fa-timeline text-primary"></i> Transparency & Impact
                            Updates</h3>
                        <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'ngo' && $_SESSION['user_id'] == $campaign['user_id']): ?>
                            <button onclick="document.getElementById('update-form-container').style.display='block'"
                                class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Post
                                Update</button>
                        <?php endif; ?>
                    </div>

                    <!-- Hidden form for NGO to post updates -->
                    <div id="update-form-container"
                        style="display: none; background: var(--background); padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem; border: 1px dashed var(--primary);">
                        <h4 style="margin-top: 0; margin-bottom: 1rem;">Post New Impact Update</h4>
                        <form action="/share_hope/actions/post_campaign_update.php" method="POST"
                            enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                            <input type="hidden" name="campaign_id" value="<?= $campaign['id'] ?>">
                            <div class="form-group">
                                <label class="form-label">Message / Proof of Funds Usage</label>
                                <textarea name="message" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Proof Image (Optional)</label>
                                <input type="file" name="update_image" class="form-control" accept="image/*">
                            </div>
                            <div style="display: flex; gap: 1rem;">
                                <button type="submit" class="btn btn-primary">Publish Update</button>
                                <button type="button" class="btn btn-outline"
                                    onclick="document.getElementById('update-form-container').style.display='none'">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <?php if (empty($updates)): ?>
                        <p class="text-muted" style="text-align: center; padding: 2rem;">No impact updates have been posted
                            for this campaign yet.</p>
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
                                        <img src="<?= h($update['image_url']) ?>" alt="Update Proof"
                                            style="max-height: 300px; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Donation Form Column -->
            <div
                style="background: var(--surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--primary); position: sticky; top: 2rem;">

                <!-- Toggle Tabs -->
                <div style="display: flex; gap: 0; margin-bottom: 2rem; border-bottom: 2px solid var(--border);">
                    <button type="button" id="tab-financial" onclick="switchDonationType('financial')"
                        style="flex: 1; padding: 0.75rem 0; border: none; background: none; font-size: 1.05rem; font-weight: 600; color: var(--primary); border-bottom: 3px solid var(--primary); cursor: pointer; transition: 0.3s;">Financial</button>
                    <button type="button" id="tab-inkind" onclick="switchDonationType('inkind')"
                        style="flex: 1; padding: 0.75rem 0; border: none; background: none; font-size: 1.05rem; font-weight: 600; color: var(--text-muted); border-bottom: 3px solid transparent; cursor: pointer; transition: 0.3s;">Pledge
                        Items</button>
                </div>

                <!-- Financial Donation Form -->
                <form id="form-financial" action="/share_hope/actions/donate_action.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                    <input type="hidden" name="campaign_id" value="<?= h($campaign['id']) ?>">
                    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                        <label
                            style="flex: 1; min-width: 80px; text-align: center; border: 2px solid var(--border); padding: 1rem; border-radius: var(--radius-md); cursor: pointer; transition: var(--transition);">
                            <input type="radio" name="amount_preset" value="25" style="display: none;"
                                onclick="document.getElementById('custom_amount').value='25'">
                            <span style="font-size: 1.25rem; font-weight: 600;">KSh 25</span>
                        </label>
                        <label
                            style="flex: 1; min-width: 80px; text-align: center; border: 2px solid var(--border); padding: 1rem; border-radius: var(--radius-md); cursor: pointer; transition: var(--transition);">
                            <input type="radio" name="amount_preset" value="50" style="display: none;"
                                onclick="document.getElementById('custom_amount').value='50'" checked>
                            <span style="font-size: 1.25rem; font-weight: 600;">KSh 50</span>
                        </label>
                        <label
                            style="flex: 1; min-width: 80px; text-align: center; border: 2px solid var(--border); padding: 1rem; border-radius: var(--radius-md); cursor: pointer; transition: var(--transition);">
                            <input type="radio" name="amount_preset" value="100" style="display: none;"
                                onclick="document.getElementById('custom_amount').value='100'">
                            <span style="font-size: 1.25rem; font-weight: 600;">KSh 100</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Or Custom Amount (KSh)</label>
                        <input type="number" step="0.01" min="1" name="amount" id="custom_amount" class="form-control"
                            value="50" style="font-size: 1.25rem; font-weight: 600;" required>
                    </div>

                    <h4 style="margin: 1.5rem 0 1rem;">Payment Method</h4>
                    <div style="display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                        <div
                            style="border: 1px solid var(--primary); border-radius: var(--radius-md); background: var(--surface);">
                            <label
                                style="display: flex; align-items: center; gap: 1rem; padding: 1rem; cursor: pointer;"
                                onclick="toggleMpesa(true)">
                                <input type="radio" name="payment_method" value="mpesa" checked>
                                <span style="flex-grow: 1; font-weight: 600;">M-Pesa</span>
                                <i class="fa-solid fa-mobile-screen"
                                    style="font-size: 1.25rem; color: var(--secondary);"></i>
                            </label>
                            <div id="mpesa-details" style="padding: 0 1.5rem 1.5rem 1.5rem;">
                                <label class="form-label" style="font-size: 0.875rem; color: var(--text-muted);">M-Pesa
                                    Phone Number</label>
                                <input type="text" name="mpesa_phone" class="form-control"
                                    placeholder="07XX XXX XXX or 2547XX XXX XXX" style="margin-bottom:0;">
                            </div>
                        </div>

                        <label
                            style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); cursor: pointer; background: var(--background);"
                            onclick="toggleMpesa(false)" id="card-label">
                            <input type="radio" name="payment_method" value="card">
                            <span style="flex-grow: 1; font-weight: 600;">Credit/Debit Card</span>
                            <i class="fa-solid fa-credit-card" style="font-size: 1.25rem; color: #6366f1;"></i>
                        </label>
                    </div>

                    <script>
                        function toggleMpesa(show) {
                            const mpesaDetails = document.getElementById('mpesa-details');
                            const cardLabel = document.getElementById('card-label');
                            if (show) {
                                mpesaDetails.style.display = 'block';
                                cardLabel.style.borderColor = 'var(--border)';
                            } else {
                                mpesaDetails.style.display = 'none';
                                cardLabel.style.borderColor = 'var(--primary)';
                            }
                        }
                    </script>

                    <div class="form-group">
                        <label class="form-label">Support Message</label>
                        <textarea name="message" class="form-control" rows="2" placeholder="Great cause!"></textarea>
                    </div>

                    <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="is_anonymous" id="anon" value="1">
                        <label for="anon" style="user-select: none; font-size: 0.875rem;">Make donation
                            anonymous</label>
                    </div>

                    <button type="submit" class="btn btn-primary"
                        style="width: 100%; font-size: 1.125rem; padding: 1rem;"><i class="fa-solid fa-heart"></i>
                        Donate Now</button>
                </form>

                <!-- In-Kind Donation Form -->
                <form id="form-inkind" action="/share_hope/actions/process_inkind.php" method="POST"
                    style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                    <input type="hidden" name="campaign_id" value="<?= h($campaign['id']) ?>">

                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div
                            style="background: rgba(245, 158, 11, 0.1); border-left: 3px solid var(--accent); padding: 1rem; margin-bottom: 1.5rem; font-size: 0.875rem;">
                            <i class="fa-solid fa-circle-info" style="color: var(--accent);"></i> Consider <a
                                href="/share_hope/login.php" class="text-primary">logging in</a> to track your pledges, or
                            pledge as a guest below.
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Category of Goods</label>
                        <select name="item_category" class="form-control" required style="font-size: 1rem;">
                            <option value="Food">Canned Food / Non-Perishables</option>
                            <option value="Clothing">Clothing / Shoes</option>
                            <option value="Medical Supplies">Medical Supplies / Hygiene Kits</option>
                            <option value="Books/Education">Books / Educational Materials</option>
                            <option value="Other">Other Items</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Approximate Quantity</label>
                        <input type="text" name="quantity" class="form-control" placeholder="e.g. 5 bags, 20 boxes"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description of Items</label>
                        <textarea name="item_description" class="form-control" rows="3"
                            placeholder="Describe the items you are pledging to donate..." required></textarea>
                    </div>

                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="form-group">
                            <label class="form-label">Your Name</label>
                            <input type="text" name="donor_name" class="form-control" required>
                        </div>
                        <div style="display: flex; gap: 1rem;">
                            <div class="form-group" style="flex:1;">
                                <label class="form-label">Email</label>
                                <input type="email" name="donor_email" class="form-control" required>
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label class="form-label">Phone</label>
                                <input type="text" name="donor_phone" class="form-control" required>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div
                        style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.5;">
                        <i class="fa-solid fa-truck-ramp-box"></i> Note: Pledging an item notifies the NGO. They will
                        contact you to arrange drop-off or pickup.
                    </div>

                    <button type="submit" class="btn btn-secondary"
                        style="width: 100%; font-size: 1.125rem; padding: 1rem;"><i
                            class="fa-solid fa-hand-holding-hand"></i> Submit Pledge</button>
                </form>
            </div>

            <script>
                function switchDonationType(type) {
                    const btnFin = document.getElementById('tab-financial');
                    const btnInk = document.getElementById('tab-inkind');
                    const frmFin = document.getElementById('form-financial');
                    const frmInk = document.getElementById('form-inkind');

                    if (type === 'financial') {
                        frmFin.style.display = 'block';
                        frmInk.style.display = 'none';
                        btnFin.style.color = 'var(--primary)';
                        btnFin.style.borderBottomColor = 'var(--primary)';
                        btnInk.style.color = 'var(--text-muted)';
                        btnInk.style.borderBottomColor = 'transparent';
                    } else {
                        frmFin.style.display = 'none';
                        frmInk.style.display = 'block';
                        btnInk.style.color = 'var(--primary)';
                        btnInk.style.borderBottomColor = 'var(--primary)';
                        btnFin.style.color = 'var(--text-muted)';
                        btnFin.style.borderBottomColor = 'transparent';
                    }
                }
            </script>
        </div>
    </div>

    <!-- Simple JS for Presets Styling -->
    <script>
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
        if (document.querySelector('input[name="amount_preset"]:checked')) {
            document.querySelector('input[name="amount_preset"]:checked').dispatchEvent(new Event('change'));
        }
    </script>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>