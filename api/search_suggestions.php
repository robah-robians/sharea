<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode(['suggestions' => []]);
    exit;
}

$suggestions = [];

try {
    // Search for campaign titles only
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.title, 'campaign' as type
        FROM campaigns c
        WHERE c.status = 'active' AND c.title LIKE ?
        LIMIT 10
    ");
    $stmt->execute(["%$q%"]);
    $campaigns = $stmt->fetchAll();
    
    foreach ($campaigns as $camp) {
        $suggestions[] = [
            'type' => 'campaign',
            'value' => $camp['title'],
            'display' => $camp['title']
        ];
    }
    
    echo json_encode(['suggestions' => $suggestions]);
    
} catch (PDOException $e) {
    echo json_encode(['suggestions' => [], 'error' => 'Database error']);
}
?>
