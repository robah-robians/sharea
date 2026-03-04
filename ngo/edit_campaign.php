<?php
require_once __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header("Location: /share_hope/login.php");
    exit;
}

$campaign_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Get NGO details
$stmt = $pdo->prepare("SELECT id FROM ngos WHERE user_id = ?");
$stmt->execute([$user_id]);
$ngo = $stmt->fetch();

if (!$ngo) {
    echo "NGO profile not found.";
    exit;
}

// Get the campaign, ensure it belongs to this NGO
$stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND ngo_id = ?");
$stmt->execute([$campaign_id, $ngo['id']]);
$campaign = $stmt->fetch();

if (!$campaign) {
    $_SESSION['error'] = "Campaign not found or you do not have permission to edit it.";
    header("Location: /share_hope/ngo/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $goal_amount = floatval($_POST['goal_amount'] ?? 0);
    $deadline = $_POST['deadline'] ?? null;
    $category_id = intval($_POST['category_id'] ?? 1);

    if (empty($title) || empty($description) || $goal_amount <= 0 || empty($deadline)) {
        $error = "Please fill in all required fields validly.";
    } else {
        $image_url = $campaign['image_url']; // keep existing by default

        // Handle image upload if a new one is provided
        if (!empty($_FILES['campaign_image']['name'])) {
            $uploadDir = __DIR__ . '/../assets/uploads/images/';
            $fileInfo = pathinfo($_FILES['campaign_image']['name']);
            $ext = strtolower($fileInfo['extension']);

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $newFilename = uniqid('camp_') . '.' . $ext;
                $destination = $uploadDir . $newFilename;
                if (move_uploaded_file($_FILES['campaign_image']['tmp_name'], $destination)) {
                    $image_url = '/assets/uploads/images/' . $newFilename;
                } else {
                    $error = "Error uploading image.";
                }
            } else {
                $error = "Invalid image extension.";
            }
        }

        if (!$error) {
            try {
                $stmt = $pdo->prepare("UPDATE campaigns SET title=?, description=?, goal_amount=?, deadline=?, category_id=?, image_url=? WHERE id=?");
                $stmt->execute([$title, $description, $goal_amount, $deadline, $category_id, $image_url, $campaign['id']]);

                $success = "Campaign updated successfully!";

                // refresh campaign object
                $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ?");
                $stmt->execute([$campaign['id']]);
                $campaign = $stmt->fetch();

            } catch (Exception $e) {
                $error = "Failed to update campaign: " . $e->getMessage();
            }
        }
    }
}

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();
?>

<div class="container" style="padding: 4rem 0; max-width: 800px;">
    <div style="margin-bottom: 2rem;">
        <a href="/share_hope/ngo/dashboard.php" class="text-primary"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div
        style="background: var(--surface); padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border);">
        <h2 style="margin-top: 0; margin-bottom: 2rem;">Edit Campaign</h2>

        <?php if ($error): ?>
            <div
                style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                <?= h($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div
                style="background: var(--secondary); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                <?= h($success) ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

            <div class="form-group">
                <label class="form-label">Campaign Title</label>
                <input type="text" name="title" class="form-control" value="<?= h($campaign['title']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-control" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $campaign['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= h($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Goal Amount ($)</label>
                    <input type="number" name="goal_amount" class="form-control" min="1" step="0.01"
                        value="<?= h($campaign['goal_amount']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Deadline</label>
                    <input type="date" name="deadline" class="form-control" value="<?= h($campaign['deadline']) ?>"
                        required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="6"
                    required><?= h($campaign['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Upload New Cover Image (Optional)</label>
                <?php if ($campaign['image_url']): ?>
                    <div style="margin-bottom: 1rem;">
                        <img src="<?= h($campaign['image_url']) ?>" alt="Current Campaign Image"
                            style="max-height: 150px; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    </div>
                <?php endif; ?>
                <input type="file" name="campaign_image" class="form-control" accept="image/*">
                <small class="text-muted">Leave blank to keep the current image. Only JPG, PNG, GIF allowed.</small>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Update
                Campaign</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
