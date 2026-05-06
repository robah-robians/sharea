# Share Hope — Project Structure

## Root Directory
```
share_hope/
├── actions/              # All POST form handlers & backend processors
├── admin/                # Admin panel pages (protected, admin role only)
│   └── includes/         # Admin-specific partials (admin_nav.php)
├── api/                  # JSON API endpoints (AJAX calls)
├── archive/              # Deprecated / superseded files (not in use)
├── assets/               # All static frontend assets
│   ├── css/              # style.css — single source of truth for all styles
│   ├── js/               # main.js — single source of truth for all scripts
│   └── uploads/          # User-uploaded files
│       ├── campaigns/    # Campaign cover images & NGO request images
│       ├── docs/         # NGO verification documents (PDF/images)
│       └── images/       # General platform images
├── backups/              # Timestamped MySQL database backups
├── database/             # Database schema and migration history
│   └── migrations/       # Ordered SQL migration files
├── docs/                 # Project documentation (markdown files)
├── donor/                # Donor panel pages (protected, donor role only)
│   └── includes/         # Donor-specific partials (donor_nav.php)
├── includes/             # Shared PHP includes used across the entire app
├── logs/                 # System logs (email logs, etc.)
├── ngo/                  # NGO panel pages (protected, ngo role only)
│   └── includes/         # NGO-specific partials (ngo_nav.php)
├── scripts/              # PowerShell maintenance scripts (backup/restore)
├── tools/                # Developer/debug tools (NOT for production use)
├── .htaccess             # Apache rewrite rules
└── *.php                 # Public-facing pages (index, login, register, etc.)
```

---

## Public Pages (Root `*.php`)
| File | Purpose |
|------|---------|
| `index.php` | Homepage — hero, featured campaigns, stats |
| `login.php` | Login form for all roles |
| `register.php` | Registration form (donor / NGO toggle) |
| `campaigns.php` | Public campaign listing |
| `donate.php` | Donation page for a specific campaign |
| `payment.php` | Payment processing page (M-Pesa / Card) |
| `payment_success.php` | Post-payment confirmation page |
| `donation_receipt.php` | Downloadable PDF receipt page |
| `receipt.php` | Receipt viewer |
| `impact.php` | Global impact map & statistics |
| `about.php` | About page with FAQ |
| `awareness_detail.php` | Single awareness campaign detail page |
| `ngo_profile.php` | Public NGO profile page |
| `forgot_password.php` | Password reset request form |
| `reset_password.php` | Password reset via token link |
| `verify_email.php` | Email verification handler |
| `maintenance.php` | Maintenance mode holding page |
| `message_detail.php` | Message/communication detail view |

---

## Actions (`actions/`)
All files here handle POST requests only and redirect back after processing.

| File | Purpose |
|------|---------|
| `login_action.php` | Authenticates user, sets session |
| `logout_action.php` | Destroys session, redirects to login |
| `register_action.php` | Creates donor/NGO user account |
| `forgot_password_action.php` | Sends password reset email |
| `reset_password_action.php` | Updates password via reset token |
| `create_campaign_action.php` | Admin creates/deploys a new campaign |
| `edit_campaign_action.php` | Admin edits an existing campaign |
| `undeploy_campaign_action.php` | Admin removes a campaign from public |
| `submit_campaign_request.php` | NGO submits a campaign suggestion |
| `admin_review_campaign_request.php` | Admin approves/rejects NGO requests |
| `admin_approve_ngo.php` | Admin verifies/rejects an NGO |
| `admin_create_announcement.php` | Admin posts a platform announcement |
| `admin_moderate_announcement.php` | Admin hides/restores announcements |
| `admin_moderate_donation.php` | Admin flags/voids donations |
| `admin_operations.php` | Admin system operations (maintenance toggle, etc.) |
| `admin_reset_user_password.php` | Admin resets any user's password |
| `admin_review_update.php` | Admin approves/rejects campaign updates |
| `donate_action.php` | Processes a donation submission |
| `process_payment.php` | Handles payment gateway response |
| `process_mpesa.php` | M-Pesa STK push handler |
| `process_inkind.php` | Processes in-kind/pledge donations |
| `post_campaign_update.php` | NGO posts a campaign progress update |
| `update_pledge_status.php` | Updates in-kind pledge status |
| `export_admin.php` | Admin data export (CSV/PDF) |
| `export_ngo.php` | NGO data export |
| `mark_notifications_read.php` | Marks user notifications as read |
| `toggle_maintenance.php` | Toggles platform maintenance mode |

