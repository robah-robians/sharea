<?php
// Run this script to create the awareness_campaigns table
require_once __DIR__ . '/includes/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `awareness_campaigns` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `title` varchar(255) NOT NULL,
      `description` text NOT NULL,
      `target_audience` enum('donors','ngos','both') NOT NULL,
      `campaign_type` enum('awareness','fundraising','education','emergency','seasonal') NOT NULL,
      `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
      `start_date` date DEFAULT NULL,
      `end_date` date DEFAULT NULL,
      `action_link` varchar(500) DEFAULT NULL,
      `is_active` tinyint(1) DEFAULT 1,
      `created_by` int(11) NOT NULL,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_target_audience` (`target_audience`),
      KEY `idx_is_active` (`is_active`),
      KEY `idx_priority` (`priority`),
      KEY `idx_created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql);
    echo "✅ Awareness campaigns table created successfully!<br>";
    
    // Insert sample data
    $stmt = $pdo->prepare("INSERT INTO awareness_campaigns (title, description, target_audience, campaign_type, priority, action_link, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $sample_campaigns = [
        [
            'Join Our Holiday Giving Drive',
            'Help us spread joy this holiday season by supporting families in need. Your donation can make a real difference in someone\'s life.',
            'both',
            'fundraising',
            'high',
            '/share_hope/campaigns.php',
            1,
            1
        ],
        [
            'New NGO Verification Process',
            'We\'ve updated our NGO verification process to ensure maximum transparency and trust. Learn about the new requirements and benefits.',
            'ngos',
            'education',
            'medium',
            '/share_hope/register.php?role=ngo',
            1,
            1
        ],
        [
            'Donor Impact Report Available',
            'See how your donations have made a difference! Our quarterly impact report shows the real-world results of your generosity.',
            'donors',
            'awareness',
            'medium',
            '/share_hope/campaigns.php',
            1,
            1
        ]
    ];
    
    foreach ($sample_campaigns as $campaign) {
        $stmt->execute($campaign);
    }
    
    echo "✅ Sample awareness campaigns added successfully!<br>";
    echo "<br><strong>You can now:</strong><br>";
    echo "1. Visit the admin dashboard → Awareness Campaigns to manage campaigns<br>";
    echo "2. View the homepage to see active campaigns displayed<br>";
    echo "3. Create new campaigns targeting specific audiences<br>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>