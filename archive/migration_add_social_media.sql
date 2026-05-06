-- Add Social Media Links Management Table
CREATE TABLE IF NOT EXISTS social_media_links (
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  platform VARCHAR(50) NOT NULL UNIQUE,
  platform_name VARCHAR(100) NOT NULL,
  icon_class VARCHAR(100) NOT NULL,
  url VARCHAR(255) NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  sort_order INT(11) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default social media platforms (can be edited by admin)
INSERT IGNORE INTO social_media_links (platform, platform_name, icon_class, url, is_active, sort_order) 
VALUES 
('twitter', 'Twitter', 'fa-brands fa-twitter', '#', 0, 1),
('facebook', 'Facebook', 'fa-brands fa-facebook-f', '#', 0, 2),
('instagram', 'Instagram', 'fa-brands fa-instagram', '#', 0, 3),
('whatsapp', 'WhatsApp', 'fa-brands fa-whatsapp', '#', 0, 4),
('linkedin', 'LinkedIn', 'fa-brands fa-linkedin-in', '#', 0, 5),
('telegram', 'Telegram', 'fa-brands fa-telegram', '#', 0, 6),
('youtube', 'YouTube', 'fa-brands fa-youtube', '#', 0, 7),
('tiktok', 'TikTok', 'fa-brands fa-tiktok', '#', 0, 8);