---

## Shared Includes (`includes/`)
| File | Purpose |
|------|---------|
| `db.php` | PDO database connection + `h()`, CSRF functions |
| `header.php` | HTML head, navbar, notification bell |
| `footer.php` | HTML footer, social links, main JS include |
| `security.php` | BCrypt, CSRF, password validation, rate limiting |
| `security_clean.php` | Input sanitization helpers |
| `security_email.php` | Email-specific security helpers |
| `functions.php` | General utility functions |
| `activity_logger.php` | Logs user actions to `activity_logs` table |
| `mailer.php` | PHPMailer wrapper for sending emails |
| `session_manager.php` | Session validation and timeout logic |
| `check_maintenance.php` | Redirects to maintenance page if mode is on |
| `critical_write_guard.php` | Prevents writes during maintenance mode |

---

## Admin Panel (`admin/`)
| File | Purpose |
|------|---------|
| `dashboard.php` | Main admin overview + NGO request inbox |
| `campaigns_hub.php` | Full campaign management (deploy, edit, undeploy) |
| `create_campaign.php` | Campaign creation form |
| `edit_campaign.php` | Campaign edit form |
| `campaign_performance.php` | Per-campaign analytics |
| `campaign_updates_review.php` | Approve/reject NGO progress updates |
| `stakeholders.php` | NGO & donor management |
| `manage_users.php` | All users management |
| `view_user.php` | Single user detail view |
| `donations.php` | Donations overview |
| `all_donations.php` | Full donations ledger with moderation |
| `all_pledges.php` | In-kind pledges management |
| `finance_controls.php` | Financial audit & transaction controls |
| `transaction_controls.php` | Flag/void/hide transactions |
| `announcements.php` | Platform announcements management |
| `awareness_campaigns.php` | Awareness campaigns management |
| `edit_awareness_campaign.php` | Edit an awareness campaign |
| `communications.php` | Platform communications |
| `reports.php` | Generated reports |
| `analytics.php` | Platform-wide analytics |
| `data_export.php` | Export data to CSV/PDF |
| `activity_logs.php` | View system activity logs |
| `staff.php` | Admin staff management |
| `emergency_alert.php` | Send emergency platform alerts |
| `ngo_health_score.php` | NGO performance scoring |

---

## NGO Panel (`ngo/`)
| File | Purpose |
|------|---------|
| `dashboard.php` | NGO overview — assigned campaigns, requests |
| `submit_campaign.php` | Submit a campaign suggestion to admin |
| `profile.php` | NGO profile management |
| `donors.php` | View donors for NGO campaigns |

---

## Donor Panel (`donor/`)
| File | Purpose |
|------|---------|
| `dashboard.php` | Donor overview — giving history, stats |
| `donations.php` | Full donation history |
| `pledges.php` | In-kind pledge history |
| `receipts.php` | Downloadable tax receipts |
| `find_campaigns.php` | Browse & search campaigns |
| `impact.php` | Personal impact visualization |
| `profile.php` | Donor profile management |

---

## API Endpoints (`api/`)
| File | Purpose |
|------|---------|
| `get_notification_count.php` | Returns unread notification count as JSON |
| `search_suggestions.php` | Returns campaign search suggestions as JSON |

---

## Database (`database/`)
| File | Purpose |
|------|---------|
| `database.sql` | Full schema — import this to set up a fresh database |
| `migrations/` | Incremental SQL changes ordered by date prefix |
