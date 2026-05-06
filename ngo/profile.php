<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';


$user_id = $_SESSION['user_id'];

// Get user and NGO info
$stmt = $pdo->prepare("
    SELECT u.*, n.mission, n.description, n.contact_details, n.verification_doc, n.is_verified
    FROM users u 
    LEFT JOIN ngos n ON u.id = n.user_id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $mission = trim($_POST['mission'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $contact_details = trim($_POST['contact_details'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (empty($name) || empty($mission)) {
        $error = "Organization name and mission are required.";
    } else {
        try {
            $pdo->beginTransaction();

            // Validate phone if provided
            if (!empty($phone) && !preg_match('/^[0-9+\-\s()]{10,15}$/', $phone)) {
                throw new Exception("Invalid phone number format.");
            }

            // Update user table
            if (!empty($new_password)) {
                if (strlen($new_password) < 8) {
                    throw new Exception("New password must be at least 8 characters.");
                }
                $hash = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, password_hash = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $hash, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $user_id]);
            }

            // Update NGO table
            $stmt = $pdo->prepare("UPDATE ngos SET mission = ?, description = ?, contact_details = ? WHERE user_id = ?");
            $stmt->execute([$mission, $description, $contact_details, $user_id]);

            $pdo->commit();
            $_SESSION['user_name'] = $name;
            $success = "Profile updated successfully.";

            // Refresh data
            $stmt = $pdo->prepare("
                SELECT u.*, n.mission, n.description, n.contact_details, n.verification_doc, n.is_verified
                FROM users u 
                LEFT JOIN ngos n ON u.id = n.user_id 
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>

<div class="admin-layout">
    <?php include __DIR__ . '/includes/ngo_nav.php'; ?>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1><i class="fa-solid fa-building"></i> Organization Profile</h1>
            <?php if($profile['is_verified']): ?>
                <span class="badge" style="background: var(--success); color: white; padding: 0.5rem 1rem; border-radius: var(--radius-md);">
                    <i class="fa-solid fa-check-circle"></i> Verified
                </span>
            <?php else: ?>
                <span class="badge" style="background: var(--warning); color: white; padding: 0.5rem 1rem; border-radius: var(--radius-md);">
                    <i class="fa-solid fa-clock"></i> Pending Verification
                </span>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-top: 2rem;">
            <div class="profile-form" style="background: var(--surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border);">
                <?php if ($error): ?>
                    <div style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                        <?= h($error) ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div style="background: var(--success); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                        <?= h($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

                    <div class="form-group">
                        <label class="form-label">Email Address (Cannot be changed)</label>
                        <input type="email" class="form-control" value="<?= h($profile['email']) ?>" readonly style="background: var(--background); color: var(--text-muted);">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Organization Name</label>
                        <input type="text" name="name" class="form-control" value="<?= h($profile['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?= h($profile['phone']) ?>" pattern="[0-9+\-\s()]{10,15}" title="Enter a valid phone number" placeholder="e.g., +254712345678">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mission Statement</label>
                        <textarea name="mission" class="form-control" rows="4" required><?= h($profile['mission']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Organization Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Tell us more about your organization..."><?= h($profile['description']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Details</label>
                        <textarea name="contact_details" class="form-control" rows="3" placeholder="Address, additional contact information..."><?= h($profile['contact_details']) ?></textarea>
                    </div>

                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
                        <h4 style="margin-bottom: 1rem;">Change Password</h4>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" minlength="8" placeholder="Leave blank to keep current password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                        <i class="fa-solid fa-save"></i> Save Changes
                    </button>
                </form>
            </div>

            <div class="profile-info" style="background: var(--surface); padding: 2rem; border-radius: var(--radius-lg); border: 1px solid var(--border); height: fit-content;">
                <h3 style="margin-top: 0;">Account Status</h3>
                
                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Verification Status:</span>
                        <span style="color: var(--<?= $profile['is_verified'] ? 'success' : 'warning' ?>); font-weight: 600;">
                            <?= $profile['is_verified'] ? 'Verified' : 'Pending' ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Account Status:</span>
                        <span style="color: var(--success); font-weight: 600;">Active</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Member Since:</span>
                        <span><?= date('M Y', strtotime($profile['created_at'])) ?></span>
                    </div>
                </div>

                <?php if($profile['verification_doc']): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <h4>Verification Document</h4>
                        <a href="<?= h($profile['verification_doc']) ?>" target="_blank" class="btn btn-outline" style="width: 100%;">
                            <i class="fa-solid fa-file-pdf"></i> View Document
                        </a>
                    </div>
                <?php endif; ?>

                <div style="padding: 1rem; background: var(--background); border-radius: var(--radius-md); border-left: 4px solid var(--primary);">
                    <h4 style="margin-top: 0; color: var(--primary);">Need Help?</h4>
                    <p style="margin-bottom: 0; font-size: 0.875rem; color: var(--text-muted);">
                        Contact our support team if you need assistance with your profile or verification process.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>