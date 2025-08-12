<?php
session_start();
require __DIR__ . '/db.php';

// Check if seller is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    http_response_code(401);
    exit;
}

// Validate input
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['buyer_id']) || !isset($_POST['message'])) {
    http_response_code(400);
    exit;
}

$seller_id = $_SESSION['user_id'];
$buyer_id = (int)$_POST['buyer_id'];
$message = trim($_POST['message']);

if (empty($message)) {
    http_response_code(400);
    exit;
}

// Verify buyer exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'buyer'");
$stmt->execute([$buyer_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    exit;
}

// Insert message
try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$seller_id, $buyer_id, $message]);
    http_response_code(200);
} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error: " . $e->getMessage());
}
?>