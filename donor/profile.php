<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
require_once __DIR__ . '/../includes/header.php';


$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (empty($name)) {
        $error = "Name cannot be empty.";
    } else {
        try {
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

            $_SESSION['user_name'] = $name; // Update session
            $success = "Profile updated successfully.";

            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<div class="container" style="padding: 4rem 0; max-width: 600px;">
    <div style="margin-bottom: 2rem;">
        <a href="<?= BASE_URL ?>/donor/dashboard.php" class="text-primary"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <div
        style="background: var(--surface); padding: 3rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border);">
        <h2 style="margin-top: 0; margin-bottom: 2rem;">Edit Profile</h2>

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

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">

            <div class="form-group">
                <label class="form-label" for="email">Email Address (Cannot be changed)</label>
                <input type="email" id="email" class="form-control" value="<?= h($user['email']) ?>" readonly
                    style="background: var(--background); color: var(--text-muted);">
            </div>

            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= h($user['name']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?= h($user['phone']) ?>" pattern="[0-9+\-\s()]{10,15}" title="Enter a valid phone number (10-15 digits)" placeholder="e.g., +254712345678">
                <small style="color: var(--text-muted); display:block; margin-top: 0.25rem;">Format: +254712345678 or 0712345678</small>
            </div>

            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
                <h4 style="margin-top: 0; margin-bottom: 1rem;">Change Password</h4>
                <p class="text-muted" style="font-size: 0.875rem; margin-bottom: 1.5rem;">Leave blank if you do not wish
                    to change your password.</p>

                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" minlength="8">
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Save Changes</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>