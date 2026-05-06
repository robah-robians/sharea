-- Data protection enhancements
CREATE TABLE IF NOT EXISTS protected_users (
  user_id INT NOT NULL PRIMARY KEY,
  note VARCHAR(255) NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS data_backups (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(50) NOT NULL,
  entity_id INT NULL,
  payload LONGTEXT NOT NULL,
  reason VARCHAR(255) NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Optional: protect the primary admin account
-- INSERT IGNORE INTO protected_users (user_id, note, created_by) VALUES (1, 'Primary protected admin account', 1);
