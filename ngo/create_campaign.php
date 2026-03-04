<?php
// ngo/create_campaign.php
require_once __DIR__ . '/../includes/header.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    $_SESSION['error'] = "Access denied. NGO login required.";
    header("Location: /share_hope/login.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();
?>

<div class="container" style="padding: 4rem 0;">
    <div style="max-width: 800px; margin: 0 auto; background: var(--surface); padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
        <h2 style="margin-bottom: 2rem;">Create New Campaign</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                <?= h($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="/share_hope/actions/create_campaign_action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
            
            <div class="form-group">
                <label class="form-label">Campaign Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label class="form-label">Funding Goal ($)</label>
                    <input type="number" step="0.01" name="goal_amount" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Deadline</label>
                    <input type="date" name="deadline" class="form-control" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Select a category</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Campaign Description</label>
                <textarea name="description" class="form-control" rows="6" required placeholder="Describe the problem, your solution, and how the funds will be used..."></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Cover Image (JPG/PNG)</label>
                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png" style="padding: 0.5rem; background: var(--border);">
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <a href="/share_hope/ngo/dashboard.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Publish Campaign</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
