# Share Hope — Database Reference

## Connection
- **Host:** 127.0.0.1
- **Database:** `share_hope`
- **User:** `root`
- **Charset:** `utf8mb4`
- **Driver:** PDO (Prepared Statements only — no raw queries)

---

## Tables

### `users`
Core authentication table for all roles.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `role` | ENUM(`admin`, `ngo`, `donor`) | |
| `name` | VARCHAR(255) | |
| `email` | VARCHAR(255) UNIQUE | |
| `phone` | VARCHAR(50) NULL | |
| `password_hash` | VARCHAR(255) | BCrypt |
| `status` | ENUM(`active`, `suspended`) | Default: `active` |
| `email_verified` | TINYINT(1) | Default: `0` |
| `created_at` | TIMESTAMP | Default: `CURRENT_TIMESTAMP` |

---

### `ngos`
Extended profile for users with `role = 'ngo'`.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `user_id` | INT FK → `users.id` | CASCADE DELETE |
| `mission` | TEXT | |
| `verification_doc` | VARCHAR(255) | Path to uploaded doc |
| `is_verified` | TINYINT(1) | Default: `0`. Set by admin |

---

### `campaigns`
All active/completed fundraising campaigns (admin-deployed).

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `ngo_id` | INT NULL FK → `ngos.id` | NULL = admin-created |
| `title` | VARCHAR(255) | |
| `description` | TEXT | |
| `goal_amount` | DECIMAL(15,2) | |
| `current_amount` | DECIMAL(15,2) | Default: `0.00` |
| `deadline` | DATE | |
| `category_id` | INT NULL FK → `categories.id` | |
| `status` | ENUM(`active`, `completed`, `cancelled`) | |
| `image_url` | VARCHAR(255) NULL | |
| `deployment_date` | DATE NULL | |
| `deployment_time` | TIME NULL | |
| `deployment_details` | TEXT NULL | |
| `created_at` | TIMESTAMP | |

---

### `campaign_requests`
NGO-submitted campaign suggestions pending admin review.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `ngo_id` | INT FK → `ngos.id` | CASCADE DELETE |
| `title` | VARCHAR(255) | |
| `description` | TEXT | |
| `goal_amount` | DECIMAL(15,2) | |
| `deadline` | DATE | |
| `category_id` | INT NULL FK → `categories.id` | |
| `image_url` | VARCHAR(255) NULL | |
| `status` | ENUM(`pending`, `approved`, `rejected`) | Default: `pending` |
| `rejection_reason` | TEXT NULL | |
| `created_at` | TIMESTAMP | |

---

### `campaign_updates`
Progress updates posted by NGOs on campaigns, reviewed by admin.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `campaign_id` | INT FK → `campaigns.id` | |
| `message` | TEXT | |
| `image_url` | VARCHAR(255) NULL | |
| `status` | ENUM(`pending`, `approved`, `rejected`) | Default: `approved` |
| `created_at` | TIMESTAMP | |

---

### `categories`
Campaign sector classifications.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `name` | VARCHAR(100) | |
| `description` | TEXT NULL | |

---

### `donations`
All financial donation records.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `campaign_id` | INT FK → `campaigns.id` | |
| `user_id` | INT NULL FK → `users.id` | NULL = anonymous |
| `amount` | DECIMAL(15,2) | |
| `payment_method` | VARCHAR(50) | e.g. `mpesa`, `card` |
| `status` | ENUM(`pending`, `completed`, `failed`) | |
| `is_anonymous` | TINYINT(1) | Default: `0` |
| `message` | TEXT NULL | Donor support message |
| `created_at` | TIMESTAMP | |

---

### `inkind_donations`
In-kind / pledge donation records.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `campaign_id` | INT FK → `campaigns.id` | |
| `donor_name` | VARCHAR(255) | |
| `donor_email` | VARCHAR(255) | |
| `donor_phone` | VARCHAR(50) | |
| `item_category` | VARCHAR(100) | |
| `item_description` | TEXT | |
| `quantity` | VARCHAR(100) | |
| `status` | ENUM(`pledged`, `received`, `cancelled`) | Default: `pledged` |
| `created_at` | TIMESTAMP | |

---

### `payments`
Payment gateway transaction records.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `donation_id` | INT FK → `donations.id` | |
| `gateway` | VARCHAR(50) | e.g. `mpesa` |
| `transaction_ref` | VARCHAR(255) | Gateway reference |
| `status` | VARCHAR(50) | |
| `created_at` | TIMESTAMP | |

---

### `announcements`
Admin-posted platform-wide announcements.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `title` | VARCHAR(255) | |
| `message` | TEXT | |
| `is_public` | TINYINT(1) | `1` = visible to all users |
| `action_link` | VARCHAR(255) NULL | Optional CTA link |
| `created_at` | TIMESTAMP | |

---

### `notifications`
Per-user in-app notifications.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `user_id` | INT FK → `users.id` | |
| `message` | TEXT | |
| `is_read` | TINYINT(1) | Default: `0` |
| `created_at` | TIMESTAMP | |

---

### `activity_logs`
Audit trail of all significant user/admin actions.

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT PK AUTO_INCREMENT | |
| `user_id` | INT NULL FK → `users.id` | |
| `action` | VARCHAR(255) | |
| `details` | TEXT NULL | |
| `ip_address` | VARCHAR(45) | |
| `created_at` | TIMESTAMP | |

---

## Key Relationships
```
users ──< ngos ──< campaigns
                └─< campaign_requests
users ──< donations >── campaigns
users ──< notifications
campaigns ──< campaign_updates
campaigns ──< inkind_donations
donations ──< payments
```

---

## Setup
1. Create database: `CREATE DATABASE share_hope CHARACTER SET utf8mb4;`
2. Import schema: `mysql -u root share_hope < database/database.sql`
3. Run any new migrations in order from `database/migrations/`
