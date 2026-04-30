CREATE DATABASE IF NOT EXISTS share_hope;
USE share_hope;

-- Drop tables if they exist to allow clean re-imports
-- DROP TABLE IF EXISTS payments;
-- DROP TABLE IF EXISTS donations;
-- DROP TABLE IF EXISTS campaigns;
-- DROP TABLE IF EXISTS categories;
-- DROP TABLE IF EXISTS ngos;
-- DROP TABLE IF EXISTS notifications;
-- DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin', 'ngo', 'donor') NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ngos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    description TEXT,
    mission TEXT,
    contact_details TEXT,
    verification_doc VARCHAR(255),
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

CREATE TABLE campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ngo_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    goal_amount DECIMAL(15,2) NOT NULL,
    current_amount DECIMAL(15,2) DEFAULT 0.00,
    deadline DATE,
    category_id INT,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ngo_id) REFERENCES ngos(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    donor_id INT,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('mpesa', 'card', 'bank', 'inkind') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'pledged') DEFAULT 'pending',
    is_anonymous BOOLEAN DEFAULT FALSE,
    message TEXT,
    transaction_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donation_id INT NOT NULL,
    payment_gateway VARCHAR(50),
    payment_status VARCHAR(50),
    gateway_response TEXT,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE CASCADE
);

CREATE TABLE inkind_donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    donor_id INT,
    donor_name VARCHAR(255),
    donor_email VARCHAR(255),
    donor_phone VARCHAR(50),
    item_category VARCHAR(100) NOT NULL,
    item_description TEXT NOT NULL,
    quantity VARCHAR(100) NOT NULL,
    status ENUM('pledged', 'received', 'distributed') DEFAULT 'pledged',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE activity_logs (
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

CREATE TABLE campaign_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    message TEXT NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
);

CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    action_link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert categories
INSERT INTO categories (name, description) VALUES 
('Health', 'Medical and health related causes'),
('Education', 'Scholastic and educational funding'),
('Disaster Relief', 'Emergency responses and natural disasters'),
('Poverty Alleviation', 'Helping impoverished communities');

-- Add dummy Admin User (password is 'admin123')
INSERT INTO users (role, name, email, phone, password_hash, status) VALUES 
('admin', 'Super Admin', 'admin@sharehope.org', '1234567890', '$2y$10$Y1/qE27/10bEwzI7R88iOuGQyC8Q/4uA479F6gEw7LzJm8x8j8o.q', 'active');
