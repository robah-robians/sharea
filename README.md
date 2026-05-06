# SHARE HOPE

A secure, transparent donation platform connecting verified NGOs with donors in Kenya.

**Stack:** PHP (PDO) · MySQL · HTML5 · CSS3 · Vanilla JavaScript  
**Local URL:** `http://localhost/share_hope/`

---

## Documentation

| Document | Description |
|----------|-------------|
| [Project Structure](docs/PROJECT_STRUCTURE.md) | Every folder and file explained |
| [Database Reference](docs/DATABASE.md) | All tables, columns, and relationships |
| [Features & User Roles](docs/FEATURES.md) | What each role can do, full feature list, workflows |
| [Security Reference](docs/SECURITY.md) | CSRF, SQLi, XSS, auth, RBAC implementation details |
| [Developer Guide](docs/DEVELOPER_GUIDE.md) | Setup, common errors & fixes, adding new features |

---

## Quick Start

1. Copy project to `C:\xampp\htdocs\share_hope\`
2. Import `database/database.sql` into MySQL as database `share_hope`
3. Start Apache + MySQL in XAMPP
4. Visit `http://localhost/share_hope/`

**Admin login:** `admin@sharehope.org` — see [Developer Guide](docs/DEVELOPER_GUIDE.md) for password reset steps.

---

## Project Structure (Summary)

```
share_hope/
├── actions/        # Form handlers & POST processors
├── admin/          # Admin panel
├── api/            # JSON endpoints
├── assets/         # CSS, JS, uploads
├── database/       # Schema + migrations
├── docs/           # All documentation
├── donor/          # Donor panel
├── includes/       # Shared PHP (db, header, footer, security)
├── ngo/            # NGO panel
├── scripts/        # Backup/restore scripts
├── tools/          # Dev/debug tools only
└── *.php           # Public pages
```
