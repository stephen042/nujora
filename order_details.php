<?php
// filepath: c:\laragon\www\trendymart\order_details.php
session_start();
require 'db.php';

// Get order ID from URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) {
    die("Invalid order ID.");
}

// Fetch order details (without product join)
$stmt = $pdo->prepare("
    SELECT o.*, u.name AS buyer_name, u.email AS buyer_email
    FROM orders o
    JOIN users u ON o.buyer_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found.");
}

// Fetch order items and their products
$stmtItems = $pdo->prepare("
    SELECT oi.*, p.name AS product_name, p.price AS product_price, p.image_url AS product_image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmtItems->execute([$order_id]);
$order_items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// Fetch order status history if available
$status_history = [];
if ($pdo->query("SHOW TABLES LIKE 'order_status_history'")->rowCount() > 0) {
    $stmtHist = $pdo->prepare("SELECT status, changed_by, changed_at FROM order_status_history WHERE order_id = ? ORDER BY changed_at DESC");
    $stmtHist->execute([$order_id]);
    $status_history = $stmtHist->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Details #<?= htmlspecialchars($order_id) ?> | TrendyMart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .order-summary-card { border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
        .order-product-img { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; }
        .badge-status { font-size: 1rem; }
        .status-history-table td, .status-history-table th { font-size: 0.95rem; }
    </style>
</head>
<body>
<div class="container my-5">
    <h2 class="mb-4">Order Details <span class="text-muted">#<?= htmlspecialchars($order_id) ?></span></h2>
    <div class="row">
        <div class="col-md-8">
            <div class="card order-summary-card mb-4">
                <div class="card-header bg-primary text-white">
                    <strong>Order Summary</strong>
                </div>
                <div class="card-body">
                    <?php foreach ($order_items as $item): ?>
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= htmlspecialchars($item['product_image'] ?? 'default.png') ?>" class="order-product-img me-3" alt="Product">
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($item['product_name']) ?></h5>
                            <div class="text-muted mb-1">₦<?= number_format($item['product_price'], 2) ?></div>
                            <span class="badge badge-status 
                                <?= $order['status']=='completed' ? 'bg-success' : '' ?>
                                <?= $order['status']=='pending' ? 'bg-secondary' : '' ?>
                                <?= $order['status']=='processing' ? 'bg-warning' : '' ?>
                                <?= $order['status']=='shipped' ? 'bg-info' : '' ?>
                                <?= $order['status']=='cancelled' ? 'bg-danger' : '' ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><strong>Quantity:</strong> <?= htmlspecialchars($item['quantity']) ?></li>
                        <li class="list-group-item"><strong>Total Price:</strong> ₦<?= number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 0), 2) ?></li>
                    </ul>
                    <?php endforeach; ?>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item"><strong>Order Date:</strong> <?= date('d M Y, H:i', strtotime($order['created_at'])) ?></li>
                        <li class="list-group-item"><strong>Delivery Address:</strong> <?= htmlspecialchars($order['delivery_address'] ?? 'N/A') ?></li>
                        <li class="list-group-item"><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></li>
                    </ul>
                </div>
            </div>
            <?php if (!empty($status_history)): ?>
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>Status History</strong>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered status-history-table mb-0">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Changed By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($status_history as $hist): ?>
                                <tr>
                                    <td><?= ucfirst(htmlspecialchars($hist['status'])) ?></td>
                                    <td><?= htmlspecialchars($hist['changed_by']) ?></td>
                                    <td><?= date('d M Y, H:i', strtotime($hist['changed_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>Buyer Info</strong>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($order['buyer_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($order['buyer_email']) ?></p>
                </div>
            </div>
            <div class="card">
                <div class="card-header bg-light">
                    <strong>Order Actions</strong>
                </div>
                <div class="card-body">
                    <a href="seller-dashboard.php?tab=orders" class="btn btn-outline-secondary mb-2 w-100">Back to Seller Dashboard</a>
                    <?php if ($order['status'] === 'pending'): ?>
                        <a href="update_order_status.php?order_id=<?= $order_id ?>&status=cancelled" class="btn btn-danger w-100" onclick="return confirm('Cancel this order?')">Cancel Order</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>