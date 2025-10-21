<?php
require '../app/config.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../auth/login.php");
    exit;
}
$buyer_id = $_SESSION['user_id'];
$statusMessage = '';
$current_tab = $_GET['tab'] ?? 'all';

try {
    // Fetch all orders with their status history
    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT status FROM order_status_history 
                WHERE order_id = o.id 
                ORDER BY changed_at DESC LIMIT 1) as current_status,
               GROUP_CONCAT(oi.product_id) as product_ids,
               GROUP_CONCAT(p.name) as product_names,
               GROUP_CONCAT(oi.quantity) as quantities,
               GROUP_CONCAT(oi.price) as prices,
               GROUP_CONCAT(p.image_url) as image_urls
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.buyer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$buyer_id]);
    $all_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter orders by status
    $orders = array_filter($all_orders, function ($order) use ($current_tab) {
        return $current_tab === 'all' || $order['current_status'] === $current_tab;
    });

    // Count orders by status for tabs
    $status_counts = [
        'all' => count($all_orders),
        'pending' => 0,
        'processing' => 0,
        'shipped' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];

    foreach ($all_orders as $order) {
        if (isset($status_counts[$order['current_status']])) {
            $status_counts[$order['current_status']]++;
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="../uploads/default-product.png">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6a11cb;
            --secondary-color: #2575fc;
            --light-bg: #f8f9fa;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: var(--light-bg);
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Poppins', sans-serif;
        }

        .order-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
            transition: transform 0.3s;
        }

        .order-card:hover {
            transform: translateY(-3px);
        }

        .order-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }

        .order-product {
            display: flex;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-product:last-child {
            border-bottom: none;
        }

        .product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #ffc107;
            color: #000;
        }

        .status-processing {
            background-color: #17a2b8;
            color: #fff;
        }

        .status-shipped {
            background-color: #007bff;
            color: #fff;
        }

        .status-completed {
            background-color: #28a745;
            color: #fff;
        }

        .status-cancelled {
            background-color: #dc3545;
            color: #fff;
        }

        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
            border: none;
            padding: 10px 15px;
            position: relative;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background: transparent;
        }

        .nav-tabs .nav-link.active:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-color);
        }

        .nav-tabs .nav-link .badge {
            font-size: 0.7rem;
            margin-left: 5px;
        }

        .tracking-steps {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            position: relative;
        }

        .tracking-step {
            text-align: center;
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 5px;
        }

        .step-active .step-icon {
            background: var(--primary-color);
            color: white;
        }

        .step-complete .step-icon {
            background: #28a745;
            color: white;
        }

        .step-text {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .step-active .step-text,
        .step-complete .step-text {
            color: var(--primary-color);
            font-weight: 500;
        }

        .tracking-line {
            position: absolute;
            top: 35px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 0;
        }

        .tracking-progress {
            position: absolute;
            top: 35px;
            left: 0;
            height: 2px;
            background: var(--primary-color);
            z-index: 1;
            transition: width 0.3s;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <?php include 'includes/nav.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Orders</h2>
            <div class="text-muted">Showing <?= count($orders) ?> of <?= count($all_orders) ?> orders</div>
        </div>

        <!-- Order Status Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'all' ? 'active' : '' ?>" href="?tab=all">
                    All Orders <span class="badge bg-secondary"><?= $status_counts['all'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'pending' ? 'active' : '' ?>" href="?tab=pending">
                    Pending <span class="badge bg-warning"><?= $status_counts['pending'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'processing' ? 'active' : '' ?>" href="?tab=processing">
                    Processing <span class="badge bg-info"><?= $status_counts['processing'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'shipped' ? 'active' : '' ?>" href="?tab=shipped">
                    Shipped <span class="badge bg-primary"><?= $status_counts['shipped'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'completed' ? 'active' : '' ?>" href="?tab=completed">
                    Completed <span class="badge bg-success"><?= $status_counts['completed'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'cancelled' ? 'active' : '' ?>" href="?tab=cancelled">
                    Cancelled <span class="badge bg-danger"><?= $status_counts['cancelled'] ?></span>
                </a>
            </li>
        </ul>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="bi bi-box-seam" style="font-size: 3rem; color: #6c757d;"></i>
                <h4 class="mt-3">No orders found</h4>
                <p class="text-muted">You haven't placed any orders yet</p>
                <a href="products.php" class="btn btn-primary mt-3">Shop Now</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order):
                $product_ids = explode(',', $order['product_ids']);
                $product_names = explode(',', $order['product_names']);
                $quantities = explode(',', $order['quantities']);
                $prices = explode(',', $order['prices']);
                $image_urls = explode(',', $order['image_urls']);

                // Calculate order total
                $order_total = 0;
                foreach ($prices as $index => $price) {
                    $order_total += $price * $quantities[$index];
                }

                // Tracking progress
                $tracking_steps = [
                    'pending' => ['icon' => 'bi-cart', 'text' => 'Order Placed'],
                    'processing' => ['icon' => 'bi-gear', 'text' => 'Processing'],
                    'shipped' => ['icon' => 'bi-truck', 'text' => 'Shipped'],
                    'completed' => ['icon' => 'bi-check-circle', 'text' => 'Delivered']
                ];

                $current_step_index = array_search($order['current_status'], array_keys($tracking_steps));
                $progress_width = $current_step_index !== false ? ($current_step_index / (count($tracking_steps) - 1)) * 100 : 0;
            ?>
                <div class="order-card bg-white mb-4">
                    <div class="order-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Order #<?= $order['id'] ?></h5>
                            <small class="text-muted">Placed on <?= date('M j, Y', strtotime($order['created_at'])) ?></small>
                        </div>
                        <div>
                            <span class="status-badge status-<?= $order['current_status'] ?>">
                                <?= ucfirst($order['current_status'] ?? 'pending') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Order Tracking -->
                    <div class="px-3 pt-3">
                        <div class="tracking-steps">
                            <div class="tracking-line"></div>
                            <div class="tracking-progress" style="width: <?= $progress_width ?>%"></div>
                            <?php foreach ($tracking_steps as $status => $step):
                                $is_active = $order['current_status'] === $status;
                                $is_complete = array_search($status, array_keys($tracking_steps)) < $current_step_index;
                            ?>
                                <div class="tracking-step <?= $is_active ? 'step-active' : '' ?> <?= $is_complete ? 'step-complete' : '' ?>">
                                    <div class="step-icon">
                                        <i class="bi <?= $step['icon'] ?>"></i>
                                    </div>
                                    <div class="step-text"><?= $step['text'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Order Products -->
                    <?php foreach ($product_ids as $index => $product_id): ?>
                        <div class="order-product">
                            <img src="../<?= htmlspecialchars($image_urls[$index]) ?? "../uploads/default-product.png" ?>"
                                class="product-img"
                                alt="<?= htmlspecialchars($product_names[$index]) ?>">
                            <div class="flex-grow-1">
                                <h6><?= htmlspecialchars($product_names[$index]) ?></h6>
                                <div class="text-muted">Qty: <?= $quantities[$index] ?></div>
                                <div>₦<?= number_format($prices[$index], 2) ?></div>
                            </div>
                            <div class="text-end">
                                <div>₦<?= number_format($prices[$index] * $quantities[$index], 2) ?></div>
                                <a href="product.php?id=<?= $product_id ?>" class="btn btn-sm btn-outline-primary mt-2">
                                    View Product
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Order Summary -->
                    <div class="order-footer p-3 bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <?php if ($order['current_status'] === 'pending' || $order['current_status'] === 'processing'): ?>
                                <a href="cancel-order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Are you sure you want to cancel this order?')">
                                    Cancel Order
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <div class="text-muted">Total <?= count($product_ids) ?> item(s)</div>
                            <h5 class="mb-0">₦<?= number_format($order_total, 2) ?></h5>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    <!-- Script -->
    <?php include 'includes/script.php'; ?>

</body>

</html>