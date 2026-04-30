<?php
/**
 * Session Management and Validation Functions
 */

/**
 * Validate current session and ensure user is properly authenticated
 */
function validate_session($pdo) {
    // Check if session exists
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }
    
    // Validate session timeout (4 hours)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 14400) {
        destroy_session();
        return false;
    }
    
    // Validate user still exists and is active
    try {
        $stmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || $user['status'] !== 'active' || $user['role'] !== $_SESSION['user_role']) {
            destroy_session();
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Session validation error: " . $e->getMessage());
        destroy_session();
        return false;
    }
}

/**
 * Properly destroy session and clear all data
 */
function destroy_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        
        session_destroy();
    }
}

/**
 * Check if user is logged in and has required role
 */
function require_auth($required_role = null) {
    global $pdo;
    
    if (!validate_session($pdo)) {
        $_SESSION['error'] = "Please log in to access this page.";
        header("Location: /share_hope/login.php");
        exit;
    }
    
    if ($required_role && $_SESSION['user_role'] !== $required_role) {
        $_SESSION['error'] = "Access denied. Insufficient permissions.";
        header("Location: /share_hope/login.php");
        exit;
    }
    
    return true;
}

/**
 * Get current user information
 */
function get_logged_in_user($pdo) {
    if (!validate_session($pdo)) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Get current user error: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if current user is admin
 */
function is_admin() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin']);
}

/**
 * Check if current user is NGO
 */
function is_ngo() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ngo';
}

/**
 * Check if current user is donor
 */
function is_donor() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'donor';
}
?>