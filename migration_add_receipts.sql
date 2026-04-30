-- Add donation receipts tracking table
CREATE TABLE IF NOT EXISTS donation_receipts (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  donation_id INT(11) NOT NULL UNIQUE,
  receipt_number VARCHAR(50) NOT NULL UNIQUE,
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  emailed_at TIMESTAMP NULL,
  emailed_to VARCHAR(255),
  FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE CASCADE,
  INDEX idx_receipt_number (receipt_number),
  INDEX idx_emailed_at (emailed_at)
);
