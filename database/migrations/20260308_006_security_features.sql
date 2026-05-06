-- Add tables for security features

-- Rate limiting/login attempts tracking
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(11),
  email VARCHAR(255),
  ip_address VARCHAR(45),
  success TINYINT(1) DEFAULT 0,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_ip (email, ip_address, attempted_at),
  INDEX idx_user_id (user_id, attempted_at)
);

-- Email verification tokens
CREATE TABLE IF NOT EXISTS email_verification (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(11) NOT NULL UNIQUE,
  token VARCHAR(100) NOT NULL UNIQUE,
  expires_at TIMESTAMP,
  is_verified TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add columns to users table if they don't exist
ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN IF NOT EXISTS account_locked_until TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS failed_login_attempts INT(11) DEFAULT 0;
