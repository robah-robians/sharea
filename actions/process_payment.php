<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Verify CSRF token
verify_csrf_token($_POST['csrf_token'] ?? '');

// Get payment data from session
$payment_data = $_SESSION['payment_data'] ?? null;
if (!$payment_data) {
    $_SESSION['error'] = 'Payment session expired. Please try again.';
    header('Location: /share_hope/');
    exit;
}

$campaign_id = $payment_data['campaign_id'];
$amount = $payment_data['amount'];
$payment_method = $payment_data['payment_method'];
$message = $payment_data['message'];
$is_anonymous = $payment_data['is_anonymous'];
$campaign_type = $payment_data['campaign_type'];

// Get user ID if logged in
$user_id = $_SESSION['user_id'] ?? null;

try {
    // Start transaction
    $pdo->beginTransaction();
    
    if ($payment_method === 'mpesa') {
        // Process M-Pesa payment
        $phone_number = $_POST['phone_number'] ?? '';
        $mpesa_method = $_POST['mpesa_method'] ?? 'paybill';
        
        if (empty($phone_number)) {
            throw new Exception('Phone number is required for M-Pesa payments.');
        }
        
        // Clean phone number
        $phone_number = preg_replace('/[^0-9+]/', '', $phone_number);
        
        // Generate transaction reference
        $transaction_ref = 'SH' . time() . rand(1000, 9999);
        
        // Insert donation record
        $stmt = $pdo->prepare("INSERT INTO donations (campaign_id, donor_id, amount, payment_method, transaction_id, message, is_anonymous, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$campaign_id, $user_id, $amount, 'mpesa', $transaction_ref, $message, $is_anonymous]);
        
        $donation_id = $pdo->lastInsertId();
        
        // Simulate M-Pesa API call (in production, integrate with actual M-Pesa API)
        // For now, we'll mark it as completed for demo purposes
        $stmt = $pdo->prepare("UPDATE donations SET status = 'completed' WHERE id = ?");
        $stmt->execute([$donation_id]);
        
        // Update campaign amount only for regular campaigns
        if ($campaign_type !== 'awareness') {
            $stmt = $pdo->prepare("UPDATE campaigns SET current_amount = current_amount + ? WHERE id = ?");
            $stmt->execute([$amount, $campaign_id]);
        }
        
        $pdo->commit();
        
        // Clear payment session data
        unset($_SESSION['payment_data']);
        
        $_SESSION['success'] = "Payment successful! Your M-Pesa transaction reference is: $transaction_ref";
        $_SESSION['donation_id'] = $donation_id;
        
        header('Location: /share_hope/payment_success.php');
        exit;
        
    } else {
        // Process card payment
        $card_number = $_POST['card_number'] ?? '';
        $expiry_date = $_POST['expiry_date'] ?? '';
        $cvv = $_POST['cvv'] ?? '';
        $cardholder_name = $_POST['cardholder_name'] ?? '';
        
        if (empty($card_number) || empty($expiry_date) || empty($cvv) || empty($cardholder_name)) {
            throw new Exception('All card details are required.');
        }
        
        // Generate transaction reference
        $transaction_ref = 'SH' . time() . rand(1000, 9999);
        
        // Mask card number for storage (store only last 4 digits)
        $masked_card = '**** **** **** ' . substr($card_number, -4);
        
        // Insert donation record
        $stmt = $pdo->prepare("INSERT INTO donations (campaign_id, donor_id, amount, payment_method, transaction_id, message, is_anonymous, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', NOW())");
        $stmt->execute([$campaign_id, $user_id, $amount, 'card', $transaction_ref, $message, $is_anonymous]);
        
        $donation_id = $pdo->lastInsertId();
        
        // Update campaign amount only for regular campaigns
        if ($campaign_type !== 'awareness') {
            $stmt = $pdo->prepare("UPDATE campaigns SET current_amount = current_amount + ? WHERE id = ?");
            $stmt->execute([$amount, $campaign_id]);
        }
        
        $pdo->commit();
        
        // Clear payment session data
        unset($_SESSION['payment_data']);
        
        $_SESSION['success'] = "Payment successful! Your transaction reference is: $transaction_ref";
        $_SESSION['donation_id'] = $donation_id;
        
        header('Location: /share_hope/payment_success.php');
        exit;
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header('Location: /share_hope/payment.php');
    exit;
}
?>