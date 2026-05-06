-- Add 'under_consideration' status to campaign_requests
ALTER TABLE campaign_requests
    MODIFY COLUMN status ENUM('pending','under_consideration','approved','rejected') DEFAULT 'pending';
