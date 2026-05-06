
<?php
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from CLI only.\n");
    exit(1);
}

require_once __DIR__ . '/includes/db.php';

$command = $argv[1] ?? '--up';
$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    checksum CHAR(64) NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$files = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($files, SORT_STRING);
if ($command === '--status') {
    $rows = $pdo->query('SELECT migration_name, checksum, applied_at FROM schema_migrations')->fetchAll(PDO::FETCH_ASSOC);
    $applied = [];
    foreach ($rows as $row) {
        $applied[$row['migration_name']] = $row;
    }

    foreach ($files as $file) {
        $name = basename($file);
        $checksum = hash_file('sha256', $file);
        if (isset($applied[$name])) {
            $tag = ($applied[$name]['checksum'] === $checksum) ? 'APPLIED' : 'CHANGED';
            echo "[$tag] $name\n";
        } else {
            echo "[PENDING] $name\n";
        }
    }
    exit(0);
}

if ($command !== '--up') {
    fwrite(STDERR, "Unknown command. Use --status or --up\n");
    exit(1);
}

$find = $pdo->prepare('SELECT checksum FROM schema_migrations WHERE migration_name = ? LIMIT 1');
$insert = $pdo->prepare('INSERT INTO schema_migrations (migration_name, checksum) VALUES (?, ?)');
$appliedCount = 0;
foreach ($files as $file) {
    $name = basename($file);
    $checksum = hash_file('sha256', $file);

    $find->execute([$name]);
    $existing = $find->fetchColumn();

    if ($existing !== false) {
        if ($existing !== $checksum) {
            fwrite(STDERR, "Checksum mismatch for applied migration: $name\n");
            exit(1);
        }
        echo "Skipping $name (already applied)\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "Failed reading $name\n");
        exit(1);
    }

    try {
        $pdo->exec($sql);
        $insert->execute([$name, $checksum]);
        $appliedCount++;
        echo "Applied $name\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "Migration failed ($name): " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Done. New migrations applied: $appliedCount\n";

