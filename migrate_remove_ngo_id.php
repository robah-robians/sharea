<?php
// Database migration to remove ngo_id column from campaigns table
require_once __DIR__ . '/includes/db.php';

try {
    // Check if ngo_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM campaigns WHERE Field = 'ngo_id'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "<h2>Removing ngo_id column...</h2>";
        
        // Drop foreign key constraint if it exists
        $fk_stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'campaigns' AND COLUMN_NAME = 'ngo_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
        $fk = $fk_stmt->fetch();
        
        if ($fk) {
            $pdo->exec("ALTER TABLE campaigns DROP FOREIGN KEY " . $fk['CONSTRAINT_NAME']);
            echo "✓ Dropped foreign key constraint<br>";
        }
        
        // Drop the column
        $pdo->exec("ALTER TABLE campaigns DROP COLUMN ngo_id");
        echo "✓ Removed ngo_id column from campaigns table<br>";
    } else {
        echo "<h2>ngo_id column not found</h2>";
        echo "<p>The column may have already been removed.</p>";
    }
    
    echo "<h3 style='color: green;'>Migration completed successfully!</h3>";
    echo "<p><a href='/share_hope/admin/campaigns_hub.php?tab=deploy'>← Back to Deploy Campaign</a></p>";
    
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
    </style>
</head>
<body>
</body>
</html>
