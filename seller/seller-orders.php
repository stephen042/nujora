<?php
require '../app/config.php';
// seller-orders.php

// Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'seller') {
    header("Location: unauthorized.php");
    exit;
}


$seller_id = $_SESSION['user_id'];

try {
    // Fetch seller info
    $stmt = $pdo->prepare("SELECT name, shop_name FROM users WHERE id = ?");
    $stmt->execute([$seller_id]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seller) {
        die("Seller not found.");
    }

    // Fetch orders with product and customer details
    $stmt = $pdo->prepare("
    SELECT 
        o.id AS order_id,
        o.order_date, 
        o.status, 
        o.shipping_address,
        p.name AS product_name,
        p.price AS product_price,
        oi.quantity,
        u.name AS customer_name,
        u.phone AS customer_phone
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    JOIN users u ON o.buyer_id = u.id
    WHERE p.seller_id = ?
    ORDER BY o.order_date DESC
");

    $stmt->execute([$seller_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group order items by order_id
    $grouped_orders = [];
    foreach ($orders as $order) {
        $order_id = $order['order_id'];
        if (!isset($grouped_orders[$order_id])) {
            $grouped_orders[$order_id] = [
                'order_id' => $order['order_id'],
                'order_date' => $order['order_date'],
                'status' => $order['status'],
                'shipping_address' => $order['shipping_address'],
                'customer_name' => $order['customer_name'],
                'customer_phone' => $order['customer_phone'],
                'items' => []
            ];
        }
        $grouped_orders[$order_id]['items'][] = [
            'product_name' => $order['product_name'],
            'product_price' => $order['product_price'],
            'quantity' => $order['quantity']
        ];
    }

    // Handle status updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['update_status'])) {
            $order_id = $_POST['order_id'];
            $new_status = $_POST['new_status'];
            
            try {
                $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
                $stmt->execute([$new_status, $order_id]);
                
                $_SESSION['status_message'] = '<div class="alert alert-success">Order status updated successfully!</div>';
                header("Location: seller-orders.php");
                exit;
            } catch (PDOException $e) {
                $_SESSION['status_message'] = '<div class="alert alert-danger">Error updating order status: ' . $e->getMessage() . '</div>';
            }
        }
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Display status message if exists
$statusMessage = $_SESSION['status_message'] ?? '';
unset($_SESSION['status_message']);
?>
    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Orders Received</h2>
        </div>

        <?php if (isset($statusMessage)) echo $statusMessage; ?>

        <?php if (empty($grouped_orders)): ?>
            <div class="alert alert-info">
                You haven't received any orders yet.
            </div>
        <?php else: ?>
            <?php foreach ($grouped_orders as $order): 
                $status_class = strtolower(str_replace(' ', '-', $order['status']));
            ?>
                <div class="card mb-3 order-card order-<?= $status_class ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title mb-0">Order #<?= $order['order_id'] ?></h5>
                            <span class="status-badge status-<?= $status_class ?>">
                                <?= $order['status'] ?>
                            </span>
                        </div>
                        
                        <p class="text-muted mb-2">
                            <i class="bi bi-calendar"></i> <?= date('M j, Y g:i A', strtotime($order['order_date'])) ?>
                        </p>
                        
                        <h6 class="mt-3">Products:</h6>
                        <ul class="list-group mb-3">
                            <?php foreach ($order['items'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span><?= htmlspecialchars($item['product_name']) ?></span>
                                    <span>
                                        <?= $item['quantity'] ?> × ₦<?= number_format($item['product_price'], 2) ?>
                                        = ₦<?= number_format($item['quantity'] * $item['product_price'], 2) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Customer Details:</h6>
                                <p>
                                    <strong><?= htmlspecialchars($order['customer_name'] ?? '') ?></strong><br>
                                    <i class="bi bi-telephone"></i> <?= htmlspecialchars($order['customer_phone'] ?? '') ?><br>
                                    <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($order['shipping_address'] ?? '') ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <?php if ($order['status'] === 'Pending' || $order['status'] === 'Processing'): ?>
                                    <form method="POST" class="mt-3">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label for="statusSelect<?= $order['order_id'] ?>" class="form-label">Update Status:</label>
                                            <select class="form-select" id="statusSelect<?= $order['order_id'] ?>" name="new_status">
                                                <option value="Processing" <?= $order['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                                <option value="Shipped" <?= $order['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                                <option value="Cancelled" <?= $order['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancel Order</option>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" name="update_status" class="btn btn-primary">
                                            Update Status
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add any JavaScript functionality here if needed
    </script>
</body>
</html>