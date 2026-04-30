<?php
session_start();
require_once __DIR__ . '/../includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: /share_hope/login.php");
    exit;
}

$campaign_id = intval($_GET['id'] ?? 0);
if (!$campaign_id) {
    $_SESSION['error'] = "Invalid initiative ID.";
    header("Location: /share_hope/admin/campaigns_hub.php?tab=performance");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

// Fetch campaign data
$stmt = $pdo->prepare("
    SELECT c.*
    FROM campaigns c
    WHERE c.id = ?
");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    $_SESSION['error'] = "Initiative not found or has been terminated.";
    header("Location: /share_hope/admin/campaigns_hub.php?tab=performance");
    exit;
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div class="container" style="padding: 4rem 0; max-width: 1400px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">

        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 2rem; margin: 0 0 0.35rem 0; font-weight: 800; color: var(--text-main);">
                        <i class="fa-solid fa-pen-to-square text-primary" style="margin-right: 0.5rem;"></i>Edit Initiative Parameters
                    </h1>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem;">
                        Modifying deployment: <strong style="color: var(--primary);"><?= h($campaign['title']) ?></strong>
                    </p>
                </div>
                <a href="/share_hope/admin/campaigns_hub.php?tab=performance" class="btn btn-outline" style="padding: 0.6rem 1.25rem;">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 0.35rem;"></i> Back to Hub
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div style="background: rgba(239,68,68,0.1); color: var(--danger); border: 1px solid rgba(239,68,68,0.3); padding: 1rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; margin-bottom: 1.5rem;">
                    <i class="fa-solid fa-triangle-exclamation" style="margin-right: 0.5rem;"></i><?= h($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Edit Form -->
            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm);">
                <!-- Form Header -->
                <div style="padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); background: var(--background);">
                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">
                        <i class="fa-solid fa-bolt text-accent" style="margin-right: 0.5rem;"></i>Parameter Configuration
                    </h3>
                </div>

                <form action="/share_hope/actions/edit_campaign_action.php" method="POST" enctype="multipart/form-data" style="padding: 2rem;">
                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                    <input type="hidden" name="campaign_id" value="<?= $campaign_id ?>">
                    <input type="hidden" name="redirect_url" value="/share_hope/admin/campaigns_hub.php?tab=performance">

                    <!-- Operation Title -->
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-tag" style="margin-right: 0.35rem; color: var(--primary);"></i>Operation Title
                        </label>
                        <input type="text" name="title" class="form-control" required
                               value="<?= h($campaign['title']) ?>"
                               style="padding: 0.85rem; background: var(--background);">
                    </div>

                    <!-- Fiscal Target & Deadline -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">
                                <i class="fa-solid fa-coins" style="margin-right: 0.35rem; color: var(--primary);"></i>Fiscal Target (KSh)
                            </label>
                            <input type="number" step="0.01" name="goal_amount" class="form-control" required
                                   value="<?= h($campaign['goal_amount']) ?>"
                                   style="padding: 0.85rem; background: var(--background);">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">
                                <i class="fa-regular fa-calendar" style="margin-right: 0.35rem; color: var(--primary);"></i>Closure Timeline (Deadline)
                            </label>
                            <input type="date" name="deadline" class="form-control" required
                                   value="<?= h(substr($campaign['deadline'], 0, 10)) ?>"
                                   style="padding: 0.85rem; background: var(--background);">
                        </div>
                    </div>

                    <!-- Sector Classification -->
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-layer-group" style="margin-right: 0.35rem; color: var(--primary);"></i>Sector Classification
                        </label>
                        <select name="category_id" class="form-control" required style="padding: 0.85rem; background: var(--background);">
                            <option value="">-- Designate Category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $campaign['category_id'] ? 'selected' : '' ?>>
                                    <?= h($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Deployment Status -->
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-toggle-on" style="margin-right: 0.35rem; color: var(--primary);"></i>Deployment Status
                        </label>
                        <select name="status" class="form-control" style="padding: 0.85rem; background: var(--background);">
                            <option value="active" <?= $campaign['status'] === 'active' ? 'selected' : '' ?>>Active — Live on Network</option>
                            <option value="completed" <?= $campaign['status'] === 'completed' ? 'selected' : '' ?>>Completed — Target Reached</option>
                            <option value="archived" <?= $campaign['status'] === 'archived' ? 'selected' : '' ?>>Archived — Removed from Public Feed</option>
                        </select>
                    </div>

                    <!-- Mission Telemetry (Description) -->
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-file-lines" style="margin-right: 0.35rem; color: var(--primary);"></i>Mission Telemetry (Description)
                        </label>
                        <textarea name="description" class="form-control" rows="7"
                                  style="padding: 0.85rem; background: var(--background);"><?= h($campaign['description']) ?></textarea>
                    </div>

                    <!-- Cover Image -->
                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-image" style="margin-right: 0.35rem; color: var(--primary);"></i>System Asset (Cover Image)
                        </label>
                        <?php if ($campaign['image_url']): ?>
                            <div style="margin-bottom: 0.75rem; display: flex; align-items: center; gap: 1rem;">
                                <img src="<?= h($campaign['image_url']) ?>" alt="Current Image"
                                     style="width: 120px; height: 80px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                                <span style="font-size: 0.85rem; color: var(--text-muted);">Current asset. Upload a new file below to replace it.</span>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png"
                               style="padding: 0.75rem; background: var(--background); border: 2px dashed var(--border);">
                    </div>

                    <!-- Actions -->
                    <div style="display: flex; gap: 1rem; justify-content: flex-end; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                        <a href="/share_hope/admin/campaigns_hub.php?tab=performance" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">
                            Abort Changes
                        </a>
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                            <i class="fa-solid fa-satellite-dish" style="margin-right: 0.5rem;"></i>Transmit Parameter Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
