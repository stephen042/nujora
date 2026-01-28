<?php
require '../app/db.php';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:image" content="uploads/default-product.png" />
    <title>Order Details #<?= htmlspecialchars($order_id) ?> | <?= htmlspecialchars($order['shop_name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .order-summary-card {
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
        }

        .order-product-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        .badge-status {
            font-size: 1rem;
        }

        .status-history-table td,
        .status-history-table th {
            font-size: 0.95rem;
        }
    </style>
</head>

<body>
    <div class="container my-5">
        <h2 class="mb-4">Order Details :</span></h2>
        <div class="row">
            <div class="col-md-8">
                <div class="card order-summary-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <strong>Order Summary</strong>
                    </div>
                    <div class="card-body">
                        <?php foreach ($order_items as $item): ?>
                            <?php
                            // Decode variant options (if JSON)
                            $variantText = '';
                            if (!empty($item['variant_options'])) {
                                $vo = json_decode($item['variant_options'], true);

                                if (is_array($vo)) {
                                    foreach ($vo as $key => $val) {
                                        $variantText .= "<h6 class='text-muted me-1 p-2'>{$key}: {$val}</h6>";
                                    }
                                } else {
                                    $variantText = "<span class='text-muted'>N/A</span>";
                                }
                            }
                            ?>
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?= htmlspecialchars($item['product_image'] ?? 'default.png') ?>" class="order-product-img me-3" alt="Product">
                                <div>
                                    <h5 class="mb-1"><?= htmlspecialchars($item['product_name']) ?></h5>
                                    <div class="text-muted mb-1">₦<?= number_format($item['product_price'], 2) ?></div>
                                    <div class="text-muted"><?= $variantText ?></div>
                                    <hr class="m-2">
                                    <span class="badge badge-status 
                                <?= $order['status'] == 'completed' ? 'bg-success' : '' ?>
                                <?= $order['status'] == 'pending' ? 'bg-secondary' : '' ?>
                                <?= $order['status'] == 'processing' ? 'bg-warning' : '' ?>
                                <?= $order['status'] == 'shipped' ? 'bg-info' : '' ?>
                                <?= $order['status'] == 'cancelled' ? 'bg-danger' : '' ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item"><strong>Quantity:</strong> <?= htmlspecialchars($item['quantity']) ?></li>
                                <li class="list-group-item text-danger"><strong>Discount:</strong> ₦<?= number_format($order['discount'], 2) ?></li>
                                <li class="list-group-item"><strong>Total Price:</strong> ₦<?= number_format($order['total'], 2) ?></li>
                            </ul>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item"><strong>Order Date:</strong> <?= date('d M Y, H:i A', strtotime($order['created_at'])) ?></li>
                            <li class="list-group-item"><strong>Delivery Method:</strong>
                                <?php if ($order['delivery_method'] == 'home') {
                                    echo "Home Delivery";
                                } elseif ($order['delivery_method'] == 'pickup_station') {
                                    echo "Pickup Station";
                                } ?>
                            </li>

                            <li class="list-group-item"><strong>Delivery Address:</strong> <?= htmlspecialchars($order['shipping_address'] ?? 'N/A') ?></li>
                            <li class="list-group-item"><strong>Payment Method:</strong>
                                <?php
                                if ($order['payment_method'] == 'card') {
                                    $payment_method = "Paid With Card";
                                } elseif ($order['payment_method'] == 'bank_transfer' || $order['payment_method'] == 'pay_with_transfer') {
                                    $payment_method = "Paid With Bank Transfer";
                                } elseif ($order['payment_method'] == 'cod') {
                                    $payment_method = "Cash on Delivery";
                                } else {
                                    $payment_method = "N/A";
                                };
                                echo htmlspecialchars($payment_method);
                                ?>
                            </li>
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
                                            <td><?= date('d M Y, H:i a', strtotime($hist['changed_at'])) ?></td>
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
                        <a href="seller-dashboard.php?tab=orders" class="btn btn-primary btn-outline-primary mb-2 w-100 text-white">Back to Seller Dashboard</a>
                        <?php if ($order['status'] === 'pending'): ?>
                            <a href="update_order_status.php?order_id=<?= $order_id ?>&status=cancelled" class="btn btn-danger w-100" onclick="return confirm('Cancel this order?')">Cancel Order</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>