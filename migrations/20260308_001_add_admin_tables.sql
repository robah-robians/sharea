-- Database Migration for Share Hope - Admin Features
-- Run this file to add the required tables for Activity Logs, Campaign Updates, and Announcements

-- 1. Activity Logs Table (for audit trail tracking)
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    action_type ENUM('create', 'update', 'delete', 'approve', 'deny', 'suspend', 'export', 'login', 'other') DEFAULT 'other',
    entity_type VARCHAR(50),
    entity_id INT,
    entity_name VARCHAR(255),
    description TEXT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (admin_id, created_at),
    INDEX (action_type, created_at)
);

-- 2. Campaign Updates Table (for impact updates and health score calculation)
CREATE TABLE IF NOT EXISTS campaign_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    message TEXT NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);

-- 3. Announcements Table (for global broadcasts)
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    action_link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Verify tables exist
SELECT 'Tables created successfully!' as status;
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'share_hope' AND TABLE_NAME IN ('activity_logs', 'campaign_updates', 'announcements');
