-- ============================================================
-- ShareHope Platform Spec Alignment Migration
-- Run this once in phpMyAdmin or MySQL CLI
-- ============================================================

-- 1. Create campaign_requests table (NGO proposals for admin review)
CREATE TABLE IF NOT EXISTS campaign_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ngo_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    goal_amount DECIMAL(15,2) NOT NULL,
    deadline DATE NOT NULL,
    category_id INT,
    image_url VARCHAR(255),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    rejection_reason TEXT,
    reviewed_by INT,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ngo_id) REFERENCES ngos(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 2. Add campaign update moderation fields
ALTER TABLE campaign_updates
    ADD COLUMN IF NOT EXISTS status ENUM('pending','approved','rejected') DEFAULT 'pending' AFTER image_url,
    ADD COLUMN IF NOT EXISTS submitted_by INT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL AFTER submitted_by,
    ADD COLUMN IF NOT EXISTS reviewed_by INT NULL AFTER rejection_reason,
    ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL AFTER reviewed_by;

-- 3. Add archived status to campaigns if not present
ALTER TABLE campaigns
    MODIFY COLUMN status ENUM('pending','active','completed','cancelled','archived','rejected') DEFAULT 'active';

-- 4. Add rejection_reason to ngos table
ALTER TABLE ngos
    ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL AFTER is_verified;

-- 5. Set existing campaign_updates to approved (they were posted live before)
UPDATE campaign_updates SET status = 'approved' WHERE status IS NULL OR status = '';
