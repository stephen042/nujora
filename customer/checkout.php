<?php
require '../app/config.php';

// Helper function to get proof of payment for a transaction
function getProofOfPayment($pdo, $transaction_reference)
{
    $stmt = $pdo->prepare("
        SELECT proof_path, created_at 
        FROM proof_of_payment 
        WHERE transaction_reference = ?
    ");
    $stmt->execute([$transaction_reference]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper function to get transaction with proof
function getTransactionWithProof($pdo, $purchase_id)
{
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            p.proof_path,
            p.created_at as proof_uploaded_at
        FROM transactions t
        LEFT JOIN proof_of_payment p ON t.reference = p.transaction_reference
        WHERE t.purchase_id = ?
    ");
    $stmt->execute([$purchase_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper function to get all transactions with proofs for a user
function getUserTransactionsWithProofs($pdo, $user_id)
{
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            pu.order_number,
            pu.total_amount,
            p.proof_path,
            p.created_at as proof_uploaded_at
        FROM transactions t
        JOIN purchases pu ON t.purchase_id = pu.purchase_id
        LEFT JOIN proof_of_payment p ON t.reference = p.transaction_reference
        WHERE pu.user_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Enhanced security check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SESSION['role'] !== 'buyer') {
    header('Location: unauthorized.php');
    exit;
}

$buyer_id = $_SESSION['user_id'];
$error = '';
$success = '';
$discount = 0;
$discount_message = '';
$applied_promo = '';

// Initialize order ID variable
$order_id = null;

// Initialize cart items array
$cart_items = [];

// STEP 1: Handle proof of payment upload via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    header('Content-Type: application/json');

    try {
        $file = $_FILES['payment_proof'];

        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error');
        }

        // Check file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File too large. Maximum 5MB allowed.');
        }

        // Check file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, PNG, PDF allowed.');
        }

        // Create upload directory if it doesn't exist
        $upload_dir = '../uploads/payment_proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_filename = 'proof_' . uniqid() . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Failed to save uploaded file');
        }

        // Generate a unique transaction reference (this will be used as both transaction_reference and reference)
        $transaction_reference = 'TXN_' . strtoupper(uniqid()) . '_' . time();

        // Save to proof_of_payment table
        $stmt = $pdo->prepare("
            INSERT INTO proof_of_payment (transaction_reference, proof_path, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$transaction_reference, $upload_path]);

        // Store transaction reference in session for later use
        $_SESSION['payment_proof_reference'] = $transaction_reference;

        echo json_encode([
            'success' => true,
            'message' => 'Payment proof uploaded successfully!',
            'reference' => $transaction_reference
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle promo code validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_promo'])) {
    $promo_code = trim($_POST['promo_code']);

    if (!empty($promo_code)) {
        try {
            $stmt = $pdo->prepare("
                SELECT discount_type, discount_value, min_spend, expiry_date 
                FROM coupons 
                WHERE code = ? AND status = 'active' AND expiry_date > NOW()
            ");
            $stmt->execute([$promo_code]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($coupon) {
                $applied_promo = $promo_code;
                $_SESSION['applied_promo'] = $promo_code;
                $discount_message = "Promo code applied successfully!";
            } else {
                $error = "Invalid or expired promo code.";
            }
        } catch (PDOException $e) {
            $error = "Error validating promo code. Please try again.";
        }
    }
}

// STEP 2: Handle order placement (after proof upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_SPECIAL_CHARS);
    $delivery_method = filter_input(INPUT_POST, 'delivery_method', FILTER_SANITIZE_SPECIAL_CHARS);
    $shipping_address = filter_input(INPUT_POST, 'shipping_address', FILTER_SANITIZE_SPECIAL_CHARS);

    // Check if bank transfer is selected and proof is uploaded
    if ($payment_method === 'pay_with_transfer' && !isset($_SESSION['payment_proof_reference'])) {
        $error = "Please upload payment proof before placing order.";
    } elseif (
        empty($payment_method) || empty($delivery_method) ||
        ($delivery_method === 'home_delivery' && empty($shipping_address))
    ) {
        $error = "Please fill all required fields.";
    } else {
        // Fetch cart items with product details
        $stmt = $pdo->prepare("
            SELECT ci.product_id, p.price, ci.quantity, p.name, p.seller_id, p.image_url
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.buyer_id = ?
        ");
        $stmt->execute([$buyer_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($cart_items) > 0) {
            $pdo->beginTransaction();

            try {
                // Calculate total and apply promo if valid
                $subtotal = 0;
                foreach ($cart_items as $item) {
                    if (!isset($item['product_id']) || empty($item['product_id'])) {
                        throw new Exception("Missing product_id in cart item");
                    }
                    $subtotal += $item['price'] * $item['quantity'];
                }

                // Apply discount if promo code is valid
                $discount_amount = 0;
                if ($applied_promo) {
                    $stmt = $pdo->prepare("
                        SELECT discount_type, discount_value 
                        FROM coupons 
                        WHERE code = ? AND status = 'active' AND expiry_date > NOW()
                    ");
                    $stmt->execute([$applied_promo]);
                    $coupon = $stmt->fetch();

                    if ($coupon) {
                        if ($coupon['discount_type'] === 'percentage') {
                            $discount_amount = ($coupon['discount_value'] / 100) * $subtotal;
                        } else {
                            $discount_amount = min($coupon['discount_value'], $subtotal);
                        }
                    }
                }

                $total = $subtotal - $discount_amount;

                // Create order
                $stmt = $pdo->prepare("
                    INSERT INTO orders (
                        buyer_id,
                        order_date,
                        payment_method,
                        delivery_method,
                        shipping_address,
                        status,
                        subtotal,
                        total,
                        promo_code,
                        discount
                    ) VALUES (?, NOW(), ?, ?, ?, 'pending', ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $buyer_id,
                    $payment_method,
                    $delivery_method,
                    $shipping_address,
                    $subtotal,
                    $total,
                    $applied_promo,
                    $discount_amount
                ]);

                $order_id = $pdo->lastInsertId();

                // Insert order items
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, price, quantity, seller_id, image_url)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                foreach ($cart_items as $item) {
                    $stmt->execute([
                        $order_id,
                        $item['product_id'],
                        $item['price'],
                        $item['quantity'],
                        $item['seller_id'],
                        $item['image_url'] ?? null
                    ]);
                }

                // STEP 3: Create purchase record for transaction tracking
                $purchase_id = 'PUR_' . strtoupper(uniqid()) . '_' . time();

                $stmt = $pdo->prepare("
                    INSERT INTO purchases (purchase_id, user_id, total_amount, order_number, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())
                ");
                $stmt->execute([$purchase_id, $buyer_id, $total, 'ORD_' . $order_id]);

                // STEP 4: Create transaction record
                $payment_reference = $_SESSION['payment_proof_reference'] ?? null;

                if ($payment_method === 'pay_with_transfer' && $payment_reference) {
                    // Use the SAME reference from proof_of_payment table
                    $stmt = $pdo->prepare("
                        INSERT INTO transactions (purchase_id, payment_method, amount, status, reference, created_at, updated_at)
                        VALUES (?, 'bank_transfer', ?, 'pending', ?, NOW(), NOW())
                    ");
                    $stmt->execute([$purchase_id, $total, $payment_reference]);
                } else {
                    // For other payment methods (no proof needed)
                    $payment_method_db = ($payment_method === 'pay_with_card') ? 'card' : 'wallet';
                    $reference = 'REF_' . strtoupper(uniqid()) . '_' . time();
                    $stmt = $pdo->prepare("
                        INSERT INTO transactions (purchase_id, payment_method, amount, status, reference, created_at, updated_at)
                        VALUES (?, ?, ?, 'pending', ?, NOW(), NOW())
                    ");
                    $stmt->execute([$purchase_id, $payment_method_db, $total, $reference]);
                }

                // Clear cart
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE buyer_id = ?");
                $stmt->execute([$buyer_id]);

                // Mark promo code as used if applicable
                if ($applied_promo) {
                    $stmt = $pdo->prepare("
                        UPDATE coupons 
                        SET max_redemptions = max_redemptions - 1 
                        WHERE code = ?
                    ");
                    $stmt->execute([$applied_promo]);
                    unset($_SESSION['applied_promo']);
                }

                // Clear payment proof reference
                unset($_SESSION['payment_proof_reference']);

                $pdo->commit();

                // Redirect to order confirmation
                header("Location: order-confirmation.php?order_id=$order_id");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Failed to place order. Please try again. " . $e->getMessage();
                error_log("Order placement failed: " . $e->getMessage());
            }
        } else {
            $error = "Your cart is empty.";
        }
    }
}

// Fetch cart items for display
$stmt = $pdo->prepare("
    SELECT p.id AS product_id, p.name, p.price, p.image_url, ci.quantity, 
           (p.price * ci.quantity) AS item_total
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.buyer_id = ?
");
$stmt->execute([$buyer_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['item_total'];
}

// Apply discount if promo code is set
$discount_amount = 0;
$total = $subtotal;
if (isset($_SESSION['applied_promo'])) {
    $applied_promo = $_SESSION['applied_promo'];
    $stmt = $pdo->prepare("
        SELECT discount_type, discount_value 
        FROM coupons 
        WHERE code = ? AND status = 'active' AND expiry_date > NOW()
    ");
    $stmt->execute([$applied_promo]);
    $coupon = $stmt->fetch();

    if ($coupon) {
        if ($coupon['discount_type'] === 'percentage') {
            $discount_amount = ($coupon['discount_value'] / 100) * $subtotal;
        } else {
            $discount_amount = min($coupon['discount_value'], $subtotal);
        }
        $total = $subtotal - $discount_amount;
        $discount_message = "Discount applied: " .
            ($coupon['discount_type'] === 'percentage' ?
                $coupon['discount_value'] . '%' :
                '₦' . number_format($coupon['discount_value'], 2));
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Nojura</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../uploads/default-product.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #f57c00;
            /* Bright Orange */
            --secondary-color: #ef6c00;
            --accent-color: #957156;
            --light-bg: #f8f9fa;
            --dark-text: #2B2A26;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            padding-bottom: 60px;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Poppins', sans-serif;
        }

        .product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }


        .nav-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: white;
            border-top: 1px solid #dee2e6;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .nav-bottom .nav-link {
            padding: 12px 0;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .nav-bottom .nav-link.active {
            color: var(--accent-color);
        }

        .nav-bottom .nav-link i {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .method-card {
            border: 2px solid #dee2e6;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            width: 140px;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #fff;
            margin-bottom: 10px;
        }

        .method-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .method-card.active {
            border-color: var(--primary-color);
            background-color: #f8f5ff;
        }

        .method-card i {
            font-size: 1.75rem;
            margin-bottom: 8px;
            color: var(--primary-color);
        }

        .checkout-summary {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }


        .discount-badge {
            background-color: #e6ffee;
            color: #28a745;
            font-size: 0.8rem;
        }

        .bank-details-box {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
        }

        .bank-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .copy-btn {
            background-color: #e9ecef;
            border: none;
            padding: 4px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }

        .copy-btn:hover {
            background-color: #d6d6d6;
        }

        #previewBox {
            max-width: 200px;
            margin-top: 10px;
        }

        #previewBox img {
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .bank-details-box {
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 15px;
            background-color: #f8f9fa;
        }

        .bank-item {
            margin-bottom: 10px;
        }


        .bank-details-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
        }

        .bank-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .bank-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .copy-btn {
            font-size: 0.8rem;
            padding: 4px 8px;
        }

        #previewBox img {
            max-width: 200px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .method-card {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .bank-item button {
                font-size: 0.75rem;
                /* Smaller text */
                padding: 0.25rem 0.5rem;
                /* Smaller button size */
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/nav.php'; ?>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Checkout</h2>
            <a href="cart.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Cart
            </a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($discount_message)): ?>
            <div class="alert alert-success d-flex justify-content-between align-items-center">
                <span><?= htmlspecialchars($discount_message) ?></span>
                <form method="post" class="d-inline">
                    <input type="hidden" name="remove_promo" value="1">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <?php if (empty($cart_items)): ?>
                    <div class="alert alert-info">
                        Your cart is empty. <a href="products.php" class="alert-link">Continue shopping</a>
                    </div>
                <?php else: ?>
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($cart_items as $item): ?>
                                    <li class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-3 col-md-2">
                                                <img src="../<?= htmlspecialchars($item['image_url'] ?? "../uploads/default-product.png") ?>"
                                                    class="img-fluid product-img"
                                                    alt="<?= htmlspecialchars($item['name']) ?>">
                                            </div>
                                            <div class="col-6 col-md-7">
                                                <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                                <small class="text-muted">₦<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?></small>
                                            </div>
                                            <div class="col-3 text-end">
                                                <span class="fw-bold">₦<?= number_format($item['item_total'], 2) ?></span>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- UNIFIED CHECKOUT FORM -->
                    <form method="POST" id="paymentForm" enctype="multipart/form-data">

                        <!-- DELIVERY METHOD SECTION -->
                        <div class="card mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Shipping Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Delivery Method <span class="text-danger">*</span></label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <div class="form-check method-card">
                                            <input class="form-check-input" type="radio" name="delivery_method"
                                                id="pickup_station" value="pickup_station" required checked>
                                            <label class="form-check-label" for="pickup_station">
                                                <i class="bi bi-geo-alt"></i>
                                                <p class="mb-0">Pickup Station</p>
                                                <small class="text-muted">Free</small>
                                            </label>
                                        </div>
                                        <div class="form-check method-card">
                                            <input class="form-check-input" type="radio" name="delivery_method"
                                                id="home_delivery" value="home_delivery" required>
                                            <label class="form-check-label" for="home_delivery">
                                                <i class="bi bi-house-door"></i>
                                                <p class="mb-0">Home Delivery</p>
                                                <small class="text-muted">₦500</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3" id="shippingAddressField" style="display: none;">
                                    <label for="shipping_address" class="form-label">Shipping Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="shipping_address" name="shipping_address"
                                        rows="3" placeholder="Enter your full address"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- PAYMENT METHOD SECTION -->
                        <div class="card mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Payment Method <span class="text-danger">*</span></h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-3 mb-3" id="paymentMethods">
                                    <div class="form-check method-card" data-method="pay_on_delivery">
                                        <input class="form-check-input d-none" type="radio" name="payment_method" id="pay_on_delivery" value="pay_on_delivery">
                                        <label class="form-check-label" for="pay_on_delivery">
                                            <i class="bi bi-truck"></i>
                                            <p class="mb-0">Pay on Delivery</p>
                                            <small class="text-muted">Cash or POS</small>
                                        </label>
                                    </div>
                                    <div class="form-check method-card" data-method="pay_with_card">
                                        <input class="form-check-input d-none" type="radio" name="payment_method" id="pay_with_card" value="pay_with_card">
                                        <label class="form-check-label" for="pay_with_card">
                                            <i class="bi bi-credit-card"></i>
                                            <p class="mb-0">Card Payment</p>
                                            <small class="text-muted">Secure online</small>
                                        </label>
                                    </div>
                                    <div class="form-check method-card" data-method="pay_with_transfer">
                                        <input class="form-check-input d-none" type="radio" name="payment_method" id="pay_with_transfer" value="pay_with_transfer" checked>
                                        <label class="form-check-label" for="pay_with_transfer">
                                            <i class="bi bi-bank"></i>
                                            <p class="mb-0">Bank Transfer</p>
                                            <small class="text-muted">Manual verification</small>
                                        </label>
                                    </div>
                                </div>

                                <!-- BANK TRANSFER INFO -->
                                <div id="paymentTransferInfo">
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>Note:</strong> Payment will be confirmed before shipping.
                                    </div>
                                    <div class="bank-details-box mt-3">
                                        <div class="bank-item"><strong>Bank Name:</strong> <span>Access Bank</span></div>
                                        <div class="bank-item d-flex flex-wrap align-items-center gap-2">
                                            <strong>Account Number:</strong>
                                            <span id="acctNumber">1234567890</span>
                                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" onclick="copyToClipboard('1234567890')">
                                                <i class="bi bi-clipboard"></i> Copy
                                            </button>
                                        </div>
                                        <div class="bank-item"><strong>Account Name:</strong> Your Business Name</div>
                                    </div>

                                    <hr class="my-3">
                                    <div class="mb-3">
                                        <label for="payment_proof" class="form-label"><i class="bi bi-cloud-upload me-2"></i>Upload Proof</label>
                                        <input type="file" class="form-control" id="payment_proof" name="payment_proof" accept="image/*,.pdf">
                                        <div class="form-text">Accepted: JPG, PNG, PDF (Max 5MB)</div>
                                        <div id="previewBox" class="mt-2"></div>
                                        <button type="button" class="btn btn-primary mt-2" id="uploadBtn">
                                            <i class="bi bi-upload me-2"></i>Upload Payment Proof
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PROMO CODE SECTION -->
                        <div class="card mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Promo Code</h5>
                            </div>
                            <div class="card-body">
                                <div class="input-group">
                                    <input type="text" name="promo_code_input" id="promo_code_input"
                                        class="form-control" placeholder="Enter promo code">
                                    <button type="button" class="btn btn-outline-primary" id="applyPromoBtn">
                                        Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <!-- PLACE ORDER BUTTON -->
                        <button type="submit" name="place_order" class="btn btn-success btn-lg w-100" id="placeOrderBtn">
                            <i class="bi bi-check-circle me-2"></i>Place Order
                        </button>
                        <hr>
                    </form>

                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="checkout-summary" style="position: sticky; top: 30px;">
                    <h5 class="mb-3">Order Summary</h5>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>₦<?= number_format($subtotal, 2) ?></span>
                    </div>

                    <?php if ($discount_amount > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Discount:</span>
                            <span class="text-danger">-₦<?= number_format($discount_amount, 2) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Delivery:</span>
                        <span id="deliveryFee">₦0.00</span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between fw-bold fs-5 mb-4">
                        <span>Total:</span>
                        <span id="orderTotal">₦<?= number_format($total, 2) ?></span>
                    </div>

                    <div class="mt-3 text-center text-muted small">
                        By placing your order, you agree to our <a href="#">Terms of Service</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    <!-- Bottom Navigation -->
    <?php include 'includes/bottomNav.php'; ?>

    <!-- Script -->
    <?php include 'includes/script.php'; ?>

    <!-- AJAX Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const transferInfo = document.getElementById('paymentTransferInfo');
            const paymentMethods = document.getElementById('paymentMethods');
            const proofUploaded = {
                status: false
            };
            const previewBox = document.getElementById('previewBox');
            const fileInput = document.getElementById('payment_proof');
            const uploadBtn = document.getElementById('uploadBtn');

            // Click on card to toggle active and update visibility
            paymentMethods.addEventListener('click', function(e) {
                const target = e.target.closest('.method-card');
                if (!target) return;

                document.querySelectorAll('.method-card').forEach(card => card.classList.remove('border-primary'));
                target.classList.add('border-primary');

                const selectedMethod = target.getAttribute('data-method');
                const radio = target.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;

                if (selectedMethod === 'pay_with_transfer') {
                    transferInfo.style.display = 'block';
                } else {
                    transferInfo.style.display = 'none';
                }
            });

            // Default: show transfer info
            transferInfo.style.display = 'block';
            document.querySelector('.method-card[data-method="pay_with_transfer"]').classList.add('border-primary');

            // Clipboard copy
            window.copyToClipboard = function(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(() => {
                        showAlert('Copied to clipboard!', 'success');
                    });
                } else {
                    const temp = document.createElement('textarea');
                    temp.value = text;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                    showAlert('Copied to clipboard!', 'success');
                }
            };

            function showAlert(msg, type = 'info') {
                const alert = document.createElement('div');
                alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
                alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                alert.innerHTML = `${msg}<button class="btn-close" data-bs-dismiss="alert"></button>`;
                document.body.appendChild(alert);
                setTimeout(() => alert.remove(), 3000);
            }

            // File preview
            if (fileInput && uploadBtn && previewBox) {
                fileInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (!file) return previewBox.innerHTML = '';

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (file.type.startsWith('image/')) {
                            previewBox.innerHTML = `
                        <p><strong>Preview:</strong></p>
                        <img src="${e.target.result}" style="max-width: 200px; border: 1px solid #ccc; border-radius: 4px;">
                        <p class="mt-2 text-muted">File: ${file.name}</p>`;
                        } else {
                            previewBox.innerHTML = `<div class="alert alert-info">PDF selected: ${file.name}</div>`;
                        }
                    };
                    reader.readAsDataURL(file);
                });

                // STEP 1: Upload proof via AJAX
                uploadBtn.addEventListener('click', function() {
                    if (proofUploaded.status) return showAlert("You've already uploaded proof.", 'warning');

                    const confirmUpload = confirm("Are you sure you want to submit this purchase proof? This action can't be reversed.");
                    if (!confirmUpload) return;

                    const file = fileInput.files[0];
                    if (!file) return showAlert('Select a file first.', 'warning');
                    if (file.size > 5 * 1024 * 1024) return showAlert('File too large (max 5MB)', 'danger');

                    uploadBtn.disabled = true;
                    uploadBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Uploading...';

                    const formData = new FormData();
                    formData.append('payment_proof', file);

                    // Send to same page (checkout.php)
                    fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                proofUploaded.status = true;
                                fileInput.disabled = true;
                                uploadBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Uploaded Successfully';
                                uploadBtn.classList.remove('btn-primary');
                                uploadBtn.classList.add('btn-success');
                                showAlert('Proof uploaded successfully! You can now place your order.', 'success');
                            } else {
                                throw new Error(data.message || 'Upload failed');
                            }
                        })
                        .catch(error => {
                            uploadBtn.disabled = false;
                            uploadBtn.innerHTML = '<i class="bi bi-upload me-2"></i>Upload Payment Proof';
                            showAlert('Upload failed: ' + error.message, 'danger');
                        });
                });
            }

            // STEP 2: Prevent form submit without proof if bank transfer selected
            const form = document.getElementById('paymentForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const selected = document.querySelector('input[name="payment_method"]:checked');
                    if (selected && selected.value === 'pay_with_transfer' && !proofUploaded.status) {
                        e.preventDefault();
                        showAlert('Upload your payment proof first.', 'warning');
                        return false;
                    }

                    // Validate delivery method
                    const deliverySelected = document.querySelector('input[name="delivery_method"]:checked');
                    if (!deliverySelected) {
                        e.preventDefault();
                        showAlert('Please select a delivery method.', 'warning');
                        return false;
                    }

                    // Validate shipping address if home delivery is selected
                    if (deliverySelected.value === 'home_delivery') {
                        const shippingAddress = document.getElementById('shipping_address');
                        if (!shippingAddress.value.trim()) {
                            e.preventDefault();
                            showAlert('Please enter your shipping address.', 'warning');
                            return false;
                        }
                    }
                });
            }

            // Handle delivery method change and fee calculation
            const deliveryMethods = document.querySelectorAll('input[name="delivery_method"]');
            const shippingAddressField = document.getElementById('shippingAddressField');
            const deliveryFeeSpan = document.getElementById('deliveryFee');
            const orderTotalSpan = document.getElementById('orderTotal');
            const baseTotal = <?= $total ?>; // PHP total without delivery

            deliveryMethods.forEach(method => {
                method.addEventListener('change', function() {
                    if (this.value === 'home_delivery') {
                        shippingAddressField.style.display = 'block';
                        deliveryFeeSpan.textContent = '₦500.00';
                        orderTotalSpan.textContent = '₦' + (baseTotal + 500).toLocaleString('en-US', {
                            minimumFractionDigits: 2
                        });
                    } else {
                        shippingAddressField.style.display = 'none';
                        deliveryFeeSpan.textContent = '₦0.00';
                        orderTotalSpan.textContent = '₦' + baseTotal.toLocaleString('en-US', {
                            minimumFractionDigits: 2
                        });
                    }
                });
            });

            // Handle promo code application
            const applyPromoBtn = document.getElementById('applyPromoBtn');
            if (applyPromoBtn) {
                applyPromoBtn.addEventListener('click', function() {
                    const promoInput = document.getElementById('promo_code_input');
                    const promoCode = promoInput.value.trim();

                    if (!promoCode) {
                        showAlert('Please enter a promo code.', 'warning');
                        return;
                    }

                    // Create a temporary form for promo code submission
                    const promoForm = document.createElement('form');
                    promoForm.method = 'POST';
                    promoForm.style.display = 'none';

                    const promoInput2 = document.createElement('input');
                    promoInput2.type = 'hidden';
                    promoInput2.name = 'promo_code';
                    promoInput2.value = promoCode;

                    const applyInput = document.createElement('input');
                    applyInput.type = 'hidden';
                    applyInput.name = 'apply_promo';
                    applyInput.value = '1';

                    promoForm.appendChild(promoInput2);
                    promoForm.appendChild(applyInput);
                    document.body.appendChild(promoForm);
                    promoForm.submit();
                });
            }
        });
    </script>

</body>

</html>