<?php
// includes/critical_write_guard.php

if (!function_exists('enforce_critical_write_lock')) {
    function enforce_critical_write_lock(array $allowList = []): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $criticalLockFile = __DIR__ . '/../.critical_update_lock';
        if (!file_exists($criticalLockFile)) {
            return;
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? ''));
        foreach ($allowList as $allowed) {
            if (strpos($script, $allowed) !== false) {
                return;
            }
        }

        http_response_code(503);
        header('Retry-After: 300');
        die('Service Unavailable: Critical update mode is active. Data-changing actions are temporarily locked.');
    }
}
