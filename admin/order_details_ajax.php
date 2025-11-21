<?php
require '../app/config.php';

if (!isset($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid request.</div>";
    exit;
}

$orderId = intval($_GET['id']);

// Fetch order
$stmt = $pdo->prepare("
    SELECT o.*, u.name AS buyer_name, u.email AS buyer_email
    FROM orders o
    LEFT JOIN users u ON u.id = o.buyer_id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<div class='alert alert-danger'>Order not found.</div>";
    exit;
}

// Fetch order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name AS product_name
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch order status history
$stmt = $pdo->prepare("
    SELECT * FROM order_status_history 
    WHERE order_id = ? ORDER BY changed_at DESC
");
$stmt->execute([$orderId]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">

    <!-- Order Summary -->
    <div class="mb-4">
        <h5 class="fw-bold"><i class="fa fa-receipt me-2"></i>Order Summary</h5>

        <div class="row mt-3">
            <div class="col-md-6">
                <p><strong>Order ID:</strong> #<?= $order['id']; ?></p>
                <p><strong>Reference:</strong> <?=htmlspecialchars($order['transaction_reference']); ?></p>
                <p><strong>Status:</strong>
                    <span class="badge 
                        <?php
                        $badge = 'bg-secondary';
                        switch ($order['status']) {
                            case 'pending':
                                $badge = 'bg-warning';
                                break;
                            case 'processing':
                                $badge = 'bg-info';
                                break;
                            case 'shipped':
                                $badge = 'bg-primary';
                                break;
                            case 'completed':
                                $badge = 'bg-success';
                                break;
                            case 'cancelled':
                                $badge = 'bg-danger';
                                break;
                        }
                        echo $badge;
                        ?>">
                        <?= ucfirst($order['status']); ?>
                    </span>
                </p>
            </div>
            <div class="col-md-6">
                <p><strong>Buyer:</strong> <?=htmlspecialchars($order['buyer_name']); ?></p>
                <p><strong>Email:</strong> <?=htmlspecialchars($order['buyer_email']); ?></p>
                <p><strong>Order Date:</strong> <?=date("d M Y, H:i A", strtotime($order['order_date'])); ?></p>
            </div>
        </div>
    </div>

    <hr>

    <!-- Order Items -->
    <div class="mb-4">
        <h5 class="fw-bold"><i class="fa fa-box me-2"></i>Items</h5>

        <table class="table table-bordered mt-3">
            <thead class="table-light">
                <tr>
                    <th>Item</th>
                    <th>Image</th>
                    <th>Seller</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($it['product_name']); ?></strong><br>
                        </td>
                        <td>
                            <?php if ($it['image_url']): ?>
                                <img src="<?= htmlspecialchars($it['image_url']); ?>"
                                    width="50" class="mt-2 rounded">
                            <?php endif; ?>
                        </td>
                        <td><?= $it['seller_id'] ? "Seller #" . $it['seller_id'] : "N/A"; ?></td>
                        <td><?= $it['quantity']; ?></td>
                        <td>$<?= number_format($it['price'], 2); ?></td>
                        <td>$<?= number_format($it['quantity'] * $it['price'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <hr>

    <!-- Totals -->
    <div class="mb-4">
        <h5 class="fw-bold"><i class="fa fa-money-bill me-2"></i>Payment Details</h5>
        <p><strong>Subtotal:</strong> $<?= number_format($order['subtotal'], 2); ?></p>
        <p><strong>Discount:</strong> -$<?= number_format($order['discount'], 2); ?></p>
        <p><strong>Total:</strong> <span class="fw-bold text-success">$<?= number_format($order['total'], 2); ?></span></p>
        <p><strong>Payment Method:</strong> <?= ucfirst($order['payment_method']); ?></p>
    </div>

    <hr>

    <!-- Delivery Info -->
    <div class="mb-4">
        <h5 class="fw-bold"><i class="fa fa-truck me-2"></i>Delivery Information</h5>
        <p><strong>Delivery Method:</strong> <?= htmlspecialchars($order['delivery_method']); ?></p>

        <?php if ($order['delivery_method'] == "pickup"): ?>
            <p><strong>Pickup Location:</strong> <?= htmlspecialchars($order['pickup_location']); ?></p>
        <?php else: ?>
            <p><strong>Shipping Address:</strong></p>
            <div class="border rounded p-2 bg-light"><?= nl2br(htmlspecialchars($order['shipping_address'])); ?></div>
        <?php endif; ?>
    </div>

    <hr>

    <!-- Status History -->
    <div>
        <h5 class="fw-bold"><i class="fa fa-history me-2"></i>Status History</h5>

        <ul class="list-group mt-3">
            <?php foreach ($history as $h): ?>
                <li class="list-group-item">
                    <strong><?= ucfirst($h['status']); ?></strong>
                    <span class="text-muted"> - <?= $h['changed_by']; ?></span>
                    <br>
                    <small class="text-muted"><?= date("d M Y, H:i A", strtotime($h['changed_at'])); ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>