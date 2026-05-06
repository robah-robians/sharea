<?php
// Database migration to fix campaign image URLs
require_once __DIR__ . '/includes/db.php';

try {
    echo "<h2>Fixing Campaign Image URLs...</h2>";
    
    // Update campaigns table - fix image URLs that don't have /share_hope/ prefix
    $stmt = $pdo->query("SELECT id, image_url FROM campaigns WHERE image_url IS NOT NULL AND image_url != ''");
    $campaigns = $stmt->fetchAll();
    
    $updated = 0;
    foreach ($campaigns as $campaign) {
        $old_url = $campaign['image_url'];
        
        // If URL starts with /assets/ but not /share_hope/assets/, add the prefix
        if (strpos($old_url, '/assets/') === 0 && strpos($old_url, '/share_hope/assets/') !== 0) {
            $new_url = '/share_hope' . $old_url;
            $update_stmt = $pdo->prepare("UPDATE campaigns SET image_url = ? WHERE id = ?");
            $update_stmt->execute([$new_url, $campaign['id']]);
            $updated++;
            echo "✓ Updated campaign {$campaign['id']}: {$old_url} → {$new_url}<br>";
        }
    }
    
    // Also update awareness_campaigns if they have images
    $stmt = $pdo->query("SELECT id, image_url FROM awareness_campaigns WHERE image_url IS NOT NULL AND image_url != ''");
    $awareness = $stmt->fetchAll();
    
    foreach ($awareness as $campaign) {
        $old_url = $campaign['image_url'];
        
        if (strpos($old_url, '/assets/') === 0 && strpos($old_url, '/share_hope/assets/') !== 0) {
            $new_url = '/share_hope' . $old_url;
            $update_stmt = $pdo->prepare("UPDATE awareness_campaigns SET image_url = ? WHERE id = ?");
            $update_stmt->execute([$new_url, $campaign['id']]);
            $updated++;
            echo "✓ Updated awareness campaign {$campaign['id']}: {$old_url} → {$new_url}<br>";
        }
    }
    
    echo "<h3 style='color: green;'>Migration completed! Updated {$updated} image URLs.</h3>";
    echo "<p><a href='/share_hope/admin/campaigns_hub.php?tab=performance'>← Back to Campaigns Hub</a></p>";
    
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
