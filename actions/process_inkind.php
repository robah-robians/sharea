<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /share_hope/campaigns.php");
    exit;
}

// CSRF check
$submitted_token = $_POST['csrf_token'] ?? '';
$session_token = $_SESSION['csrf_token'] ?? '';
if (empty($submitted_token) || !hash_equals($session_token, $submitted_token)) {
    die("Security verification failed. CSRF token mismatch.");
}

$campaign_id = intval($_POST['campaign_id'] ?? 0);
$item_category = $_POST['item_category'] ?? '';
$quantity = trim($_POST['quantity'] ?? '');
$item_description = trim($_POST['item_description'] ?? '');

$donor_id = $_SESSION['user_id'] ?? null;
$donor_name = trim($_POST['donor_name'] ?? '');
$donor_email = trim($_POST['donor_email'] ?? '');
$donor_phone = trim($_POST['donor_phone'] ?? '');

if (empty($quantity) || empty($item_description) || empty($item_category)) {
    $_SESSION['error'] = "Please fill in all required item details.";
    header("Location: /share_hope/donate.php?campaign_id=" . $campaign_id);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verify campaign exists
    $stmt = $pdo->prepare("SELECT title, user_id FROM campaigns c JOIN ngos n ON c.ngo_id = n.id WHERE c.id = ?");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch();

    if (!$campaign) {
        throw new Exception("Campaign not found.");
    }

    // Insert Pledge
    $stmt = $pdo->prepare("INSERT INTO inkind_donations (campaign_id, donor_id, donor_name, donor_email, donor_phone, item_category, item_description, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $campaign_id,
        $donor_id,
        $donor_name ?: null,
        $donor_email ?: null,
        $donor_phone ?: null,
        $item_category,
        $item_description,
        $quantity
    ]);

    // Notify NGO
    $ngo_msg = "New In-Kind Pledge Received: A donor pledged $quantity of $item_category for your campaign '" . $campaign['title'] . "'. Check your dashboard to contact them.";
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$campaign['user_id'], $ngo_msg]);

    $pdo->commit();

    $_SESSION['success'] = "Thank you! Your pledge for physical items has been recorded. The organization will contact you shortly.";
    header("Location: /share_hope/donate.php?campaign_id=" . $campaign_id);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Failed to process pledge: " . $e->getMessage();
    header("Location: /share_hope/donate.php?campaign_id=" . $campaign_id);
    exit;
}
