<?php
// Migration: restore ngo_id column to campaigns table
require_once __DIR__ . '/includes/db.php';

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM campaigns WHERE Field = 'ngo_id'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "<p style='color:green;'>✓ ngo_id column already exists — nothing to do.</p>";
    } else {
        // Add nullable ngo_id column (no FK constraint, since campaigns are now admin-managed)
        $pdo->exec("ALTER TABLE campaigns ADD COLUMN ngo_id INT NULL DEFAULT NULL");
        echo "<p style='color:green;'>✓ ngo_id column restored to campaigns table (nullable, no FK).</p>";
    }

    echo "<h3 style='color:green;'>Done.</h3>";
    echo "<p><a href='/share_hope/admin/dashboard.php'>← Back to Admin Dashboard</a></p>";

} catch (Exception $e) {
    echo "<h3 style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Restore ngo_id Migration</title>
    <style>body { font-family: Arial, sans-serif; padding: 2rem; max-width: 600px; margin: 0 auto; }</style>
</head>
<body></body>
</html>
