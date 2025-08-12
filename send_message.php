<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_POST['chat_with'])) {
    http_response_code(401);
    exit;
}

$user_id = $_SESSION['user_id'];
$chat_with = (int)$_POST['chat_with'];
$message = trim($_POST['message']);

if (empty($message)) {
    http_response_code(400);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $chat_with, $message]);
    http_response_code(200);
} catch (PDOException $e) {
    http_response_code(500);
}
?>