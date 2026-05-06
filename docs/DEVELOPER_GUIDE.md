# Share Hope — Developer Setup & Troubleshooting Guide

## Requirements
- XAMPP (Apache + MySQL + PHP 8.0+)
- PHP extensions: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`
- Browser: Any modern browser

---

## Local Setup

### 1. Place project files
```
C:\xampp\htdocs\share_hope\
```

### 2. Import the database
Open phpMyAdmin → Create database `share_hope` → Import:
```
database/database.sql
```

### 3. Configure database connection
Edit `includes/db.php` if your credentials differ:
```php
$host = '127.0.0.1';
$db   = 'share_hope';
$user = 'root';
$pass = '';
```

### 4. Start XAMPP
Start Apache and MySQL from the XAMPP Control Panel.

### 5. Access the app
```
http://localhost/share_hope/
```

---

## Default Admin Account
| Field | Value |
|-------|-------|
| Email | `admin@sharehope.org` |
| Password | Set via `tools/` or phpMyAdmin using BCrypt hash |
| Role | `admin` |

To reset admin password from command line:
```bash
cd C:\xampp\mysql\bin
mysql -u root -e "UPDATE share_hope.users SET password_hash='<bcrypt_hash>' WHERE role='admin';"
```

Generate a BCrypt hash:
```bash
cd C:\xampp\php
php -r "echo password_hash('YourPassword@123', PASSWORD_BCRYPT);"
```

---

## Common Errors & Fixes

### `Unknown column 'X' in field list`
A column referenced in PHP doesn't exist in the database yet.
**Fix:** Run the relevant migration from `database/migrations/` or add the column manually:
```sql
ALTER TABLE share_hope.table_name ADD COLUMN column_name TYPE DEFAULT value;
```

### `Table 'share_hope.X' doesn't exist`
A table referenced in PHP hasn't been created yet.
**Fix:** Check `database/migrations/` for the relevant SQL file and run it, or check `database/database.sql` for the full schema.

### `Cannot add or update a child row: foreign key constraint fails`
A foreign key value being inserted doesn't exist in the parent table.
**Fix:** Either insert the parent record first, or if the column should be nullable:
```sql
ALTER TABLE share_hope.table_name MODIFY COLUMN column_name INT NULL DEFAULT NULL;
```

### `Registration failed` / `Login failed`
1. Check `includes/db.php` credentials match your MySQL setup
2. Verify the `users` table exists and has all required columns (see `docs/DATABASE.md`)
3. Check `email_verified` column exists — if not: `ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0;`

### Session / Login loop
1. Clear browser cookies for `localhost`
2. Check `includes/header.php` session validation logic
3. Use `tools/clear_session.php` in development to force-clear sessions

### CSRF token mismatch
Usually caused by session expiry mid-form.
**Fix:** Refresh the page to get a new token. If persistent, check `includes/security.php` `verify_csrf_token()`.

### File upload fails
1. Check `assets/uploads/campaigns/` and `assets/uploads/docs/` directories exist
2. Check Apache has write permission to those directories
3. Check `php.ini` `upload_max_filesize` and `post_max_size` values

---

## Folder Quick Reference

| I need to... | Go to... |
|-------------|---------|
| Change page layout/styles | `assets/css/style.css` |
| Change frontend behaviour | `assets/js/main.js` |
| Change what a form does | `actions/` |
| Change a page's display | The matching `.php` in `admin/`, `donor/`, `ngo/`, or root |
| Change DB connection | `includes/db.php` |
| Change security logic | `includes/security.php` |
| Change email sending | `includes/mailer.php` |
| Add a DB column/table | Create a new file in `database/migrations/` |
| Check old/removed code | `archive/` |
| Debug a session issue | `tools/debug_session.php` |
| Debug a CSRF issue | `tools/debug_csrf.php` |

---

## Adding a New Feature Checklist

1. **Page** — create `.php` in the appropriate role folder (`admin/`, `donor/`, `ngo/`) or root
2. **Action** — create `actions/your_feature_action.php` for any POST handling
3. **Database** — create `database/migrations/YYYYMMDD_NNN_description.sql` for any schema changes
4. **Security** — add role check, CSRF token, prepared statements, `h()` on all output
5. **Navigation** — add link to the relevant nav partial (`admin/includes/admin_nav.php`, etc.)

---

## Backup & Restore

**Manual backup via phpMyAdmin:** Export → `share_hope` → SQL format

**Script backup (PowerShell):**
```powershell
.\scripts\backup.ps1
```
Backups are saved to `backups/YYYYMMDD_HHMMSS/`

**Restore:**
```powershell
.\scripts\restore.ps1
```

---

## Tools (Development Only)
Files in `tools/` are for debugging during development. **Do not expose these on a production server.**

| Tool | Purpose |
|------|---------|
| `debug_session.php` | Dumps current session state |
| `debug_csrf.php` | Tests CSRF token generation |
| `clear_session.php` | Force-destroys current session |
| `check_session_state.php` | Validates session integrity |
| `test_login.php` | Tests login flow |
| `email_sandbox.php` | Tests email sending |
| `test_system.php` | Full system health check |
