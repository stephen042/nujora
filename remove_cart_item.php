<?php
require 'app/config.php';
header('Content-Type: application/json');

session_start();

$user_id = $_SESSION['user_id'] ?? null;
$id = $_POST['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    if ($user_id) {
        // === Logged in user ===
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND buyer_id = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Item removed']);
        exit;
    } else {
        // === Guest user ===
        if (isset($_SESSION['guest_cart'][$id])) {
            unset($_SESSION['guest_cart'][$id]);
            echo json_encode(['success' => true, 'message' => 'Item removed']);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
?>
