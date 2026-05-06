# Share Hope — Security Reference

## Overview
Every layer of the application is hardened against the OWASP Top 10. This document describes what is implemented, where it lives, and how it works.

---

## 1. Authentication — `includes/security.php`

### Password Hashing
- All passwords hashed with **BCrypt** via `password_hash($password, PASSWORD_BCRYPT)`
- Verified with `password_verify()` — never compared as plain text
- Minimum strength enforced before hashing: 8+ chars, uppercase, lowercase, number, special character

### Session Security
- Sessions use `httponly`, `samesite=Strict` cookie flags
- Every session stores `login_time` and `login_validated` — sessions without these are destroyed immediately
- **4-hour timeout** — sessions older than 14400 seconds are auto-destroyed in `includes/header.php`
- Session is re-validated on every page load

### Password Reset Flow
- Token generated with `bin2hex(random_bytes(32))` — cryptographically secure
- Token stored hashed in the database with an expiry timestamp
- Link is single-use and time-limited
- Handled by: `forgot_password.php` → `actions/forgot_password_action.php` → `reset_password.php` → `actions/reset_password_action.php`

---

## 2. CSRF Protection — `includes/db.php`

Every form that mutates data includes a CSRF token.

**Generation:**
```php
// In includes/db.php
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
```

**In every form:**
```html
<input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
```

**Verification at top of every action file:**
```php
verify_csrf_token($_POST['csrf_token'] ?? '');
```

Uses `hash_equals()` for timing-safe comparison.

**Covered forms:** Login, Register, Donate, All admin actions, NGO submissions, Password reset.

---

## 3. SQL Injection Prevention — PDO Prepared Statements

**Rule:** No raw SQL queries anywhere in the codebase. Every query uses prepared statements.

```php
// Correct — always used
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// Never used
$pdo->query("SELECT * FROM users WHERE email = '$email'");
```

PDO is configured with `PDO::ATTR_EMULATE_PREPARES => false` to force true prepared statements at the driver level.

---

## 4. XSS Prevention — `h()` function

All dynamic output is escaped before rendering.

```php
// In includes/db.php
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
```

Used on every `<?= ?>` output of user-supplied data throughout all views.

---

## 5. Role-Based Access Control (RBAC)

Every protected page checks role at the top before any output:

```php
// Admin pages
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /share_hope/login.php'); exit;
}

// NGO pages
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'ngo') {
    header('Location: /share_hope/login.php'); exit;
}

// Donor pages
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header('Location: /share_hope/login.php'); exit;
}
```

Action files also verify role before processing any POST data.

---

## 6. File Upload Security — `actions/register_action.php`, `actions/submit_campaign_request.php`

- Extension whitelist enforced: only `pdf`, `jpg`, `jpeg`, `png` allowed
- Files renamed with `uniqid()` — original filename never used
- Stored outside web-accessible paths where possible
- MIME type should be added as a future improvement

---

## 7. Maintenance Mode Write Guard — `includes/critical_write_guard.php`

Prevents any database writes during maintenance mode. Called at the top of `includes/db.php`. Exempts only the toggle action itself so the admin can turn maintenance off.

---

## 8. Email Verification — `verify_email.php`

- Token generated on registration with `bin2hex(random_bytes(32))`
- Currently set to auto-verify on registration (testing mode)
- To enable: uncomment the token generation block in `actions/register_action.php`

---

## Security Checklist for New Features

When adding any new feature, verify:

- [ ] All form inputs validated server-side (never trust client)
- [ ] CSRF token added to every state-changing form
- [ ] All DB queries use prepared statements
- [ ] All output wrapped in `h()`
- [ ] Role check at top of every new page/action
- [ ] File uploads use extension whitelist + `uniqid()` rename
- [ ] No sensitive data stored in session beyond user_id, role, name
