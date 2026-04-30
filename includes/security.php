<?php
/**
 * Security Helper Functions
 * Password validation, rate limiting, email verification, CSRF protection
 */



/**
 * Validate password strength
 * Requirements: min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char
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

/**
 * Check if account is locked due to failed login attempts
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
 * Record login attempt
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
 * Check rate limiting - max 5 attempts per IP per 15 minutes
 */
function check_login_rate_limit($pdo, $email) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // Check attempts in last 15 minutes
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
        return true; // Allow if check fails
    }
}

/**
 * Handle failed login - increment counter and lock if needed
 */
function handle_failed_login($pdo, $user_id) {
    try {
        // Increment failed attempts
        $stmt = $pdo->prepare("UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Check if locked out (5+ failed attempts)
        $stmt = $pdo->prepare("SELECT failed_login_attempts FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user['failed_login_attempts'] >= 5) {
            // Lock for 30 minutes
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
 * Reset failed login attempts on successful login
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

/**
 * Generate email verification token
 */
function generate_verification_token($pdo, $user_id) {
    try {
        $token = bin2hex(random_bytes(50));
        $expires_at = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
        
        // Delete old tokens
        $stmt = $pdo->prepare("DELETE FROM email_verification WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Create new token
        $stmt = $pdo->prepare("
            INSERT INTO email_verification (user_id, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $token, $expires_at]);
        
        return $token;
    } catch (Exception $e) {
        error_log("Failed to generate verification token: " . $e->getMessage());
        return null;
    }
}

/**
 * Verify email token
 */
function verify_email_token($pdo, $token) {
    try {
        $stmt = $pdo->prepare("
            SELECT user_id FROM email_verification 
            WHERE token = ? 
            AND expires_at > NOW() 
            AND is_verified = 0
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return ['valid' => false, 'message' => 'Invalid or expired token'];
        }
        
        $user_id = $result['user_id'];
        
        // Mark as verified
        $stmt = $pdo->prepare("UPDATE email_verification SET is_verified = 1 WHERE token = ?");
        $stmt->execute([$token]);
        
        // Update user
        $stmt = $pdo->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        return ['valid' => true, 'user_id' => $user_id];
    } catch (Exception $e) {
        error_log("Failed to verify email token: " . $e->getMessage());
        return ['valid' => false, 'message' => 'Verification failed'];
    }
}

/**
 * Send verification email
 */
function send_verification_email($email, $name, $token) {
    try {
        $verification_link = "https://{$_SERVER['HTTP_HOST']}/share_hope/verify_email.php?token=" . urlencode($token);
        
        $subject = "Verify your Share Hope account";
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Welcome to Share Hope, $name!</h2>
            <p>Please verify your email address to complete registration:</p>
            <p><a href='$verification_link' style='background: #4F46E5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>Verify Email</a></p>
            <p>Or copy this link: $verification_link</p>
            <p style='color: #999; font-size: 12px;'>This link expires in 24 hours.</p>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@sharehope.com\r\n";
        
        return mail($email, $subject, $message, $headers);
    } catch (Exception $e) {
        error_log("Failed to send verification email: " . $e->getMessage());
        return false;
    }
}
