<?php
/**
 * Email Verification Functions
 * Separated from main functions for organization
 */

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
?>