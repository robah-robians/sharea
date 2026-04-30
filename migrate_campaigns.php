<?php
// Database migration to add deployment fields to campaigns table
require_once __DIR__ . '/includes/db.php';

try {
    // Check if deployment_date column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM campaigns LIKE 'deployment_date'");
    $has_deployment_date = $stmt->rowCount() > 0;
    
    if (!$has_deployment_date) {
        echo "<h2>Running Migration...</h2>";
        
        // Add deployment_date column
        $pdo->exec("ALTER TABLE campaigns ADD COLUMN deployment_date DATE NULL AFTER deadline");
        echo "✓ Added 'deployment_date' column<br>";
        
        // Add deployment_time column
        $pdo->exec("ALTER TABLE campaigns ADD COLUMN deployment_time TIME NULL AFTER deployment_date");
        echo "✓ Added 'deployment_time' column<br>";
        
        // Add deployment_details column
        $pdo->exec("ALTER TABLE campaigns ADD COLUMN deployment_details LONGTEXT NULL AFTER deployment_time");
        echo "✓ Added 'deployment_details' column<br>";
        
        echo "<h3 style='color: green;'>Migration completed successfully!</h3>";
    } else {
        echo "<h3 style='color: green;'>✓ Deployment columns already exist</h3>";
    }
    
    // Verify the columns
    $stmt = $pdo->query("DESCRIBE campaigns");
    $columns = $stmt->fetchAll();
    echo "<h3>Campaigns table structure:</h3>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li><strong>{$col['Field']}</strong> ({$col['Type']})</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='/share_hope/admin/create_campaign.php'>← Back to Create Campaign</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . h($e->getMessage()) . "</h3>";
    exit(1);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Migration</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 2rem; max-width: 800px; margin: 0 auto; }
        h2, h3 { color: #0066FF; }
        ul { line-height: 1.8; }
    </style>
</head>
<body>
</body>
</html>
