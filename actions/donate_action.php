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

if ($payment_method !== 'inkind' && $amount <= 0) {
    $_SESSION['error'] = "Invalid donation amount.";
    header("Location: /share_hope/donate.php?campaign_id=" . $campaign_id);
    exit;
}

if (!in_array($payment_method, ['mpesa', 'card', 'bank', 'inkind'])) {
    $_SESSION['error'] = "Invalid payment method.";
    header("Location: /share_hope/donate.php?campaign_id=" . $campaign_id);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verify campaign existence and fetch NGO owner user_id for notifications
    $campaign_type = $_POST['campaign_type'] ?? 'regular';
    
    if ($campaign_type === 'awareness') {
        // For awareness campaigns, we'll create a donation record but link it to a special admin campaign
        $stmt = $pdo->prepare("SELECT ac.*, 1 as user_id FROM awareness_campaigns ac WHERE ac.id = ?");
        $stmt->execute([$campaign_id]);
        $campaign = $stmt->fetch();
        
        if ($campaign) {
            $campaign['title'] = $campaign['title'];
            $campaign['user_id'] = 1; // Admin user ID for notifications
        }
    } else {
        $stmt = $pdo->prepare("SELECT c.*, n.user_id FROM campaigns c LEFT JOIN ngos n ON c.ngo_id = n.id WHERE c.id = ?");
        $stmt->execute([$campaign_id]);
        $campaign = $stmt->fetch();
    }

    if (!$campaign) {
        throw new Exception("Campaign not found.");
    }

    // Mock Payment Gateway Processing
    $transaction_id = 'TXN_' . strtoupper(uniqid());
    $gateway_status = 'success'; // Stubbed as always success for demonstration

    // Insert Donation record
    $status = $payment_method === 'inkind' ? 'pledged' : 'completed';
    $stmt = $pdo->prepare("INSERT INTO donations (campaign_id, donor_id, amount, payment_method, status, is_anonymous, message, transaction_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$campaign_id, $donor_id, $amount, $payment_method, $status, $is_anonymous, $message, $transaction_id]);
    $donation_id = $pdo->lastInsertId();

    // Insert Payment Log
    $stmt = $pdo->prepare("INSERT INTO payments (donation_id, payment_gateway, payment_status, gateway_response) VALUES (?, ?, ?, ?)");
    $stmt->execute([$donation_id, $payment_method, $gateway_status, '{"mock_response":"Payment/Pledge verified successfully"}']);

    // Update Campaign Current Amount safely using transaction (only if not a pledge/inkind and not awareness campaign)
    if ($payment_method !== 'inkind' && $campaign_type !== 'awareness') {
        $stmt = $pdo->prepare("UPDATE campaigns SET current_amount = current_amount + ? WHERE id = ?");
        $stmt->execute([$amount, $campaign_id]);
    }

    // Send Notification to Donor
    if ($donor_id) {
        $donor_msg = "Your donation of $" . number_format($amount, 2) . " to " . $campaign['title'] . " was successful. Thank you!";
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$donor_id, $donor_msg]);
    }

    // Send Notification to NGO owner (only if campaign is linked to an NGO)
    if (!empty($campaign['user_id'])) {
        $ngo_msg = "You received a new " . strtolower($payment_method) . " donation of $" . number_format($amount, 2) . " for your campaign: " . $campaign['title'];
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$campaign['user_id'], $ngo_msg]);
    }

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
