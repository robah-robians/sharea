<?php
// Database migration - Run this once to add created_by column
require_once __DIR__ . '/includes/db.php';

try {
    // Check if created_by column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM announcements LIKE 'created_by'");
    $column_exists = $stmt->rowCount() > 0;
    
    if (!$column_exists) {
        echo "<h2>Running Migration...</h2>";
        
        // Add created_by column
        $pdo->exec("ALTER TABLE announcements ADD COLUMN created_by INT UNSIGNED NULL AFTER action_link");
        echo "✓ Added 'created_by' column<br>";
        
        // Add foreign key constraint
        $pdo->exec("ALTER TABLE announcements ADD CONSTRAINT fk_announcements_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
        echo "✓ Added foreign key constraint<br>";
        
        echo "<h3 style='color: green;'>Migration completed successfully!</h3>";
    } else {
        echo "<h3 style='color: green;'>✓ 'created_by' column already exists</h3>";
    }
    
    // Verify the column
    $stmt = $pdo->query("DESCRIBE announcements");
    $columns = $stmt->fetchAll();
    echo "<h3>Announcements table structure:</h3>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li><strong>{$col['Field']}</strong> ({$col['Type']})</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='/share_hope/admin/communications.php'>← Back to Communications Hub</a></p>";
    
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
