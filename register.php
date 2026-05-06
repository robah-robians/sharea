<?php
session_start();
require_once __DIR__ . '/includes/header.php';
$role = $_GET['role'] ?? 'donor';
if (!in_array($role, ['donor', 'ngo'])) {
    $role = 'donor';
}
?>
<div class="container" style="padding-top: 4rem; padding-bottom: 4rem;">
    <div class="auth-container" style="margin-top: 0; border: 1px solid var(--border); box-shadow: var(--shadow-lg); overflow: hidden;">
        <div style="background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); padding: 2rem; text-align: center;">
            <h2 class="auth-title" style="color: white; margin: 0; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">Create an Account</h2>
            <p class="auth-subtitle" style="color: rgba(255,255,255,0.9); margin: 0.5rem 0 0 0; text-shadow: 0 1px 2px rgba(0,0,0,0.1);">Join us as a <?= ucfirst($role) ?> to make an impact.</p>
        </div>
        <div style="padding: 2.5rem;">
        
        <?php if (isset($_SESSION['error'])): ?>
            <div style="background: var(--danger); color: white; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
                <?= h($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div style="display: flex; gap: 1rem; margin-bottom: 2rem; justify-content: center;">
            <a href="register.php?role=donor" class="btn <?= $role === 'donor' ? 'btn-primary' : 'btn-outline' ?>" style="flex:1; background: <?= $role === 'donor' ? 'linear-gradient(135deg, var(--primary), var(--accent))' : 'transparent' ?>; border: <?= $role === 'donor' ? 'none' : '2px solid var(--primary)' ?>;">Donor</a>
            <a href="register.php?role=ngo" class="btn <?= $role === 'ngo' ? 'btn-primary' : 'btn-outline' ?>" style="flex:1; background: <?= $role === 'ngo' ? 'linear-gradient(135deg, var(--primary), var(--accent))' : 'transparent' ?>; border: <?= $role === 'ngo' ? 'none' : '2px solid var(--primary)' ?>;">NGO</a>
        </div>

        <form action="<?= BASE_URL ?>/actions/register_action.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
            <input type="hidden" name="role" value="<?= h($role) ?>">
            
            <div class="form-group">
                <label class="form-label"><?= $role === 'ngo' ? 'Organization Name' : 'Full Name' ?></label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number (Optional)</label>
                <input type="tel" name="phone" class="form-control" pattern="[0-9+\-\s()]{10,15}" title="Enter a valid phone number (10-15 digits)" placeholder="e.g., +254712345678">
                <small style="color: var(--text-muted); display:block; margin-top: 0.25rem;">Format: +254712345678 or 0712345678</small>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="password_field" name="password" class="form-control" required minlength="8" style="flex: 1; padding-right: 2.5rem;">
                    <button type="button" onclick="togglePassword('password_field')" class="btn btn-text" style="position: absolute; right: 0.5rem; padding: 0.5rem; color: var(--text-muted);">
                        <i id="pwd_icon" class="fa-solid fa-eye"></i>
                    </button>
                </div>
                <small style="color: var(--text-muted); display:block; margin-top: 0.5rem;">Min 8 chars, uppercase, lowercase, number, special char (!@#$%^&*)</small>
            </div>
            
            <?php if ($role === 'ngo'): ?>
                <div class="form-group">
                    <label class="form-label">Mission Statement</label>
                    <textarea name="mission" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Verification Document (PDF/JPG)</label>
                    <input type="file" name="verification_doc" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required style="padding: 0.5rem; background: var(--border);">
                    <small style="color: var(--text-muted); display:block; margin-top: 0.25rem;">Required for admin approval.</small>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; background: linear-gradient(135deg, var(--primary), var(--accent)); border: none; box-shadow: 0 4px 14px rgba(0, 102, 255, 0.39);">Register</button>
            <p style="text-align: center; margin-top: 1.5rem; color: var(--text-muted);">
                Already have an account? <a href="<?= BASE_URL ?>/login.php" class="text-primary" style="font-weight: 500;">Log in here</a>.
            </p>
        </form>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById('pwd_icon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Real-time validation
document.addEventListener('DOMContentLoaded', function() {
    const phoneInput = document.querySelector('input[name="phone"]');
    const goalInput = document.querySelector('input[name="goal_amount"]');
    
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            const value = this.value;
            const isValid = /^[0-9+\-\s()]{10,15}$/.test(value) || value === '';
            this.style.borderColor = isValid ? '' : 'var(--danger)';
        });
    }
    
    if (goalInput) {
        goalInput.addEventListener('input', function() {
            const value = parseFloat(this.value);
            const isValid = value >= 100 && value <= 1000000;
            this.style.borderColor = isValid ? '' : 'var(--danger)';
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
