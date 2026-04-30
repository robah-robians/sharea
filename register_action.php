<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /share_hope/register.php");
    exit;
}

verify_csrf_token($_POST['csrf_token'] ?? '');

$role = $_POST['role'] ?? 'donor';
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$mission = trim($_POST['mission'] ?? '');

// Basic validation
if (empty($name) || empty($email) || empty($password)) {
    $_SESSION['error'] = "Please fill in all required fields.";
    header("Location: /share_hope/register.php?role=$role");
    exit;
}

$password_check = validate_password_strength($password);
if (!$password_check['valid']) {
    $_SESSION['error'] = implode("<br>", $password_check['errors']);
    header("Location: /share_hope/register.php?role=$role");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format.";
    header("Location: /share_hope/register.php?role=$role");
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "An account with this email already exists.";
        $pdo->rollBack();
        header("Location: /share_hope/register.php?role=$role");
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $status = 'active'; // Admin could suspend later
    
    // Insert User
    $stmt = $pdo->prepare("INSERT INTO users (role, name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$role, $name, $email, $phone, $password_hash, $status]);
    $user_id = $pdo->lastInsertId();

    // If NGO, insert into ngos and handle file
    if ($role === 'ngo') {
        if (empty($mission) || empty($_FILES['verification_doc']['name'])) {
            throw new Exception("Mission and Verification doc are required for NGOs.");
        }
        
        $uploadDir = __DIR__ . '/../assets/uploads/docs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileInfo = pathinfo($_FILES['verification_doc']['name']);
        $ext = strtolower($fileInfo['extension']);
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            throw new Exception("Invalid file type. Only PDF or Images allowed.");
        }
        
        $newFilename = uniqid('doc_') . '.' . $ext;
        $destination = $uploadDir . $newFilename;
        $dbFilePath = '/assets/uploads/docs/' . $newFilename;
        
        if (!move_uploaded_file($_FILES['verification_doc']['tmp_name'], $destination)) {
            throw new Exception("Failed to upload verification document.");
        }
        
        $stmt = $pdo->prepare("INSERT INTO ngos (user_id, mission, verification_doc) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $mission, $dbFilePath]);
    }

    $pdo->commit();
    $_SESSION['success'] = "Registration successful. Please log in.";
    header("Location: /share_hope/login.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Registration failed: " . $e->getMessage();
    header("Location: /share_hope/register.php?role=$role");
    exit;
}
