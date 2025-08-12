<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_GET['chat_with'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$chat_with = (int)$_GET['chat_with'];

try {
    $stmt = $pdo->prepare("SELECT * FROM messages 
                          WHERE (sender_id = ? AND receiver_id = ?) 
                          OR (sender_id = ? AND receiver_id = ?) 
                          ORDER BY sent_at ASC");
    $stmt->execute([$user_id, $chat_with, $chat_with, $user_id]);
    $messages = $stmt->fetchAll();
    
    echo json_encode($messages);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>