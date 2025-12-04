<?php
require '../app/config.php';

require_once  '../app/rate_limiter.php';

if (!global_rate_limit(50, 60)) {
    http_response_code(429);
    die("Too many admin requests. Try again in a 3 minute.");
}

// Fetch the latest seller info from the database
$stmt = $pdo->prepare("SELECT is_approved, profile_complete FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// if (
//     isset($_SESSION['role']) && $_SESSION['role'] === 'seller' &&
//     $user &&
//     $user['is_approved'] == 1 &&
//     $user['profile_complete'] != 1 &&
//     basename($_SERVER['PHP_SELF']) !== 'complete_profile.php'
// ) {
//     header('Location: complete_profile.php');
//     exit;
// }


// Security checks
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit;
}

$seller_id = $_SESSION['user_id'];
$statusMessage = '';
$current_tab = $_GET['tab'] ?? 'dashboard';

try {
    // Fetch seller info
    $stmt = $pdo->prepare("SELECT name, shop_name, approval_status FROM users WHERE id = ? AND role = 'seller'");
    $stmt->execute([$seller_id]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seller || $seller['approval_status'] !== 'approved') {
        die($seller ? "<h3>Your account is pending approval. Please wait for admin verification.</h3>" : "Seller not found.");
    }

    // Fetch seller's products
    $stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC");
    $stmt->execute([$seller_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch chat conversations
    $stmt = $pdo->prepare("
        SELECT DISTINCT m.sender_id as buyer_id, u.name as buyer_name 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.receiver_id = ? AND u.role = 'buyer'
    ");
    $stmt->execute([$seller_id]);
    $buyers = $stmt->fetchAll();

    // Handle selected buyer messages
    $selected_buyer = null;
    $messages = [];
    if (isset($_GET['view_messages']) && isset($_GET['buyer_id'])) {
        $buyer_id = (int)$_GET['buyer_id'];
        foreach ($buyers as $buyer) {
            if ($buyer['buyer_id'] == $buyer_id) {
                $selected_buyer = $buyer;
                break;
            }
        }

        if ($selected_buyer) {
            $stmt = $pdo->prepare("
                SELECT m.*, u.name as sender_name 
                FROM messages m
                JOIN users u ON m.sender_id = u.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?)
                OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.sent_at ASC
            ");
            $stmt->execute([$buyer_id, $seller_id, $seller_id, $buyer_id]);
            $messages = $stmt->fetchAll();
        }
    }

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Coupon creation
        if (isset($_POST['create_coupon'])) {
            $code = strtoupper(trim($_POST['code']));
            $discount_type = $_POST['discount_type'];
            $discount_value = (float)$_POST['discount_value'];
            $min_spend = (float)$_POST['min_spend'];
            $expiry_date = $_POST['expiry_date'];
            $coupon_type = $_POST['coupon_type'];
            $max_redemptions = 1;

            $commission = ($discount_type === 'percentage')
                ? ($discount_value / 100) * $min_spend * 0.025
                : $discount_value * 0.025;

            $status = ($coupon_type === 'offline') ? 'pending_payment' : 'pending_approval';

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO coupons 
                    (seller_id, code, discount_type, discount_value, min_spend, expiry_date, 
                    coupon_type, status, max_redemptions, commission)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $seller_id,
                    $code,
                    $discount_type,
                    $discount_value,
                    $min_spend,
                    $expiry_date,
                    $coupon_type,
                    $status,
                    $max_redemptions,
                    $commission
                ]);

                $statusMessage = '<div class="alert alert-success">Coupon created successfully! ' .
                    ($coupon_type === 'offline' ? 'Please complete payment for activation.' : 'Waiting for admin approval.') . '</div>';
            } catch (PDOException $e) {
                $statusMessage = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }

        // Offline coupon redemption
        if (isset($_POST['redeem_offline_coupon'])) {
            $coupon_code = strtoupper(trim($_POST['coupon_code']));
            $order_amount = (float)$_POST['order_amount'];

            try {
                $stmt = $pdo->prepare("
                    SELECT * FROM coupons 
                    WHERE seller_id = ? AND code = ? AND coupon_type = 'offline'
                    AND status = 'active'
                ");
                $stmt->execute([$seller_id, $coupon_code]);
                $coupon = $stmt->fetch();

                if ($coupon) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM coupon_redemptions WHERE coupon_id = ?");
                    $stmt->execute([$coupon['id']]);
                    $redemption_count = $stmt->fetchColumn();

                    if ($redemption_count >= $coupon['max_redemptions']) {
                        $statusMessage = '<div class="alert alert-danger">This coupon has already been redeemed.</div>';
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO coupon_redemptions 
                            (coupon_id, buyer_id, order_amount, redemption_date, redemption_type)
                            VALUES (?, ?, ?, NOW(), 'offline')
                        ");
                        $stmt->execute([$coupon['id'], $selected_buyer['buyer_id'] ?? 0, $order_amount]);

                        if ($redemption_count + 1 >= $coupon['max_redemptions']) {
                            $stmt = $pdo->prepare("UPDATE coupons SET status = 'redeemed' WHERE id = ?");
                            $stmt->execute([$coupon['id']]);
                        }

                        $statusMessage = '<div class="alert alert-success">Coupon redeemed successfully!</div>';
                    }
                } else {
                    $statusMessage = '<div class="alert alert-danger">Invalid coupon code or not approved.</div>';
                }
            } catch (PDOException $e) {
                $statusMessage = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }

        // Order status update
        if (isset($_POST['update_order_status'])) {
            $order_id = (int)$_POST['order_id'];
            $new_status = $_POST['status'];
            $allowed_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];

            if (in_array($new_status, $allowed_statuses)) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT o.id 
                        FROM orders o
                        JOIN order_items oi ON o.id = oi.order_id
                        JOIN products p ON oi.product_id = p.id
                        WHERE o.id = ? AND p.seller_id = ?
                        LIMIT 1
                    ");
                    $stmt->execute([$order_id, $seller_id]);
                    $valid_order = $stmt->fetch();

                    if ($valid_order) {
                        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                        $stmt->execute([$new_status, $order_id]);

                        $stmt = $pdo->prepare("
                            INSERT INTO order_status_history 
                            (order_id, status, changed_by, changed_at)
                            VALUES (?, ?, 'seller', NOW())
                        ");
                        $stmt->execute([$order_id, $new_status]);

                        $statusMessage = '<div class="alert alert-success">Order status updated successfully</div>';
                    } else {
                        $statusMessage = '<div class="alert alert-danger">Order not found or not yours</div>';
                    }
                } catch (PDOException $e) {
                    $statusMessage = '<div class="alert alert-danger">Error updating order: ' . $e->getMessage() . '</div>';
                }
            } else {
                $statusMessage = '<div class="alert alert-danger">Invalid order status</div>';
            }
        }
    }

    // Fetch coupons with analytics
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(r.id) as redemption_count,
               SUM(r.order_amount) as total_sales,
               SUM(c.commission) as total_commission
        FROM coupons c
        LEFT JOIN coupon_redemptions r ON c.id = r.coupon_id
        WHERE c.seller_id = ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$seller_id]);
    $coupons = $stmt->fetchAll();

    // Fetch loyal customers
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, COUNT(r.id) as coupon_usage
        FROM coupon_redemptions r
        JOIN coupons c ON r.coupon_id = c.id
        JOIN users u ON r.buyer_id = u.id
        WHERE c.seller_id = ?
        GROUP BY u.id, u.name, u.email
        ORDER BY coupon_usage DESC
        LIMIT 5
    ");
    $stmt->execute([$seller_id]);
    $loyal_customers = $stmt->fetchAll();

    // Fetch orders with status
    $stmt = $pdo->prepare("
        SELECT o.*, oi.product_id, p.name as product_name, 
               u.name as buyer_name, oi.quantity, oi.price,
               (SELECT status FROM order_status_history 
                WHERE order_id = o.id 
                ORDER BY changed_at DESC LIMIT 1) as current_status
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        JOIN users u ON o.buyer_id = u.id
        WHERE p.seller_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$seller_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

/// Ensure session contains user details
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteProduct'])) {
    if (isset($_POST['id']) && is_numeric($_POST['id']) && $user_id && $user_role === 'seller') {
        $id = (int) $_POST['id'];
        try {
            // Check if product belongs to this seller
            $checkStmt = $pdo->prepare("SELECT seller_id FROM products WHERE id = :id");
            $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $checkStmt->execute();
            $product = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($product && $product['seller_id'] == $user_id) {
                // Delete the product
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id AND seller_id = :seller_id");
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':seller_id', $user_id, PDO::PARAM_INT);

                if ($stmt->execute() && $stmt->rowCount() > 0) {
                    $statusMessage = '<div class="alert alert-success">Product deleted successfully</div>';
                    return header("Location: seller-dashboard.php?tab=products");
                } else {
                    $statusMessage = '<div class="alert alert-danger">Failed to delete product. Try again.</div>';
                }
            } else {
                $statusMessage = '<div class="alert alert-warning">You are not allowed to delete this product.</div>';
            }
        } catch (PDOException $e) {
            $statusMessage = '<div class="alert alert-danger">Database error: ' . $e->getMessage() . '</div>';
        }
    } else {
        $statusMessage = '<div class="alert alert-danger">Invalid product ID or unauthorized access.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:image" content="uploads/default-product.png" />
    <title>Seller Dashboard | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #6a11cb;
            --secondary-color: #2575fc;
            --accent-color: #957156;
            --light-bg: #f8f9fa;
            --dark-text: #2B2A26;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 2000;
        }

        @media (max-width: 768px) {

            .main-content {
                margin-left: 0;
            }
        }

        @media (min-width: 992px) {
            .content {
                margin-left: 250px !important;
            }
        }


        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }

        .stat-card {
            text-align: center;
            padding: 20px;
            color: white;
            border-radius: 10px;
            margin: 10px 0;
        }

        .product-img {
            height: 200px;
            object-fit: contain;
            width: 100%;
        }

        .chat-container {
            height: 60vh;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f8f9fa;
        }

        .message {
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            max-width: 70%;
        }

        .buyer-message {
            background-color: #d1e7dd;
            margin-right: auto;
        }

        .seller-message {
            background-color: #fff3cd;
            margin-left: auto;
        }

        .coupon-status-badge {
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 10px;
        }

        .status-pending {
            background: #ffc107;
            color: #000;
        }

        .status-active {
            background: #28a745;
            color: #fff;
        }

        .status-redeemed {
            background: #6c757d;
            color: #fff;
        }

        .status-pending_payment {
            background: #fd7e14;
            color: #fff;
        }

        .nav-tabs .nav-link.active {
            font-weight: 600;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div id="sellerSidebar"
        class="sidebar bg-dark text-white p-3 d-none d-lg-flex flex-column flex-shrink-0 border-end">

        <!-- Header -->
        <div class="mb-4 mt-3">
            <h5 class="mb-0"><?= htmlspecialchars($seller['shop_name'] ?? $seller['name']) ?></h5>
            <small class="text-light">Seller Dashboard</small>
        </div>

        <!-- Menu -->
        <ul class="nav nav-pills flex-column mb-auto">

            <li class="nav-item">
                <a href="seller-dashboard.php"
                    class="nav-link text-white <?= $current_tab === 'dashboard' ? 'active bg-secondary' : '' ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>

            <li>
                <a href="seller-dashboard.php?tab=products"
                    class="nav-link text-white <?= $current_tab === 'products' ? 'active bg-secondary' : '' ?>">
                    <i class="bi bi-box-seam me-2"></i> Products
                </a>
            </li>

            <li>
                <a href="seller-dashboard.php?tab=messages"
                    class="nav-link text-white <?= $current_tab === 'messages' ? 'active bg-secondary' : '' ?>">
                    <i class="bi bi-chat-dots me-2"></i> Messages
                </a>
            </li>

            <!-- <li>
                <a href="seller-dashboard.php?tab=coupons"
                    class="nav-link text-white <?= $current_tab === 'coupons' ? 'active bg-secondary' : '' ?>">
                    <i class="bi bi-percent me-2"></i> Coupons
                </a>
            </li> -->

            <li>
                <a href="seller-dashboard.php?tab=orders"
                    class="nav-link text-white <?= $current_tab === 'orders' ? 'active bg-secondary' : '' ?>">
                    <i class="bi bi-cart me-2"></i> Orders
                </a>
            </li>

            <li class="mt-3">
                <a href="../auth/logout.php" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                </a>
            </li>

        </ul>
    </div>

    <!-- Mobile Sidebar -->
    <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="mobileSidebar">

        <div class="offcanvas-header">
            <h5 class="offcanvas-title"><?= htmlspecialchars($seller['shop_name'] ?? $seller['name']) ?></h5>
            <button class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>

        <div class="offcanvas-body">
            <ul class="nav nav-pills flex-column mb-auto">

                <li class="nav-item">
                    <a href="seller-dashboard.php"
                        class="nav-link text-white <?= $current_tab === 'dashboard' ? 'active bg-secondary' : '' ?>">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>

                <li>
                    <a href="seller-dashboard.php?tab=products"
                        class="nav-link text-white <?= $current_tab === 'products' ? 'active bg-secondary' : '' ?>">
                        <i class="bi bi-box-seam me-2"></i> Products
                    </a>
                    </a>
                </li>

                <li>
                    <a href="seller-dashboard.php?tab=messages"
                        class="nav-link text-white <?= $current_tab === 'messages' ? 'active bg-secondary' : '' ?>">
                        <i class="bi bi-chat-dots me-2"></i> Messages
                    </a>
                </li>

                <!-- <li>
                    <a href="seller-dashboard.php?tab=coupons"
                        class="nav-link text-white <?= $current_tab === 'coupons' ? 'active bg-secondary' : '' ?>">
                        <i class="bi bi-percent me-2"></i> Coupons
                    </a>
                </li> -->

                <li>
                    <a href="seller-dashboard.php?tab=orders"
                        class="nav-link text-white <?= $current_tab === 'orders' ? 'active bg-secondary' : '' ?>">
                        <i class="bi bi-cart me-2"></i> Orders
                    </a>
                </li>

                <li class="mt-3">
                    <a href="../auth/logout.php" class="nav-link text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </a>
                </li>

            </ul>
        </div>
    </div>



    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Seller Dashboard</h2>
            <div class="text-end">
                <span class="badge bg-success">Approved</span>
            </div>
            <!-- Mobile Sidebar Toggle -->
            <button class="btn btn-dark d-lg-none mb-2" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                <i class="bi bi-list"></i>
            </button>
        </div>

        <?php if (isset($statusMessage)) echo $statusMessage; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary">
                    <i class="bi bi-box-seam"></i>
                    <h3><?= count($products) ?></h3>
                    <p>Products</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success">
                    <i class="bi bi-people"></i>
                    <h3><?= count($buyers) ?></h3>
                    <p>Customers</p>
                </div>
            </div>
            <!-- <div class="col-md-3">
                <div class="stat-card bg-info">
                    <i class="bi bi-percent"></i>
                    <h3><?= count($coupons) ?></h3>
                    <p>Active Coupons</p>
                </div>
            </div> -->
            <div class="col-md-3">
                <div class="stat-card bg-warning">
                    <i class="bi bi-cash"></i>
                    <h3>₦0</h3>
                    <p>This Month</p>
                </div>
            </div>
        </div>

        <!-- Tab Content -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-4" style="background-color: #f8f9fa; padding: 10px 15px;">
                <li class="breadcrumb-item">
                    <a href="?tab=products" class="<?= $current_tab === 'products' ? 'fw-bold text-primary' : '' ?>">
                        Products
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="?tab=messages" class="<?= $current_tab === 'messages' ? 'fw-bold text-primary' : '' ?>">
                        Messages
                    </a>
                </li>
                <!-- <li class="breadcrumb-item">
                    <a href="?tab=coupons" class="<?= $current_tab === 'coupons' ? 'fw-bold text-primary' : '' ?>">
                        Coupons
                    </a>
                </li> -->
                <li class="breadcrumb-item">
                    <a href="?tab=orders" class="<?= $current_tab === 'orders' ? 'fw-bold text-primary' : '' ?>">
                        Orders
                    </a>
                </li>
            </ol>
        </nav>


        <!-- Products Tab -->
        <div class="tab-content" id="productsTab" style="<?= $current_tab === 'products' ? '' : 'display:none;' ?>">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Your Products</h4>
                <a href="add_product.php" class="btn btn-primary">
                    <i class="bi bi-plus"></i> Add Product
                </a>
            </div>

            <?php if (empty($products)): ?>
                <div class="alert alert-info">
                    You haven't added any products yet. <a href="add_product.php">Add your first product</a>.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <img src="<?= htmlspecialchars($product['image_url']) ?>"
                                    class="product-img"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    onerror="this.src='https://via.placeholder.com/300'">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    <p class="card-text">₦<?= number_format($product['price'], 2) ?></p>
                                    <div class="d-flex justify-content-between">
                                        <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="seller-dashboard.php" method="POST" style="display:inline;"
                                            onsubmit="return confirm('Are you sure you want to delete this product?')">
                                            <input type="hidden" name="deleteProduct" value="1">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($product['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>

                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Messages Tab -->
        <div class="tab-content" id="messagesTab" style="<?= $current_tab === 'messages' ? '' : 'display:none;' ?>">
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <i class="bi bi-people"></i> Customers
                        </div>
                        <div class="card-body buyer-list">
                            <?php if (empty($buyers)): ?>
                                <p class="text-muted">No conversations yet</p>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($buyers as $buyer): ?>
                                        <a href="?tab=messages&buyer_id=<?= $buyer['buyer_id'] ?>"
                                            class="list-group-item list-group-item-action <?= isset($_GET['buyer_id']) && $_GET['buyer_id'] == $buyer['buyer_id'] ? 'active' : '' ?>">
                                            <?= htmlspecialchars($buyer['buyer_name']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <?php if (isset($selected_buyer)): ?>
                                <i class="bi bi-chat-left-text"></i> Conversation with <?= htmlspecialchars($selected_buyer['buyer_name']) ?>
                            <?php else: ?>
                                <i class="bi bi-chat-left-text"></i> Select a conversation
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (isset($selected_buyer)): ?>
                                <div class="chat-container" id="chatContainer">
                                    <?php if (empty($messages)): ?>
                                        <p class="text-muted">No messages in this conversation yet</p>
                                    <?php else: ?>
                                        <?php foreach ($messages as $message): ?>
                                            <div class="message <?= $message['sender_id'] == $seller_id ? 'seller-message' : 'buyer-message' ?>">
                                                <div><?= htmlspecialchars($message['message']) ?></div>
                                                <div class="message-time">
                                                    <?= date('M j, Y g:i A', strtotime($message['sent_at'])) ?>
                                                    <?php if ($message['sender_id'] == $seller_id): ?>
                                                        <span class="badge bg-info">You</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary"><?= htmlspecialchars($message['sender_name']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <form method="POST" class="mt-3">
                                    <input type="hidden" name="buyer_id" value="<?= $selected_buyer['buyer_id'] ?>">
                                    <div class="input-group">
                                        <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                                        <button type="submit" class="btn btn-primary">Send</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-chat-square-text" style="font-size: 3rem; color: #6c757d;"></i>
                                    <p class="mt-3">Please select a conversation from the list</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coupons Tab -->
        <div class="tab-content" id="couponsTab" style="<?= $current_tab === 'coupons' ? '' : 'display:none;' ?>">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Coupon Management</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCouponModal">
                    <i class="bi bi-plus"></i> Create Coupon
                </button>
            </div>

            <!-- Offline Coupon Redemption Form -->
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <i class="bi bi-upc-scan"></i> Offline Coupon Redemption
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="coupon_code" class="form-label">Coupon Code</label>
                                    <input type="text" class="form-control" id="coupon_code" name="coupon_code" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="order_amount" class="form-label">Order Amount (₦)</label>
                                    <input type="number" class="form-control" id="order_amount" name="order_amount" min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" name="redeem_offline_coupon" class="btn btn-primary w-100">
                                    Redeem
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Coupons Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Discount</th>
                                    <th>Status</th>
                                    <th>Redemptions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coupons as $coupon):
                                    $is_expired = strtotime($coupon['expiry_date']) < time();
                                ?>
                                    <tr class="<?= $is_expired ? 'table-secondary' : '' ?>">
                                        <td><strong><?= htmlspecialchars($coupon['code']) ?></strong></td>
                                        <td><?= ucfirst($coupon['coupon_type']) ?></td>
                                        <td>
                                            <?= $coupon['discount_type'] === 'percentage'
                                                ? $coupon['discount_value'] . '%'
                                                : '₦' . number_format($coupon['discount_value'], 2) ?>
                                        </td>
                                        <td>
                                            <span class="coupon-status-badge status-<?= str_replace('_', '-', $coupon['status']) ?>">
                                                <?= ucfirst(str_replace('_', ' ', $coupon['status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= $coupon['redemption_count'] ?> / <?= $coupon['max_redemptions'] ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Details</button>
                                            <?php if ($coupon['coupon_type'] === 'offline' && $coupon['status'] === 'pending_payment'): ?>
                                                <a href="pay_commission.php?coupon_id=<?= $coupon['id'] ?>" class="btn btn-sm btn-success">Pay Commission</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Tab -->
        <div class="tab-content" id="ordersTab" style="<?= $current_tab === 'orders' ? '' : 'display:none;' ?>">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Order Management</span>
                </div>
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <div class="alert alert-info">No orders found</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Product</th>
                                        <th>Buyer</th>
                                        <th>Date</th>
                                        <th>Qty</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?= htmlspecialchars($order['id']) ?></td>
                                            <td><?= htmlspecialchars($order['product_name']) ?></td>
                                            <td><?= htmlspecialchars($order['buyer_name']) ?></td>
                                            <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($order['quantity']) ?></td>
                                            <td>₦<?= number_format($order['price'] * $order['quantity'], 2) ?></td>
                                            <td>
                                                <span class="badge 
                                                <?= $order['current_status'] === 'completed' ? 'bg-success' : '' ?>
                                                <?= $order['current_status'] === 'cancelled' ? 'bg-danger' : '' ?>
                                                <?= $order['current_status'] === 'shipped' ? 'bg-info' : '' ?>
                                                <?= $order['current_status'] === 'processing' ? 'bg-warning' : '' ?>
                                                <?= $order['current_status'] === 'pending' ? 'bg-info' : 'text-muted' ?>
                                            ">
                                                    <?= ucfirst($order['current_status'] ?? 'pending') ?>
                                                </span>
                                            </td>
                                            <td width="220px">
                                                <div class="d-flex">
                                                    <form method="post" class="me-2"
                                                        onsubmit="return confirm('Are you sure you want to update this order status?')">
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                        <div class="input-group input-group-sm">
                                                            <select name="status" class="form-select form-select-sm" required>
                                                                <option value="pending" <?= $order['current_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                                <option value="processing" <?= $order['current_status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                                <option value="shipped" <?= $order['current_status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                                <option value="completed" <?= $order['current_status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                                <option value="cancelled" <?= $order['current_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                            </select>
                                                            <button type="submit" name="update_order_status" class="btn btn-primary btn-sm">
                                                                <i class="bi bi-check"></i>
                                                            </button>
                                                        </div>
                                                    </form>
                                                    <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Coupon Modal -->
    <div class="modal fade" id="createCouponModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Coupon</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Coupon Type Toggle -->
                        <div class="mb-3">
                            <label class="form-label">Coupon Type</label>
                            <div class="d-flex">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="coupon_type" id="couponTypeOnline" value="online" checked>
                                    <label class="form-check-label" for="couponTypeOnline">
                                        Online
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="coupon_type" id="couponTypeOffline" value="offline">
                                    <label class="form-check-label" for="couponTypeOffline">
                                        Offline
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Coupon Code -->
                        <div class="mb-3">
                            <label for="couponCode" class="form-label">Coupon Code</label>
                            <input type="text" class="form-control" id="couponCode" name="code" required
                                placeholder="e.g., SUMMER20" pattern="[A-Z0-9]{4,20}">
                            <small class="text-muted">Uppercase letters and numbers only (4-20 characters)</small>
                        </div>

                        <!-- Discount Details -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Discount Type</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="discount_type"
                                            id="discountPercentage" value="percentage" checked>
                                        <label class="form-check-label" for="discountPercentage">
                                            Percentage
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="discount_type"
                                            id="discountFixed" value="fixed">
                                        <label class="form-check-label" for="discountFixed">
                                            Fixed Amount
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="discountValue" class="form-label">Discount Value</label>
                                    <input type="number" class="form-control" id="discountValue" name="discount_value"
                                        min="1" max="100" step="0.01" required>
                                </div>
                            </div>
                        </div>



                        <!-- Minimum Spend -->
                        <div class="mb-3">
                            <label for="minSpend" class="form-label">Minimum Spend (₦)</label>
                            <input type="number" class="form-control" id="minSpend" name="min_spend"
                                min="0" step="100" required>
                        </div>

                        <!-- Expiry Date -->
                        <div class="mb-3">
                            <label for="expiryDate" class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" id="expiryDate" name="expiry_date"
                                min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        </div>

                        <!-- Commission Preview -->
                        <div class="alert alert-info" id="commissionPreview">
                            <strong>Estimated Commission:</strong> ₦0.00 (2.5% of discount value)
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_coupon" class="btn btn-primary">Create Coupon</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateCommissionPreview() {
            const minSpend = parseFloat(document.getElementById('minSpend').value) || 0;

            // Calculate 2.5% commission on minSpend
            const commission = minSpend * 0.025;

            // Display result
            document.getElementById('commissionPreview').innerHTML = `
            <strong>Estimated Commission:</strong> ₦${commission.toFixed(2)} (2.5% of Minimum Spend)
        `;
        }

        // Add event listener only for minSpend since discount type/value are no longer relevant
        document.getElementById('minSpend').addEventListener('input', updateCommissionPreview);

        // Initialize preview
        updateCommissionPreview();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>