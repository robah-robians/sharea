<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['pending_donation'])) {
    header("Location: " . BASE_URL . "/campaigns.php");
    exit;
}

$donation_data = $_SESSION['pending_donation'];
unset($_SESSION['pending_donation']);

$campaign_id = intval($donation_data['campaign_id']);
$amount = floatval($donation_data['amount']);
$payment_method = $donation_data['payment_method'];
$is_anonymous = $donation_data['is_anonymous'];
$message = $donation_data['message'];
$donor_id = $donation_data['donor_id'];
$mpesa_phone = $donation_data['mpesa_phone'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT campaigns.*, ngos.user_id FROM campaigns JOIN ngos ON campaigns.ngo_id = ngos.id WHERE campaigns.id = ?");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch();

    if (!$campaign) {
        throw new Exception("Campaign not found.");
    }

    // Check if this donation achieves the campaign goal
    if ($campaign['current_amount'] < $campaign['goal_amount'] && ($campaign['current_amount'] + $amount) >= $campaign['goal_amount']) {
        $_SESSION['goal_reached'] = true;
    }

    $transaction_id = 'MPESA_' . strtoupper(uniqid());
    $gateway_status = 'success';

    $stmt = $pdo->prepare("INSERT INTO donations (campaign_id, donor_id, amount, payment_method, status, is_anonymous, message, transaction_id) VALUES (?, ?, ?, ?, 'completed', ?, ?, ?)");
    $stmt->execute([$campaign_id, $donor_id, $amount, $payment_method, $is_anonymous, $message, $transaction_id]);
    $donation_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO payments (donation_id, payment_gateway, payment_status, gateway_response) VALUES (?, ?, ?, ?)");
    $stmt->execute([$donation_id, $payment_method, $gateway_status, '{"mock_response":"M-Pesa Sandbox Verification Success", "phone":"' . $mpesa_phone . '"}']);

    $stmt = $pdo->prepare("UPDATE campaigns SET current_amount = current_amount + ? WHERE id = ?");
    $stmt->execute([$amount, $campaign_id]);

    if ($donor_id) {
        $donor_msg = "Your M-Pesa donation of KSh " . number_format($amount, 2) . " to " . $campaign['title'] . " was successful. Thank you!";
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$donor_id, $donor_msg]);
    }

    $ngo_msg = "You received a new M-Pesa donation of KSh " . number_format($amount, 2) . " for your campaign: " . $campaign['title'] . " (Phone: " . $mpesa_phone . ")";
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$campaign['user_id'], $ngo_msg]);

    $pdo->commit();

    $_SESSION['success'] = "Thank you! Your simulated M-Pesa SDK Push was successful.";
    header("Location: ' . BASE_URL . '/receipt.php?id=" . $donation_id);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "M-Pesa processing failed: " . $e->getMessage();
    header("Location: ' . BASE_URL . '/donate.php?campaign_id=" . $campaign_id);
    exit;
}
