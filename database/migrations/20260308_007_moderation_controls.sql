-- Add moderation controls for announcements and donations ledger
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS is_hidden TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS hidden_by INT NULL;
ALTER TABLE announcements ADD COLUMN IF NOT EXISTS hidden_at TIMESTAMP NULL;

ALTER TABLE donations ADD COLUMN IF NOT EXISTS hidden_in_ledger TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE donations ADD COLUMN IF NOT EXISTS moderation_state VARCHAR(20) NOT NULL DEFAULT 'normal';
ALTER TABLE donations ADD COLUMN IF NOT EXISTS moderation_note TEXT NULL;
ALTER TABLE donations ADD COLUMN IF NOT EXISTS moderated_by INT NULL;
ALTER TABLE donations ADD COLUMN IF NOT EXISTS moderated_at TIMESTAMP NULL;
