<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header("Location: /share_hope/login.php"); exit;
}
require_once __DIR__ . '/../includes/header.php';

// Check NGO is verified
$stmt = $pdo->prepare("SELECT * FROM ngos WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$ngo = $stmt->fetch();
if (!$ngo || !$ngo['is_verified']) {
    header("Location: /share_hope/ngo/dashboard.php"); exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<div class="container" style="padding: 4rem 0; max-width: 1400px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">
        <?php require_once __DIR__ . '/includes/ngo_nav.php'; ?>
        <div class="admin-main" style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 2rem; margin: 0 0 0.35rem 0; font-weight: 800; color: var(--text-main);">
                        <i class="fa-solid fa-paper-plane text-primary" style="margin-right: 0.5rem;"></i>Submit Campaign Request
                    </h1>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem;">
                        Your proposal will be reviewed by the admin. If approved, they will create and publish the campaign and credit your organisation.
                    </p>
                </div>
                <a href="/share_hope/ngo/dashboard.php" class="btn btn-outline">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 0.35rem;"></i> Back to Dashboard
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div style="background:rgba(239,68,68,0.1);color:var(--danger);padding:1rem 1.5rem;border-radius:var(--radius-md);font-weight:600;margin-bottom:1.5rem;">
                    <i class="fa-solid fa-triangle-exclamation" style="margin-right:0.5rem;"></i><?= h($_SESSION['error']) ?>
                </div><?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Info Banner -->
            <div style="background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.2);border-radius:var(--radius-md);padding:1.25rem 1.5rem;margin-bottom:2rem;display:flex;gap:1rem;align-items:flex-start;">
                <i class="fa-solid fa-circle-info text-primary" style="font-size:1.25rem;margin-top:0.1rem;"></i>
                <div>
                    <strong style="color:var(--text-main);">How the campaign request process works:</strong>
                    <ol style="margin:0.5rem 0 0 1.25rem;color:var(--text-muted);font-size:0.9rem;line-height:1.8;">
                        <li>You fill and submit this form with full campaign details.</li>
                        <li>The admin reviews your request from their dashboard.</li>
                        <li>If approved, the admin creates and publishes the campaign, crediting your organisation.</li>
                        <li>If rejected, you'll see the reason in your dashboard and can resubmit.</li>
                    </ol>
                </div>
            </div>

            <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);">
                <div style="padding:1.5rem 2rem;border-bottom:1px solid var(--border);background:var(--background);">
                    <h3 style="margin:0;font-size:1.1rem;font-weight:700;">
                        <i class="fa-solid fa-bolt text-accent" style="margin-right:0.5rem;"></i>Campaign Proposal Details
                    </h3>
                </div>
                <form action="/share_hope/actions/submit_campaign_request.php" method="POST" enctype="multipart/form-data" style="padding:2rem;">
                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label class="form-label" style="font-weight:700;display:block;margin-bottom:0.5rem;">Campaign Title <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="title" class="form-control" required placeholder="E.g., Clean Water for Marsabit Schools" style="padding:0.85rem;background:var(--background);">
                    </div>

                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label class="form-label" style="font-weight:700;display:block;margin-bottom:0.5rem;">Campaign Description <span style="color:var(--danger)">*</span></label>
                        <textarea name="description" class="form-control" rows="6" required placeholder="Describe the problem, the solution, and how funds will be used. Be as detailed as possible to help the admin evaluate your request." style="padding:0.85rem;background:var(--background);"></textarea>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight:700;display:block;margin-bottom:0.5rem;">Funding Goal (KSh) <span style="color:var(--danger)">*</span></label>
                            <input type="number" step="0.01" min="100" name="goal_amount" class="form-control" required placeholder="50000" style="padding:0.85rem;background:var(--background);">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight:700;display:block;margin-bottom:0.5rem;">Proposed Deadline <span style="color:var(--danger)">*</span></label>
                            <input type="date" name="deadline" class="form-control" required min="<?= date('Y-m-d', strtotime('+7 days')) ?>" style="padding:0.85rem;background:var(--background);">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label class="form-label" style="font-weight:700;display:block;margin-bottom:0.5rem;">Sector / Category <span style="color:var(--danger)">*</span></label>
                        <select name="category_id" class="form-control" required style="padding:0.85rem;background:var(--background);">
                            <option value="">-- Select a category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:2rem;">
                        <label class="form-label" style="font-weight:700;display:block;margin-bottom:0.5rem;">Proposed Cover Image (Optional)</label>
                        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png" style="padding:0.75rem;background:var(--background);border:2px dashed var(--border);">
                        <small style="color:var(--text-muted);display:block;margin-top:0.35rem;">JPG or PNG. The admin may use this or choose a different image when creating the campaign.</small>
                    </div>

                    <div style="display:flex;gap:1rem;justify-content:flex-end;border-top:1px solid var(--border);padding-top:1.5rem;">
                        <a href="/share_hope/ngo/dashboard.php" class="btn btn-outline" style="padding:0.75rem 1.5rem;">Cancel</a>
                        <button type="submit" class="btn btn-primary" style="padding:0.75rem 1.5rem;">
                            <i class="fa-solid fa-paper-plane" style="margin-right:0.5rem;"></i>Submit Request to Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
