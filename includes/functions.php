<?php
/**
 * SHARE HOPE - Core Functions Library
 * Consolidated helper functions for clean code organization
 */

// =============================================================================
// SECURITY & VALIDATION FUNCTIONS
// =============================================================================

/**
 * HTML escape function for XSS protection
 */
if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Generate CSRF token
 */
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

/**
 * Verify CSRF token
 */
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            die("Invalid CSRF token.");
        }
        return true;
    }
}

/**
 * Validate password strength
 */
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        $errors[] = "Password must contain at least one special character (!@#$%^&*)";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

// =============================================================================
// SESSION MANAGEMENT FUNCTIONS
// =============================================================================

/**
 * Check if user is authenticated
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Check if user has specific role
 */
function has_role($role) {
    return is_logged_in() && $_SESSION['user_role'] === $role;
}

/**
 * Require authentication with optional role check
 */
function require_login($required_role = null) {
    if (!is_logged_in()) {
        $_SESSION['error'] = "Please log in to access this page.";
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
    
    if ($required_role && !has_role($required_role)) {
        $_SESSION['error'] = "Access denied. Insufficient permissions.";
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

/**
 * Clear session data
 */
function clear_session() {
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    session_destroy();
}

// =============================================================================
// DATABASE HELPER FUNCTIONS
// =============================================================================

/**
 * Get user by ID
 */
function get_user_by_id($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get user error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get current logged in user
 */
function get_current_user_data($pdo) {
    if (!is_logged_in()) {
        return null;
    }
    return get_user_by_id($pdo, $_SESSION['user_id']);
}

// =============================================================================
// ANNOUNCEMENT FUNCTIONS
// =============================================================================

/**
 * Get public announcements count
 */
function get_announcements_count($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM announcements WHERE is_public = 1");
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        error_log("Failed to get announcements count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get public announcements
 */
function get_public_announcements($pdo, $limit = 5) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM announcements WHERE is_public = 1 ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get public announcements: " . $e->getMessage());
        return [];
    }
}

// =============================================================================
// LOGIN SECURITY FUNCTIONS
// =============================================================================

/**
 * Check if account is locked
 */
function is_account_locked($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT account_locked_until FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || !$user['account_locked_until']) {
            return false;
        }
        
        $locked_until = strtotime($user['account_locked_until']);
        if (time() > $locked_until) {
            // Unlock account
            $stmt = $pdo->prepare("UPDATE users SET account_locked_until = NULL, failed_login_attempts = 0 WHERE id = ?");
            $stmt->execute([$user_id]);
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to check account lock: " . $e->getMessage());
        return false;
    }
}

/**
 * Log login attempt
 */
function log_login_attempt($pdo, $email, $success, $user_id = null) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (user_id, email, ip_address, success, attempted_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $email, $ip_address, $success ? 1 : 0]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log login attempt: " . $e->getMessage());
        return false;
    }
}

/**
 * Check login rate limiting
 */
function check_login_rate_limit($pdo, $email) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts FROM login_attempts 
            WHERE (email = ? OR ip_address = ?) 
            AND success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$email, $ip_address]);
        $result = $stmt->fetch();
        
        return $result['attempts'] < 5;
    } catch (Exception $e) {
        error_log("Failed to check rate limit: " . $e->getMessage());
        return true;
    }
}

/**
 * Handle failed login
 */
function handle_failed_login($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $stmt = $pdo->prepare("SELECT failed_login_attempts FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user['failed_login_attempts'] >= 5) {
            $lock_until = date('Y-m-d H:i:s', time() + (30 * 60));
            $stmt = $pdo->prepare("UPDATE users SET account_locked_until = ? WHERE id = ?");
            $stmt->execute([$lock_until, $user_id]);
            return 'locked';
        }
        
        return 'incremented';
    } catch (Exception $e) {
        error_log("Failed to handle failed login: " . $e->getMessage());
        return 'error';
    }
}

/**
 * Reset failed login attempts
 */
function reset_failed_login_attempts($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to reset login attempts: " . $e->getMessage());
        return false;
    }
}

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

/**
 * Format currency
 */
function format_currency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Format date
 */
function format_date($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

/**
 * Calculate percentage
 */
function calculate_percentage($current, $total) {
    if ($total <= 0) return 0;
    return min(100, round(($current / $total) * 100, 1));
}
?>