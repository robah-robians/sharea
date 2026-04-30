<?php
session_start();
$dirs = ['admin', 'donor', 'ngo'];
foreach ($dirs as $dir) {
    foreach (glob($dir . '/*.php') as $file) {
        $content = file_get_contents($file);
        // Find if header is required BEFORE session check
        if (preg_match('/require_once.*header\.php.*if\s*\(!isset\(\$_SESSION/s', $content)) {
            echo $file . "\n";
        }
    }
}
