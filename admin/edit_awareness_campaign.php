<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/activity_logger.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

$campaign_id = intval($_GET['id'] ?? 0);
$redirect    = BASE_URL . "/admin/awareness_campaigns.php";

if (!$campaign_id) {
    $_SESSION['error'] = "Invalid campaign ID.";
    header("Location: " . $redirect);
    exit;
}

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $title           = trim($_POST['title'] ?? '');
    $description     = trim($_POST['description'] ?? '');
    $target_audience = $_POST['target_audience'] ?? 'both';
    $campaign_type   = $_POST['campaign_type'] ?? 'awareness';
    $priority        = $_POST['priority'] ?? 'medium';
    $start_date      = $_POST['start_date'] ?: null;
    $end_date        = $_POST['end_date'] ?: null;
    $action_link     = trim($_POST['action_link'] ?? '') ?: null;
    $is_active       = isset($_POST['is_active']) ? 1 : 0;

    // Fetch existing image
    $existing = $pdo->prepare("SELECT image_url FROM awareness_campaigns WHERE id = ?");
    $existing->execute([$campaign_id]);
    $existing = $existing->fetch();
    $image_url = $existing['image_url'] ?? null;

    // Handle image replacement
    if (!empty($_FILES['campaign_image']['name']) && $_FILES['campaign_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../assets/uploads/images/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['campaign_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $filename = 'awareness_' . time() . '_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['campaign_image']['tmp_name'], $upload_dir . $filename)) {
                $image_url = BASE_URL . '/assets/uploads/images/' . $filename;
            }
        }
    }

    if (empty($title) || empty($description)) {
        $_SESSION['error'] = "Title and description are required.";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE awareness_campaigns
                SET title = ?, description = ?, target_audience = ?, campaign_type = ?,
                    priority = ?, start_date = ?, end_date = ?, action_link = ?, image_url = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $target_audience, $campaign_type, $priority, $start_date, $end_date, $action_link, $image_url, $is_active, $campaign_id]);
            $_SESSION['success'] = "Awareness campaign updated successfully.";
            header("Location: " . $redirect);
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch current data
$stmt = $pdo->prepare("SELECT * FROM awareness_campaigns WHERE id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch();

if (!$campaign) {
    $_SESSION['error'] = "Awareness campaign not found.";
    header("Location: " . $redirect);
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 2.5rem 0; max-width: 1150px;">
    <div class="admin-layout" style="display: flex; gap: 2.5rem; align-items: flex-start;">

        <?php require_once __DIR__ . '/includes/admin_nav.php'; ?>

        <div class="admin-main" style="flex: 1; min-width: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 2rem; margin: 0 0 0.35rem 0; font-weight: 800; color: var(--text-main);">
                        <i class="fa-solid fa-megaphone text-accent" style="margin-right: 0.5rem;"></i>Edit Awareness Campaign
                    </h1>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.95rem;">
                        Modifying: <strong style="color: var(--accent);"><?= h($campaign['title']) ?></strong>
                    </p>
                </div>
                <a href="<?= BASE_URL ?>/admin/awareness_campaigns.php" class="btn btn-outline" style="padding: 0.6rem 1.25rem;">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 0.35rem;"></i> Back to Hub
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div style="background: rgba(239,68,68,0.1); color: var(--danger); border: 1px solid rgba(239,68,68,0.3); padding: 1rem 1.5rem; border-radius: var(--radius-md); font-weight: 600; margin-bottom: 1.5rem;">
                    <?= h($_SESSION['error']) ?> <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm);">
                <div style="padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); background: var(--background);">
                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700;">
                        <i class="fa-solid fa-bolt text-accent" style="margin-right: 0.5rem;"></i>Campaign Parameters
                    </h3>
                </div>

                <form method="POST" enctype="multipart/form-data" style="padding: 2rem; display: flex; flex-direction: column; gap: 1.5rem;">
                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Campaign Title</label>
                        <input type="text" name="title" class="form-control" required value="<?= h($campaign['title']) ?>" style="padding: 0.85rem; background: var(--background);">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Description</label>
                        <textarea name="description" class="form-control" rows="5" required style="padding: 0.85rem; background: var(--background);"><?= h($campaign['description']) ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Target Audience</label>
                            <select name="target_audience" class="form-control" style="padding: 0.85rem; background: var(--background);">
                                <option value="donors" <?= $campaign['target_audience'] === 'donors' ? 'selected' : '' ?>>Private Donors</option>
                                <option value="ngos" <?= $campaign['target_audience'] === 'ngos' ? 'selected' : '' ?>>NGOs</option>
                                <option value="both" <?= $campaign['target_audience'] === 'both' ? 'selected' : '' ?>>Both Donors & NGOs</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Campaign Type</label>
                            <select name="campaign_type" class="form-control" style="padding: 0.85rem; background: var(--background);">
                                <option value="awareness" <?= $campaign['campaign_type'] === 'awareness' ? 'selected' : '' ?>>General Awareness</option>
                                <option value="fundraising" <?= $campaign['campaign_type'] === 'fundraising' ? 'selected' : '' ?>>Fundraising Drive</option>
                                <option value="education" <?= $campaign['campaign_type'] === 'education' ? 'selected' : '' ?>>Educational</option>
                                <option value="emergency" <?= $campaign['campaign_type'] === 'emergency' ? 'selected' : '' ?>>Emergency Alert</option>
                                <option value="seasonal" <?= $campaign['campaign_type'] === 'seasonal' ? 'selected' : '' ?>>Seasonal Campaign</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Priority Level</label>
                            <select name="priority" class="form-control" style="padding: 0.85rem; background: var(--background);">
                                <option value="low" <?= $campaign['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= $campaign['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= $campaign['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="urgent" <?= $campaign['priority'] === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= h(substr($campaign['start_date'] ?? '', 0, 10)) ?>" style="padding: 0.85rem; background: var(--background);">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?= h(substr($campaign['end_date'] ?? '', 0, 10)) ?>" style="padding: 0.85rem; background: var(--background);">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Action Link (Optional)</label>
                        <input type="url" name="action_link" class="form-control" value="<?= h($campaign['action_link'] ?? '') ?>" placeholder="https://example.com/action" style="padding: 0.85rem; background: var(--background);">
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700; display: block; margin-bottom: 0.5rem;">Campaign Image</label>
                        <?php if (!empty($campaign['image_url'])): ?>
                            <div style="margin-bottom: 0.75rem; display: flex; align-items: center; gap: 1rem;">
                                <img src="<?= h($campaign['image_url']) ?>" alt="Current Image" style="width: 120px; height: 80px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                                <span style="font-size: 0.85rem; color: var(--text-muted);">Current asset. Upload a new file to replace it.</span>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="campaign_image" class="form-control" accept="image/*" style="padding: 0.75rem; background: var(--background); border: 2px dashed var(--border);">
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_active" value="1" <?= $campaign['is_active'] ? 'checked' : '' ?> style="width: 1.2rem; height: 1.2rem;">
                            <span style="font-weight: 700; color: var(--text-main);">Campaign is Active (visible on public pages)</span>
                        </label>
                    </div>

                    <div style="display: flex; gap: 1rem; justify-content: flex-end; border-top: 1px solid var(--border); padding-top: 1.5rem;">
                        <a href="<?= BASE_URL ?>/admin/awareness_campaigns.php" class="btn btn-outline" style="padding: 0.75rem 1.5rem;">Cancel</a>
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                            <i class="fa-solid fa-save" style="margin-right: 0.5rem;"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
