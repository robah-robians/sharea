<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../includes/activity_logger.php";

if (!isset($_SESSION["user_id"]) || !in_array($_SESSION["user_role"] ?? "", ["admin", "super_admin"], true)) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . BASE_URL . "/admin/announcements.php");
    exit;
}

verify_csrf_token($_POST["csrf_token"] ?? "");
$announcementId = (int)($_POST["announcement_id"] ?? 0);
$action = $_POST["mod_action"] ?? "";
$allowedActions = ["hide", "restore", "withdraw", "publish", "delete"];

if ($announcementId <= 0 || !in_array($action, $allowedActions, true)) {
    $_SESSION["error"] = "Invalid announcement moderation request.";
    header("Location: " . BASE_URL . "/admin/announcements.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id, title, is_public, COALESCE(is_hidden,0) AS is_hidden FROM announcements WHERE id = ?");
$stmt->execute([$announcementId]);
$announcement = $stmt->fetch();

if (!$announcement) {
    $_SESSION["error"] = "Announcement not found.";
    header("Location: " . BASE_URL . "/admin/announcements.php");
    exit;
}

$adminId = (int)$_SESSION["user_id"];
$adminRole = $_SESSION["user_role"] ?? "";
$logAction = "";
$description = "";

switch ($action) {
    case "hide":
        $pdo->prepare("UPDATE announcements SET is_hidden = 1, hidden_by = ?, hidden_at = NOW() WHERE id = ?")
            ->execute([$adminId, $announcementId]);
        $_SESSION["success"] = "Announcement hidden.";
        $logAction = "Hide announcement";
        $description = "Announcement hidden from public surfaces";
        break;
    case "restore":
        $pdo->prepare("UPDATE announcements SET is_hidden = 0, hidden_by = NULL, hidden_at = NULL WHERE id = ?")
            ->execute([$announcementId]);
        $_SESSION["success"] = "Announcement restored.";
        $logAction = "Restore announcement";
        $description = "Announcement restored to public/admin visibility";
        break;
    case "withdraw":
        $pdo->prepare("UPDATE announcements SET is_public = 0 WHERE id = ?")
            ->execute([$announcementId]);
        $_SESSION["success"] = "Announcement withdrawn from public view.";
        $logAction = "Withdraw announcement";
        $description = "Announcement switched to internal-only visibility";
        break;
    case "publish":
        $pdo->prepare("UPDATE announcements SET is_public = 1 WHERE id = ?")
            ->execute([$announcementId]);
        $_SESSION["success"] = "Announcement published to public view.";
        $logAction = "Publish announcement";
        $description = "Announcement published publicly";
        break;
    case "delete":
        // Allow both admin and super_admin to delete announcements
        if (!in_array($adminRole, ["admin", "super_admin"], true)) {
            $_SESSION["error"] = "Only administrators can delete announcements.";
            header("Location: " . BASE_URL . "/admin/announcements.php");
            exit;
        }
        $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$announcementId]);
        $_SESSION["success"] = "Announcement removed from system permanently.";
        $logAction = "Delete announcement";
        $description = "Announcement permanently deleted from system";
        break;
}

log_admin_activity(
    $pdo,
    $adminId,
    $logAction,
    $action === "delete" ? "delete" : "update",
    "announcements",
    $announcementId,
    $announcement["title"] ?? "",
    $description
);

header("Location: " . BASE_URL . "/admin/announcements.php");
exit;
