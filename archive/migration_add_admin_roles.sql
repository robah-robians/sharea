-- Add role levels for admin hierarchy
ALTER TABLE users ADD COLUMN IF NOT EXISTS role_level INT(11) DEFAULT 1;
-- role_level: 1=assistant_admin, 2=admin, 3=super_admin

-- Add permissions tracking
CREATE TABLE IF NOT EXISTS admin_permissions (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  admin_id INT(11) NOT NULL,
  permission VARCHAR(100) NOT NULL,
  granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_permission (admin_id, permission),
  FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Default permissions by role
-- super_admin (3): all permissions
-- admin (2): can manage NGOs, campaigns, donations, view staff (cannot create/delete staff)
-- assistant_admin (1): can view dashboards, activity logs, reports only
