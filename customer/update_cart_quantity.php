<?php
require '../app/config.php';

$cart_id = $_POST['cart_id'] ?? null;
$change = $_POST['change'] ?? 0;

if (!$cart_id || !is_numeric($change)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit;
}

// Get current quantity
$stmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE id = ?");
$stmt->execute([$cart_id]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found.']);
    exit;
}

$newQuantity = (int)$item['quantity'] + (int)$change;
if ($newQuantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Minimum quantity is 1.']);
    exit;
}

// Update cart quantity
$stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
$stmt->execute([$newQuantity, $cart_id]);

echo json_encode(['success' => true, 'newQuantity' => $newQuantity]);

