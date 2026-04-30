<?php
$dirs = [
    __DIR__ . '/admin',
    __DIR__ . '/ngo',
    __DIR__ . '/donor',
    __DIR__ . '/admin/includes',
    __DIR__ . '/ngo/includes',
    __DIR__ . '/donor/includes',
    __DIR__ // root
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $files = glob($dir . '/*.php');
    if (!$files) continue;
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $original = $content;
        
        // Headers/Navs
        $content = str_replace('linear-gradient(135deg, var(--primary), var(--accent))', 'linear-gradient(135deg, var(--primary), var(--accent))', $content);
        $content = preg_replace('/linear-gradient\(135deg, var\(--primary\), rgba\(79, 70, 229, 0\.8\)\)/', 'linear-gradient(135deg, var(--primary), var(--primary-hover))', $content);
        $content = preg_replace('/linear-gradient\(135deg, var\(--secondary\), rgba\(16, 185, 129, 0\.8\)\)/', 'linear-gradient(135deg, var(--primary), var(--accent))', $content);
        
        // Cards Mixes
        $content = preg_replace('/linear-gradient\(135deg, var\(--(success|primary|secondary|warning|info)\), #[a-fA-F0-9]+\)/', 'linear-gradient(135deg, var(--primary), var(--accent))', $content);
        $content = preg_replace('/linear-gradient\(135deg, #[a-fA-F0-9]{6}, #[a-fA-F0-9]{6}\)/i', 'linear-gradient(135deg, var(--primary), var(--accent))', $content);
        
        // Maintenance/Backgrounds
        $content = preg_replace('/linear-gradient\(135deg, rgba\([^)]+\), rgba\([^)]+\)\)/', 'linear-gradient(135deg, rgba(30, 58, 138, 0.1), rgba(20, 184, 166, 0.1))', $content);
        
        // Specific combos
        $content = str_replace('linear-gradient(135deg, var(--primary), var(--accent))', 'linear-gradient(135deg, var(--primary), var(--accent))', $content);
        $content = str_replace('linear-gradient(135deg, var(--primary), var(--accent))', 'linear-gradient(135deg, var(--primary), var(--accent))', $content);
        
        if ($content !== $original) {
            file_put_contents($file, $content);
            echo "Fixed gradients in: " . basename($file) . "\n";
        }
    }
}
echo "Done!\n";
