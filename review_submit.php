<?php
// review_submit.php
require 'app/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    echo json_encode(['success' => false, 'message' => 'Not authorized.']);
    exit;
}

$buyer_id = $_SESSION['user_id'];

// Basic POST validation
$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
$review_text = trim($_POST['review_text'] ?? '');

if ($order_id <= 0 || $product_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

// 1) Confirm the product is part of the order and the order belongs to this buyer
$stmt = $pdo->prepare("
    SELECT o.id
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    WHERE o.id = ? AND o.buyer_id = ? AND oi.product_id = ?
    LIMIT 1
");
$stmt->execute([$order_id, $buyer_id, $product_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Order/product mismatch or not authorized.']);
    exit;
}

// 2) Confirm order status is completed (use order_status_history latest)
$stmt = $pdo->prepare("SELECT status FROM order_status_history WHERE order_id = ? ORDER BY changed_at DESC LIMIT 1");
$stmt->execute([$order_id]);
$latestStatus = $stmt->fetchColumn();
if ($latestStatus !== 'completed') {
    echo json_encode(['success' => false, 'message' => 'You can only review delivered orders.']);
    exit;
}

// 3) Prevent duplicate review for same order+product+buyer
$stmt = $pdo->prepare("SELECT id FROM product_reviews WHERE order_id = ? AND product_id = ? AND buyer_id = ? LIMIT 1");
$stmt->execute([$order_id, $product_id, $buyer_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this item for this order.']);
    exit;
}

// 4) Handle optional image upload (single image allowed, <=3MB, jpeg/png)
$images_json = null;
if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Image upload error.']);
        exit;
    }
    if ($file['size'] > 3 * 1024 * 1024) { // 3MB
        echo json_encode(['success' => false, 'message' => 'Image exceeds 3MB.']);
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($allowed[$mime])) {
        echo json_encode(['success' => false, 'message' => 'Only JPG and PNG images are allowed.']);
        exit;
    }

    $ext = $allowed[$mime];
    $targetDir = __DIR__ . '/uploads/reviews';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $targetPath = $targetDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded image.']);
        exit;
    }
    // store relative path (web-accessible)
    $images_json = json_encode([$filename]);
}

// 5) Insert into product_reviews
$stmt = $pdo->prepare("
    INSERT INTO product_reviews (order_id, product_id, buyer_id, rating, review_text, images, created_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");
$stmt->execute([$order_id, $product_id, $buyer_id, $rating, $review_text, $images_json]);

// 6) Mark this product in this order as reviewed
$updateReviewed = $pdo->prepare("
    UPDATE order_items 
    SET reviewed = 1 
    WHERE order_id = ? AND product_id = ?
    LIMIT 1
");
$updateReviewed->execute([$order_id, $product_id]);

// Response
echo json_encode([
    'success' => true,
    'message' => 'Review submitted successfully.',
    'redirect' => 'orders.php?tab=completed'
]);
exit;
