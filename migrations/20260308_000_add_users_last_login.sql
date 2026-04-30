-- Ensure users.last_login exists for staff/audit modules.
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'last_login'
);

SET @ddl := IF(
    @col_exists = 0,
    'ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER created_at',
    'SELECT 1'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
