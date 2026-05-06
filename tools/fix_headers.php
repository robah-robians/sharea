<?php
$dirs = ['admin', 'donor', 'ngo'];
$fixed = 0;

foreach ($dirs as $dir) {
    foreach (glob($dir . '/*.php') as $file) {
        $content = file_get_contents($file);
        
        // Exclude stakeholders and impact since we just fixed them manually
        if (strpos($file, 'stakeholders.php') !== false || strpos($file, 'impact.php') !== false) {
            continue;
        }

        // We are looking for the pattern where require_once header is before the auth block.
        // The auth block looks like:
        // if (!isset($_SESSION['user_id']) || ... ) {
        //     header("Location: /share_hope/login.php");
        //     exit;
        // }
        // We will extract the exact require_once header line, remove it, and place it AFTER the auth block.
        
        if (preg_match('/(require_once\s+__DIR__\s*\.\s*\'\/.*?includes\/header\.php\';\s*)(.*?if\s*\(!isset\(\$_SESSION.*?}s*)/s', $content, $matches)) {
            $header_include = $matches[1];
            $auth_block = $matches[2];
            
            // Reorder them
            // Remove the header include from its original spot
            $new_content = str_replace($header_include . $auth_block, $auth_block . "\n" . trim($header_include) . ";\n", $content);
            
            if ($new_content !== $content) {
                file_put_contents($file, $new_content);
                echo "Fixed: $file\n";
                $fixed++;
            }
        }
    }
}

echo "Total fixed: $fixed\n";
