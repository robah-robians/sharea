# Share Hope — Features & User Roles

## Platform Summary
Share Hope is a donation platform connecting verified NGOs with donors in Kenya. The admin acts as the trusted intermediary — verifying NGOs, deploying campaigns, and maintaining full platform oversight.

---

## User Roles

### Admin (Primary)
Full system control. Only one primary admin exists.

**Can:**
- Approve or reject NGO registration applications
- Deploy, edit, and undeploy campaigns
- Review and action NGO campaign requests
- Approve or reject NGO campaign progress updates
- Moderate announcements (publish, hide, restore, delete)
- Moderate donations (flag, unflag, void, unvoid, hide, restore)
- View and export full financial ledger
- Manage all users (view, suspend, reset passwords)
- Toggle platform maintenance mode
- View activity logs and audit trails
- Generate and export reports

**Cannot:**
- There are no restrictions — full platform authority

---

### Admin (Assistant)
Read-only staff role for monitoring.

**Can:**
- View performance dashboards
- Monitor activity logs
- Generate reports

**Cannot:**
- Deploy or modify campaigns
- Approve NGOs or requests
- Alter any database state

---

### NGO (Verified)
Institutional partner. Must be approved by admin before gaining access.

**Can:**
- Submit campaign suggestions to admin for review
- Post progress updates on campaigns (pending admin approval)
- View their own campaign request history and status
- Make financial donations
- View their donor activity

**Cannot:**
- Directly publish, edit, or delete campaigns
- View other NGOs' data or donor database
- Access any admin panel

---

### Donor
End user / general public.

**Can:**
- Browse all active campaigns
- Make financial donations via M-Pesa or Card
- Make in-kind / pledge donations
- Download PDF tax receipts
- View personal donation history
- View personal impact statistics

**Cannot:**
- Create or manage campaigns
- Access other users' data
- Access admin or NGO panels

---

## Core Features

### Campaign Management
- Admin deploys campaigns with title, description, goal, deadline, category, cover image
- Campaigns track `current_amount` vs `goal_amount` with a live progress bar
- Visual confetti celebration triggers when a campaign hits 100% of its goal
- Campaigns can be undeployed (removed from public) without deleting data
- NGOs submit campaign suggestions via `ngo/submit_campaign.php` — admin reviews and deploys

### Donation System
- **Financial donations** via M-Pesa (PayBill: 247247, Till: 8556345) or Card
- **In-kind / pledge donations** — donors pledge physical items; NGO coordinates pickup
- Anonymous donation option available
- Support message field on every donation
- Automated PDF receipt generation via `html2pdf.js`
- Donation ledger with admin moderation (flag, void, hide)

### NGO Verification
- NGOs register with mission statement and verification document (PDF/image)
- Admin reviews document and approves or rejects via `admin/stakeholders.php`
- Unverified NGOs see a "pending verification" screen and cannot access features
- Verified NGOs appear on the public impact map

### Interactive Impact Map
- Leaflet.js / OpenStreetMap integration on `impact.php`
- Plots verified NGO locations with geographic coordinates
- Visible to all public visitors for full transparency

### Announcements
- Admin posts platform-wide announcements
- Visible to all logged-in users via the dashboard
- Latest announcement shown on NGO dashboard
- Admin can hide/restore/delete announcements

### Notifications
- In-app notification bell in the header (fixed top-right)
- Auto-refreshes every 30 seconds via `api/get_notification_count.php`
- Notifications sent to admin when NGO submits a campaign request
- Mark all read functionality

### Awareness Campaigns
- Separate from fundraising campaigns — informational/awareness content
- Managed by admin via `admin/awareness_campaigns.php`
- Public detail page at `awareness_detail.php`

### Maintenance Mode
- Admin toggles via `actions/toggle_maintenance.php`
- All public pages redirect to `maintenance.php` when active
- `includes/critical_write_guard.php` blocks all DB writes during maintenance
- Admin panel remains accessible during maintenance

### Security Features
See `docs/SECURITY.md` for full details.
- BCrypt password hashing
- CSRF tokens on all forms
- PDO prepared statements (no raw SQL)
- XSS prevention via `h()` on all output
- Session timeout (4 hours)
- Secure password reset via time-expiring email tokens
- Role-based access control on every page and action

### Social Sharing
- 1-click WhatsApp and Twitter share buttons on every campaign page
- Zero external dependencies — pure HTML links

### Responsive Design
- Mobile-first CSS with Flexbox/Grid layouts
- Slide-out hamburger menu on mobile
- Google Fonts: Outfit (modern, trustworthy aesthetic)
- CSS custom properties (variables) for consistent theming

---

## Payment Integration

### M-Pesa (Sandbox)
- STK Push via `actions/process_mpesa.php`
- PayBill: **247247** | Account: **342567**
- Till Number: **8556345**
- Localized for Kenya (KSh currency)

### Card Payments
- Handled via `actions/process_payment.php`
- Visa, Mastercard, American Express

---

## Workflow: NGO Campaign Request → Live Campaign

```
1. NGO submits request via ngo/submit_campaign.php
        ↓
2. Admin receives notification + sees request in dashboard inbox
        ↓
3. Admin reviews: Approve or Reject (with reason)
        ↓
4. If Approved → campaign deployed to public campaigns listing
   If Rejected → NGO sees rejection reason on their dashboard
        ↓
5. NGO posts progress updates → Admin approves → Visible on donate page
```

---

## Workflow: Donor Donation

```
1. Donor browses campaigns.php or donate.php
        ↓
2. Selects amount + payment method (M-Pesa / Card)
        ↓
3. Submits form → actions/donate_action.php → actions/process_payment.php
        ↓
4. Payment confirmed → donation recorded → campaign current_amount updated
        ↓
5. Donor downloads PDF receipt from donor/receipts.php
```
