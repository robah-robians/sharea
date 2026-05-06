<?php
// Database migration to make ngo_id nullable in campaigns table
require_once __DIR__ . '/includes/db.php';

try {
    // Check current ngo_id column definition
    $stmt = $pdo->query("SHOW COLUMNS FROM campaigns WHERE Field = 'ngo_id'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "<h2>Checking ngo_id column...</h2>";
        echo "<p>Current definition: " . $column['Type'] . " - Null: " . $column['Null'] . "</p>";
        
        if ($column['Null'] === 'NO') {
            echo "<p>Making ngo_id nullable...</p>";
            $pdo->exec("ALTER TABLE campaigns MODIFY COLUMN ngo_id INT NULL");
            echo "✓ ngo_id column is now nullable<br>";
        } else {
            echo "✓ ngo_id column is already nullable<br>";
        }
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
