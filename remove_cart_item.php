<?php
// Include configuration (once)
require_once __DIR__ . '/app/config.php';

$cart_id = $_POST['cart_id'] ?? null;

if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart ID.']);
    exit;
}

// Delete the item
$stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
$stmt->execute([$cart_id]);

echo json_encode(['success' => true]);
