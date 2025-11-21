<?php
require 'app/config.php';
header('Content-Type: application/json');

session_start();

$user_id = $_SESSION['user_id'] ?? null;
$id = $_POST['id'] ?? null;
$change = intval($_POST['change'] ?? 0);

if (!$id || $change === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    if ($user_id) {
        // === Logged in user ===
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE id = ? AND buyer_id = ?");
        $stmt->execute([$id, $user_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
            exit;
        }

        $newQty = max(1, $item['quantity'] + $change);

        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQty, $id]);

        echo json_encode(['success' => true, 'message' => 'Quantity updated']);
        exit;
    } else {
        // === Guest user ===
        if (!isset($_SESSION['guest_cart'][$id])) {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
            exit;
        }

        $_SESSION['guest_cart'][$id] += $change;
        if ($_SESSION['guest_cart'][$id] < 1) {
            $_SESSION['guest_cart'][$id] = 1;
        }

        echo json_encode(['success' => true, 'message' => 'Quantity updated']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
