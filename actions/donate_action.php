<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /share_hope/campaigns.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$campaign_id = intval($_POST['campaign_id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);
$payment_method = $_POST['payment_method'] ?? 'card';
$message = trim($_POST['message'] ?? '');
$is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
// Donor ID logic, null if not logged in
$donor_id = $_SESSION['user_id'] ?? null;

$mpesa_phone = trim($_POST['mpesa_phone'] ?? '');

if ($amount <= 0 || !in_array($payment_method, ['mpesa', 'card', 'bank'])) {
    $_SESSION['error'] = "Invalid donation amount or payment method.";
    header("Location: /share_hope/donate.php?campaign_id=" . $campaign_id);
    exit;
}

if ($payment_method === 'mpesa') {
    if (empty($mpesa_phone)) {
        $_SESSION['error'] = "Please enter an M-Pesa phone number.";
        header("Location: /share_hope/donate.php?campaign_id=" . $campaign_id);
        exit;
    }

    $_SESSION['pending_donation'] = [
        'campaign_id' => $campaign_id,
        'amount' => $amount,
        'payment_method' => 'mpesa',
        'is_anonymous' => $is_anonymous,
        'message' => $message,
        'donor_id' => $donor_id,
        'mpesa_phone' => $mpesa_phone
    ];

    header("Location: /share_hope/mpesa_processing.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // Verify campaign existence
    $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ?");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch();

    if (!$campaign) {
        throw new Exception("Campaign not found.");
    }

    // Mock Payment Gateway Processing
    $transaction_id = 'TXN_' . strtoupper(uniqid());
    $gateway_status = 'success'; // Stubbed as always success for demonstration

    // Insert Donation record
    $stmt = $pdo->prepare("INSERT INTO donations (campaign_id, donor_id, amount, payment_method, status, is_anonymous, message, transaction_id) VALUES (?, ?, ?, ?, 'completed', ?, ?, ?)");
    $stmt->execute([$campaign_id, $donor_id, $amount, $payment_method, $is_anonymous, $message, $transaction_id]);
    $donation_id = $pdo->lastInsertId();

    // Insert Payment Log
    $stmt = $pdo->prepare("INSERT INTO payments (donation_id, payment_gateway, payment_status, gateway_response) VALUES (?, ?, ?, ?)");
    $stmt->execute([$donation_id, $payment_method, $gateway_status, '{"mock_response":"Payment verified successfully"}']);

    // Update Campaign Current Amount safely using transaction
    $stmt = $pdo->prepare("UPDATE campaigns SET current_amount = current_amount + ? WHERE id = ?");
    $stmt->execute([$amount, $campaign_id]);

    // Send Notification to Donor
    if ($donor_id) {
        $donor_msg = "Your donation of KSh " . number_format($amount, 2) . " to " . $campaign['title'] . " was successful. Thank you!";
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$donor_id, $donor_msg]);
    }

    // Send Notification to NGO
    $ngo_msg = "You received a new " . strtolower($payment_method) . " donation of KSh " . number_format($amount, 2) . " for your campaign: " . $campaign['title'];
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$campaign['user_id'], $ngo_msg]);

    $pdo->commit();

    // Redirect to Thank You / Receipt Page
    $_SESSION['success'] = "Thank you for your generous donation!";
    header("Location: /share_hope/receipt.php?id=" . $donation_id);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Donation failed: " . $e->getMessage();
    header("Location: /share_hope/donate.php?campaign_id=" . $campaign_id);
    exit;
}
