<?php
session_start();
// admin/create_campaign.php

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "Access denied. Admin login required.";
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

require_once __DIR__ . '/../includes/header.php';

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();
?>

<div class="container" style="padding: 2.5rem 0;">
    <div style="max-width: 800px; margin: 0 auto; background: var(--surface); padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
        <h2 style="margin-bottom: 2rem;">Deploy New Initiative</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                <?= h($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/actions/create_campaign_action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
            
            <div class="form-group">
                <label class="form-label">Operation Title</label>
                <input type="text" name="title" class="form-control" required placeholder="E.g., Global Hydration Implementation Phase 1">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Fiscal Requirement (KSh)</label>
                    <input type="number" step="0.01" name="goal_amount" class="form-control" required placeholder="50000">
                </div>
                <div class="form-group">
                    <label class="form-label">Logic Closure Timeline (Deadline)</label>
                    <input type="date" name="deadline" class="form-control" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Deployment Date</label>
                    <input type="date" name="deployment_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Deployment Time</label>
                    <input type="time" name="deployment_time" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Sector Classification</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Designate System Category</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Mission Telemetry (Description)</label>
                <textarea name="description" class="form-control" rows="6" required placeholder="Transmit full operation thesis, infrastructure required, and output estimates..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Deployment Details</label>
                <textarea name="deployment_details" class="form-control" rows="4" placeholder="Provide specific deployment information, location, resources allocated, team involved, etc..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">System Asset (Cover Image)</label>
                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png" style="padding: 0.5rem; background: var(--border);">
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-bullhorn"></i> Deploy Initiative</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
