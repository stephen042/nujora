<?php
require 'app/config.php';

// Redirect if user is already logged in, but cart is empty
if (!isset($_SESSION['user_id']) && empty($_SESSION['pending_checkout'])) {
    header("Location: checkout.php");
    exit;
}

// Fetch states for the state dropdown
$stmt = $pdo->query("SELECT id, name FROM states ORDER BY name ASC");
$states = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle guest checkout info (auto-account creation)
if (!isset($_SESSION['user_id'])) {
    $guest_email = $_SESSION['pending_checkout']['email'] ?? null;
    if ($guest_email) {
        $stmt = $pdo->prepare("SELECT id FROM buyers WHERE email = ?");
        $stmt->execute([$guest_email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            header("Location: login.php?redirect=checkout");
            exit;
        } else {
            $password = password_hash(rand(100000, 999999), PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO buyers (full_name, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                $_SESSION['pending_checkout']['full_name'],
                $guest_email,
                $password
            ]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
        }
    }
}

$buyer_id = $_SESSION['user_id'];

// Fetch cart items with delivery and COD info
$stmt = $pdo->prepare("
    SELECT 
        ci.id AS cart_id,
        ci.product_id,
        ci.quantity,
        ci.variant_id,
        ci.variant_options,
        p.name,
        p.price,
        p.image_url,
        p.seller_id,
        p.free_delivery,
        p.pay_on_delivery,
        (p.price * ci.quantity) AS item_total
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.buyer_id = ?
");
$stmt->execute([$buyer_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal and delivery logic
$subtotal = 0;
$delivery_fee = 1200; // Static delivery fee
$has_non_free_delivery = false;
$all_items_support_cod = true;

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];

    // Check if any item doesn't have free delivery (free_delivery == 1 means NOT free, 2 means FREE)
    if ($item['free_delivery'] == 1) {
        $has_non_free_delivery = true;
    }

    // Check if all items support COD (pay_on_delivery == 1 means NOT COD, 2 means COD)
    if ($item['pay_on_delivery'] == 1) {
        $all_items_support_cod = false;
    }
}

// Apply delivery fee only if there are items without free delivery
if (!$has_non_free_delivery) {
    $delivery_fee = 0;
}

// Apply promo code if exists
$discount_amount = 0;
$applied_promo = $_SESSION['applied_coupon']['code'] ?? null;
if ($applied_promo) {
    $stmt = $pdo->prepare("SELECT discount_type, discount_value FROM coupons WHERE code = ? AND status='active' AND expiry_date > NOW()");
    $stmt->execute([$applied_promo]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($coupon) {
        if ($coupon['discount_type'] === 'percentage') {
            $discount_amount = ($coupon['discount_value'] / 100) * $subtotal;
        } else {
            $discount_amount = min($coupon['discount_value'], $subtotal);
        }
    }
}

$total = max($subtotal - $discount_amount + $delivery_fee, 0);

// Fetch bank details
$stmt = $pdo->query("SELECT account_name, account_number, bank_name FROM bank_details LIMIT 1");
$bank_details = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
$success = '';
$proof_uploaded = false;

// Handle proof of payment upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    try {
        $file = $_FILES['payment_proof'];
        if ($file['error'] !== UPLOAD_ERR_OK) throw new Exception('File upload error');
        if ($file['size'] > 5 * 1024 * 1024) throw new Exception('File too large (max 5MB)');
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
        if (!in_array($file['type'], $allowed_types)) throw new Exception('Invalid file type');

        $upload_dir = 'uploads/payment_proofs/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $unique_name = 'proof_' . uniqid() . '_' . time() . '.' . $ext;
        $path = $upload_dir . $unique_name;

        if (!move_uploaded_file($file['tmp_name'], $path)) throw new Exception('Failed to save file');

        $txn_ref = 'TXN_' . strtoupper(uniqid()) . '_' . time();
        $stmt = $pdo->prepare("INSERT INTO proof_of_payment (transaction_reference, proof_path, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$txn_ref, $path]);
        $_SESSION['payment_proof_reference'] = $txn_ref;

        $success = 'Payment proof uploaded successfully!';
        $proof_uploaded = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $payment_method = $_POST['payment_method'] ?? '';
    $country = $_POST['country'] ?? '';
    $state = $_POST['state'] ?? '';
    $lga = $_POST['lga'] ?? '';
    $address = $_POST['address'] ?? '';
    $delivery_method = $_POST['delivery_method'] ?? 'pickup_station';

    if ($delivery_method ==  'home') {
        // Concatenate address fields into one string
        $address = trim($address);
        $full_address = trim($country . ', ' . $state . ', ' . $lga . ', ' . $address);

        // Optionally, you can save $full_address to the database or use it as needed
        $pickup_address = $full_address;
    } else {
        $pickup_address  = 'Suite 45 Maikassu Plaza, Hajj Camp, Kano';
    }


    // Validation based on payment method
    if ($payment_method === 'bank_transfer' && !isset($_SESSION['payment_proof_reference'])) {
        $error = "Please upload payment proof before placing order.";
    } elseif ($payment_method === 'cod' && !$all_items_support_cod) {
        $error = "Cash on Delivery is not available for some items in your cart.";
    } elseif (empty($cart_items)) {
        $error = "Your cart is empty.";
    } elseif (!in_array($payment_method, ['bank_transfer', 'card', 'cod'])) {
        $error = "Invalid payment method selected.";
    } else {
        try {
            $pdo->beginTransaction();

            $txn_ref = $payment_method === 'bank_transfer' ? $_SESSION['payment_proof_reference'] : 'TXN_' . strtoupper(uniqid()) . '_' . time();

            // Insert order
            // $stmt = $pdo->prepare("INSERT INTO orders (buyer_id, transaction_reference, subtotal, discount, total, payment_method, shipping_address, delivery_method ,status, created_at)
            //                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            // $stmt->execute([$buyer_id, $txn_ref, $subtotal, $discount_amount, $total, $payment_method, $pickup_address]);
            // $order_id = $pdo->lastInsertId();
            // Insert order
            $stmt = $pdo->prepare("INSERT INTO orders (buyer_id, transaction_reference, subtotal, discount, total, payment_method, shipping_address, delivery_method, status, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");

            $stmt->execute([
                $buyer_id,
                $txn_ref,
                $subtotal,
                $discount_amount,
                $total,
                $payment_method,
                $pickup_address,
                $delivery_method
            ]);
            $order_id = $pdo->lastInsertId();

            // Insert order items
            $stmt = $pdo->prepare("
                INSERT INTO order_items 
                (order_id, product_id, variant_id, variant_options, price, quantity, seller_id, image_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($cart_items as $item) {
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['variant_id'] ?: null,
                    $item['variant_options'] ?: null,
                    $item['price'],
                    $item['quantity'],
                    $item['seller_id'],
                    $item['image_url']
                ]);
            }


            // Insert transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (payment_method, amount, status, transaction_reference, created_at, updated_at)
                                   VALUES (?, ?, 'pending', ?, NOW(), NOW())");
            $stmt->execute([$payment_method, $total, $txn_ref]);

            // Clear cart & session
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE buyer_id = ?");
            $stmt->execute([$buyer_id]);
            unset($_SESSION['payment_proof_reference'], $_SESSION['applied_promo']);

            $pdo->commit();
            header("Location: order-confirmation.php?order_id=$order_id");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to place order: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order | <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="uploads/default-product.png">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #f57c00;
            /* Bright Orange */
            --secondary-color: #ef6c00;
            /* Deep Orange */
            --accent-color: #ffb74d;
            /* Soft Yellow-Orange */
            --light-bg: #fff8f0;
            /* Warm Light Background */
            --dark-text: #1e1e1e;
            /* Darker Text for Better Contrast */
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Poppins', sans-serif;
        }

        /* ================= BUTTONS ================= */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #e65100;
            border-color: #e65100;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .checkout-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: #fff;
            overflow-x: auto;
            display: block;
        }

        .cart-table th,
        .cart-table td {
            border: 1px solid #dee2e6;
            padding: 12px 10px;
            text-align: left;
        }

        .cart-table th {
            background: #f1f3f5;
            font-weight: 600;
        }

        .order-summary {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .order-summary p {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .order-summary .total-row {
            font-size: 1.2rem;
            font-weight: 700;
            padding-top: 10px;
            border-top: 2px solid #dee2e6;
            margin-top: 10px;
        }

        .payment-section {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .payment-method-option {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method-option:hover {
            border-color: #007bff;
            background: #f8f9ff;
        }

        .payment-method-option input[type="radio"] {
            margin-right: 12px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .payment-method-option label {
            cursor: pointer;
            margin: 0;
            flex: 1;
            font-weight: 500;
        }

        .payment-details {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .payment-details.active {
            display: block;
        }

        .bank-info {
            background: #fff;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
        }

        .bank-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px 0;
        }

        .bank-info-row:last-child {
            margin-bottom: 0;
        }

        .bank-info-label {
            font-weight: 600;
            color: #495057;
        }

        .bank-info-value {
            font-weight: 500;
            color: #212529;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .copy-btn {
            padding: 4px 12px;
            font-size: 0.85rem;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .copy-btn:hover {
            background: #0056b3;
        }

        .copy-btn.copied {
            background: #28a745;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 1rem;
        }

        .card-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .file-upload-wrapper {
            position: relative;
        }

        .file-preview {
            margin-top: 15px;
            display: none;
        }

        .file-preview.active {
            display: block;
        }

        .file-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }

        .file-preview-info {
            margin-top: 10px;
            padding: 10px;
            background: #e9ecef;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .btn-primary {
            background: #007bff;
            color: #fff;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-success {
            background: #28a745;
            color: #fff;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-lg {
            padding: 15px 30px;
            font-size: 1.1rem;
            width: 100%;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .cod-notice {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            color: #856404;
        }

        .cod-notice.disabled {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .bank-input {
            width: 400px;
            /* wider than before */
            max-width: 450px;
            /* prevents stretching too wide */
            padding: 6px;
            font-size: 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
        }


        .copy-btn {
            margin-left: 8px;
        }


        @media (max-width: 768px) {
            .checkout-container {
                margin: 15px auto;
            }

            .cart-table {
                font-size: 0.9rem;
            }

            .cart-table th,
            .cart-table td {
                padding: 8px 6px;
            }

            .payment-section {
                padding: 15px;
            }

            .card-inputs {
                grid-template-columns: 1fr;
            }

            .bank-info-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .bank-info-value {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .order-summary p {
                font-size: 0.95rem;
            }

            .btn-lg {
                padding: 12px 20px;
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/nav.php'; ?>

    <div class="checkout-container">
        <h2>Complete order Process</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['auto_account_info'])):
            $auto_info = $_SESSION['auto_account_info'];
            $email = $auto_info['email'];
            $autoPass = $auto_info['password'];
        ?>
            <div class="alert alert-info">
                <strong>Your account has been created! Your account details will be emailed to you shortly</strong><br>
                Email: <strong><?= htmlspecialchars($email) ?></strong><br>
                Temporary Password: <strong><?= htmlspecialchars($autoPass) ?></strong><br>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="alert alert-info">Your cart is empty. <a href="products.php">Continue shopping</a></div>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Image</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($item['name']) ?>

                                <!-- VARIANT DISPLAY -->
                                <?php if (!empty($item['variant_options'])): ?>
                                    <?php
                                    // Convert JSON string → array
                                    $opts = is_string($item['variant_options'])
                                        ? json_decode($item['variant_options'], true)
                                        : $item['variant_options'];
                                    ?>

                                    <?php if (!empty($opts)): ?>
                                        <div class="mt-1 text-muted small">
                                            <?php foreach ($opts as $key => $value): ?>
                                                <div>
                                                    <?= ucfirst($key) ?>:
                                                    <strong><?= htmlspecialchars($value) ?></strong>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <td><?= $item['quantity'] ?></td>

                            <td>₦<?= number_format($item['price'], 2) ?></td>

                            <td>
                                <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                    alt="<?= htmlspecialchars($item['name']) ?>"
                                    style="max-width: 80px; max-height: 80px; border-radius: 6px;">
                            </td>

                            <td>₦<?= number_format($item['item_total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="order-summary">
                <h4>Order Summary</h4>
                <p><span>Subtotal:</span> <span>₦<?= number_format($subtotal, 2) ?></span></p>
                <?php if ($discount_amount > 0): ?>
                    <p><span>Discount:</span> <span class="text-danger">-₦<?= number_format($discount_amount, 2) ?></span></p>
                <?php endif; ?>
                <p><span>Delivery Fee:</span> <span><?= $delivery_fee > 0 ? '₦' . number_format($delivery_fee, 2) : 'FREE' ?></span></p>
                <p class="total-row"><span>Total:</span> <span>₦<?= number_format($total, 2) ?></span></p>
            </div>

            <div class="order-summary">
                <h4>Delivery Method</h4>

                <div class="mb-3">
                    <label class="form-check">
                        <input type="radio" name="delivery_method" value="home" class="form-check-input" checked>
                        <span class="form-check-label">Deliver to My Address</span>
                    </label>

                    <label class="form-check mt-2">
                        <input type="radio" name="delivery_method" value="pickup_station" class="form-check-input">
                        <span class="form-check-label">Pick-up Station</span>
                    </label>
                </div>
                <hr>
                <!-- Pickup Station Box -->
                <div id="pickup_box" class="alert alert-info" style="display:none;">
                    <strong>Pickup Station Address:</strong><br>
                    Suite 45 Maikassu Plaza, Hajj Camp, Kano
                </div>

                <!-- Address Box -->
                <div id="address_box">
                    <?php
                    // Fetch buyer's shipping address details
                    $stmt = $pdo->prepare("
                                SELECT 
                                    u.address,
                                    u.country,
                                    u.state_id,
                                    u.lga_id,
                                    s.name AS state,
                                    lg.name AS lga
                                FROM users u
                                LEFT JOIN states s ON u.state_id = s.id
                                LEFT JOIN local_governments lg ON u.lga_id = lg.id
                                WHERE u.id = ?
                            ");
                    $stmt->execute([$buyer_id]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    ?>
                    <h4>Shipping Address</h4>
                    <p><strong><span>Edit the address below if you wish to deliver to a different address.</span></strong> </p>
                    <form>
                        <!-- Country Select with Phone Code -->
                        <div class="mb-3">
                            <label for="country" class="form-label">Country</label>
                            <select id="country" name="country" class="form-select" required>
                                <option value="" data-code="">-- Select Country --</option>
                                <option value="Afghanistan" data-code="+93" <?= ($user['country'] ?? '') === 'Afghanistan' ? 'selected' : '' ?>>Afghanistan (+93)</option>
                                <option value="Albania" data-code="+355" <?= ($user['country'] ?? '') === 'Albania' ? 'selected' : '' ?>>Albania (+355)</option>
                                <option value="Algeria" data-code="+213" <?= ($user['country'] ?? '') === 'Algeria' ? 'selected' : '' ?>>Algeria (+213)</option>
                                <option value="Andorra" data-code="+376" <?= ($user['country'] ?? '') === 'Andorra' ? 'selected' : '' ?>>Andorra (+376)</option>
                                <option value="Angola" data-code="+244" <?= ($user['country'] ?? '') === 'Angola' ? 'selected' : '' ?>>Angola (+244)</option>
                                <option value="Argentina" data-code="+54" <?= ($user['country'] ?? '') === 'Argentina' ? 'selected' : '' ?>>Argentina (+54)</option>
                                <option value="Armenia" data-code="+374" <?= ($user['country'] ?? '') === 'Armenia' ? 'selected' : '' ?>>Armenia (+374)</option>
                                <option value="Australia" data-code="+61" <?= ($user['country'] ?? '') === 'Australia' ? 'selected' : '' ?>>Australia (+61)</option>
                                <option value="Austria" data-code="+43" <?= ($user['country'] ?? '') === 'Austria' ? 'selected' : '' ?>>Austria (+43)</option>
                                <option value="Bangladesh" data-code="+880" <?= ($user['country'] ?? '') === 'Bangladesh' ? 'selected' : '' ?>>Bangladesh (+880)</option>
                                <option value="Belgium" data-code="+32" <?= ($user['country'] ?? '') === 'Belgium' ? 'selected' : '' ?>>Belgium (+32)</option>
                                <option value="Brazil" data-code="+55" <?= ($user['country'] ?? '') === 'Brazil' ? 'selected' : '' ?>>Brazil (+55)</option>
                                <option value="Canada" data-code="+1" <?= ($user['country'] ?? '') === 'Canada' ? 'selected' : '' ?>>Canada (+1)</option>
                                <option value="China" data-code="+86" <?= ($user['country'] ?? '') === 'China' ? 'selected' : '' ?>>China (+86)</option>
                                <option value="Denmark" data-code="+45" <?= ($user['country'] ?? '') === 'Denmark' ? 'selected' : '' ?>>Denmark (+45)</option>
                                <option value="Egypt" data-code="+20" <?= ($user['country'] ?? '') === 'Egypt' ? 'selected' : '' ?>>Egypt (+20)</option>
                                <option value="France" data-code="+33" <?= ($user['country'] ?? '') === 'France' ? 'selected' : '' ?>>France (+33)</option>
                                <option value="Germany" data-code="+49" <?= ($user['country'] ?? '') === 'Germany' ? 'selected' : '' ?>>Germany (+49)</option>
                                <option value="Ghana" data-code="+233" <?= ($user['country'] ?? '') === 'Ghana' ? 'selected' : '' ?>>Ghana (+233)</option>
                                <option value="India" data-code="+91" <?= ($user['country'] ?? '') === 'India' ? 'selected' : '' ?>>India (+91)</option>
                                <option value="Italy" data-code="+39" <?= ($user['country'] ?? '') === 'Italy' ? 'selected' : '' ?>>Italy (+39)</option>
                                <option value="Japan" data-code="+81" <?= ($user['country'] ?? '') === 'Japan' ? 'selected' : '' ?>>Japan (+81)</option>
                                <option value="Kenya" data-code="+254" <?= ($user['country'] ?? '') === 'Kenya' ? 'selected' : '' ?>>Kenya (+254)</option>
                                <option value="Mexico" data-code="+52" <?= ($user['country'] ?? '') === 'Mexico' ? 'selected' : '' ?>>Mexico (+52)</option>
                                <option value="Netherlands" data-code="+31" <?= ($user['country'] ?? '') === 'Netherlands' ? 'selected' : '' ?>>Netherlands (+31)</option>
                                <option value="Nigeria" data-code="+234" <?= ($user['country'] ?? '') === 'Nigeria' ? 'selected' : '' ?>>Nigeria (+234)</option>
                                <option value="Norway" data-code="+47" <?= ($user['country'] ?? '') === 'Norway' ? 'selected' : '' ?>>Norway (+47)</option>
                                <option value="Pakistan" data-code="+92" <?= ($user['country'] ?? '') === 'Pakistan' ? 'selected' : '' ?>>Pakistan (+92)</option>
                                <option value="Russia" data-code="+7" <?= ($user['country'] ?? '') === 'Russia' ? 'selected' : '' ?>>Russia (+7)</option>
                                <option value="Saudi Arabia" data-code="+966" <?= ($user['country'] ?? '') === 'Saudi Arabia' ? 'selected' : '' ?>>Saudi Arabia (+966)</option>
                                <option value="South Africa" data-code="+27" <?= ($user['country'] ?? '') === 'South Africa' ? 'selected' : '' ?>>South Africa (+27)</option>
                                <option value="Spain" data-code="+34" <?= ($user['country'] ?? '') === 'Spain' ? 'selected' : '' ?>>Spain (+34)</option>
                                <option value="Sweden" data-code="+46" <?= ($user['country'] ?? '') === 'Sweden' ? 'selected' : '' ?>>Sweden (+46)</option>
                                <option value="Turkey" data-code="+90" <?= ($user['country'] ?? '') === 'Turkey' ? 'selected' : '' ?>>Turkey (+90)</option>
                                <option value="Uganda" data-code="+256" <?= ($user['country'] ?? '') === 'Uganda' ? 'selected' : '' ?>>Uganda (+256)</option>
                                <option value="United Arab Emirates" data-code="+971" <?= ($user['country'] ?? '') === 'United Arab Emirates' ? 'selected' : '' ?>>United Arab Emirates (+971)</option>
                                <option value="United Kingdom" data-code="+44" <?= ($user['country'] ?? '') === 'United Kingdom' ? 'selected' : '' ?>>United Kingdom (+44)</option>
                                <option value="United States" data-code="+1" <?= ($user['country'] ?? '') === 'United States' ? 'selected' : '' ?>>United States (+1)</option>
                                <option value="Zimbabwe" data-code="+263" <?= ($user['country'] ?? '') === 'Zimbabwe' ? 'selected' : '' ?>>Zimbabwe (+263)</option>
                            </select>
                        </div>

                        <div class="mb-3 toggle-address">
                            <label>State * (Nigeria Only)</label>
                            <select class="form-control" id="state_select" name="state_id" required>
                                <option value="">Select State</option>

                                <?php foreach ($states as $s): ?>
                                    <option value="<?= $s['id'] ?>"
                                        <?= ($user['state_id'] == $s['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3 toggle-address">
                            <label>LGA * (Nigeria Only)</label>
                            <select class="form-control" id="lga_select" name="lga_id" required>
                                <option value="">Select LGA</option>

                                <?php if (!empty($user['lga_id'])): ?>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT id, name FROM local_governments WHERE state_id = ?");
                                    $stmt->execute([$user['state_id']]);
                                    $lgas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    ?>

                                    <?php foreach ($lgas as $lga): ?>
                                        <option value="<?= $lga['id'] ?>"
                                            <?= ($user['lga_id'] == $lga['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($lga['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                    </form>
                </div>

            </div>

            <div class="payment-section">
                <h4>Payment Method</h4>
                <form method="POST" enctype="multipart/form-data" id="checkoutForm">

                    <!-- Card Payment -->
                    <div class="payment-method-option d-none" onclick="selectPayment('card')">
                        <input type="radio" name="payment_method" value="card" id="card_payment">
                        <label for="card_payment">
                            <strong>Card Payment</strong>
                            <div style="font-size: 0.9rem; color: #6c757d;">Pay securely with your debit/credit card</div>
                        </label>
                    </div>
                    <div class="payment-details mb-3" id="card_details">
                        <div class="form-group">
                            <label>Card Number</label>
                            <input type="text" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19" id="card_number">
                        </div>
                        <div class="card-inputs">
                            <div class="form-group">
                                <label>Expiry Date</label>
                                <input type="text" class="form-control" placeholder="MM/YY" maxlength="5" id="card_expiry">
                            </div>
                            <div class="form-group">
                                <label>CVV</label>
                                <input type="text" class="form-control" placeholder="123" maxlength="3" id="card_cvv">
                            </div>
                        </div>
                        <div class="alert alert-info" style="margin-top: 15px;">
                            <strong>Note:</strong> Card integration coming soon. This is for display only.
                        </div>
                    </div>

                    <!-- Bank Transfer -->
                    <div class="payment-method-option" onclick="selectPayment('bank')">
                        <input type="radio" name="payment_method" value="bank_transfer" id="bank_payment">
                        <label for="bank_payment">
                            <strong>Bank Transfer</strong>
                            <div style="font-size: 0.9rem; color: #6c757d;">Transfer to our bank account</div>
                        </label>
                    </div>
                    <div class="payment-details mb-3" id="bank_details">
                        <?php if ($bank_details): ?>
                            <div class="bank-info">
                                <h5 style="margin-bottom: 15px;">Transfer to this account:</h5>

                                <div class="bank-info-row">
                                    <span class="bank-info-label">Bank Name:</span>
                                    <span class="bank-info-value">
                                        <input class="bank-input" type="text"
                                            value="<?= htmlspecialchars($bank_details['bank_name']) ?>" readonly>
                                        <button type="button" class="copy-btn" onclick="copyInput(this)">Copy</button>
                                    </span>
                                </div>

                                <div class="bank-info-row">
                                    <span class="bank-info-label">Account Name:</span>
                                    <span class="bank-info-value">
                                        <input class="bank-input" type="text"
                                            value="<?= htmlspecialchars($bank_details['account_name']) ?>" readonly>
                                        <button type="button" class="copy-btn" onclick="copyInput(this)">Copy</button>
                                    </span>
                                </div>

                                <div class="bank-info-row">
                                    <span class="bank-info-label">Account Number:</span>
                                    <span class="bank-info-value">
                                        <input class="bank-input" type="text"
                                            value="<?= htmlspecialchars($bank_details['account_number']) ?>" readonly>
                                        <button type="button" class="copy-btn" onclick="copyInput(this)">Copy</button>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>


                        <div class="form-group">
                            <label>Upload Proof of Payment (JPG, PNG, PDF - Max 5MB)</label>
                            <input type="file" name="payment_proof" class="form-control" id="payment_proof" accept="image/jpeg,image/png,image/jpg,application/pdf">
                        </div>

                        <div class="file-preview" id="file_preview">
                            <img id="preview_image" src="" alt="Payment Proof Preview">
                            <div class="file-preview-info">
                                <strong>File:</strong> <span id="file_name"></span><br>
                                <strong>Size:</strong> <span id="file_size"></span>
                            </div>
                        </div>

                        <?php if (!$proof_uploaded): ?>
                            <button type="button" class="btn btn-primary" id="upload_btn" style="margin-top: 15px;">
                                Upload Proof
                            </button>
                            <div id="upload_status" style="margin-top: 10px;"></div>
                        <?php else: ?>
                            <div class="alert alert-success" style="margin-top: 15px;">✓ Payment proof uploaded successfully!</div>
                        <?php endif; ?>
                    </div>

                    <!-- Cash on Delivery -->
                    <div class="payment-method-option" onclick="selectPayment('cod')">
                        <input type="radio" name="payment_method" value="cod" id="cod_payment" <?= !$all_items_support_cod ? 'disabled' : '' ?>>
                        <label for="cod_payment">
                            <strong>Cash on Delivery (COD)</strong>
                            <div style="font-size: 0.9rem; color: #6c757d;">
                                Pay when you receive your order
                                <?php if (!$all_items_support_cod): ?> <span class="text-danger"> (Not available for This Order) </span> <?php endif; ?>
                            </div>
                        </label>
                    </div>
                    <div class="payment-details mb-3" id="cod_details">
                        <?php if ($all_items_support_cod): ?>
                            <div class="cod-notice">
                                <strong>✓ Cash on Delivery Available</strong>
                                <p style="margin: 8px 0 0 0;">You can pay cash when your order is delivered to you.</p>
                            </div>
                        <?php else: ?>
                            <div class="cod-notice disabled">
                                <strong>✗ Cash on Delivery Not Available</strong>
                                <p style="margin: 8px 0 0 0;">Some items in your cart don't support COD. Please choose another payment method.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <hr style="margin: 30px 0;">
                    <button type="submit" name="place_order" class="btn btn-success btn-lg" id="place_order_btn" disabled>
                        Place Order ₦<?= number_format($total, 2) ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/bottomNav.php'; ?>
    <?php include 'includes/script.php'; ?>

    <script>
        document.getElementById("state_select").addEventListener("change", function() {
            fetch("get_lgas.php?state_id=" + this.value)
                .then(r => r.text())
                .then(data => document.getElementById("lga_select").innerHTML = data);
        });

        // Toggle delivery method
        const addressBox = document.getElementById("address_box");
        const pickupBox = document.getElementById("pickup_box");

        document.querySelectorAll("input[name='delivery_method']").forEach(radio => {
            radio.addEventListener("change", function() {
                if (this.value === "home") {
                    addressBox.style.display = "block";
                    pickupBox.style.display = "none";
                } else {
                    addressBox.style.display = "none";
                    pickupBox.style.display = "block";
                }
            });
        });
    </script>

    <script>
        let selectedPaymentMethod = null;
        const proofUploaded = <?= $proof_uploaded ? 'true' : 'false' ?>;
        const codAvailable = <?= $all_items_support_cod ? 'true' : 'false' ?>;

        function selectPayment(method) {
            // Hide all details
            document.querySelectorAll('.payment-details').forEach(el => el.classList.remove('active'));

            // Uncheck all radios
            document.querySelectorAll('input[name="payment_method"]').forEach(el => el.checked = false);

            // Show selected details and check radio
            if (method === 'card') {
                document.getElementById('card_details').classList.add('active');
                document.getElementById('card_payment').checked = true;
                selectedPaymentMethod = 'card';
            } else if (method === 'bank') {
                document.getElementById('bank_details').classList.add('active');
                document.getElementById('bank_payment').checked = true;
                selectedPaymentMethod = 'bank_transfer';
            } else if (method === 'cod') {
                if (codAvailable) {
                    document.getElementById('cod_details').classList.add('active');
                    document.getElementById('cod_payment').checked = true;
                    selectedPaymentMethod = 'cod';
                }
            }

            updatePlaceOrderButton();
        }

        function updatePlaceOrderButton() {
            const placeOrderBtn = document.getElementById('place_order_btn');

            if (!selectedPaymentMethod) {
                placeOrderBtn.disabled = true;
                return;
            }

            if (selectedPaymentMethod === 'bank_transfer' && !proofUploaded) {
                placeOrderBtn.disabled = true;
            } else {
                placeOrderBtn.disabled = false;
            }
        }

        function copyInput(button) {
            const input = button.previousElementSibling;
            input.select();
            input.setSelectionRange(0, 99999); // For mobile devices

            try {
                const successful = document.execCommand("copy");
                if (successful) {
                    const original = button.textContent;
                    button.textContent = 'Copied!';
                    button.classList.add('copied');

                    setTimeout(() => {
                        button.textContent = original;
                        button.classList.remove('copied');
                    }, 2000);
                } else {
                    alert("Copy failed. Please copy manually.");
                }
            } catch (err) {
                alert("Copy not supported in this browser.");
            }
        }

        // File preview functionality
        document.getElementById('payment_proof')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const preview = document.getElementById('file_preview');
            const previewImage = document.getElementById('preview_image');
            const fileName = document.getElementById('file_name');
            const fileSize = document.getElementById('file_size');

            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    preview.classList.add('active');
                };
                reader.readAsDataURL(file);
            } else if (file.type === 'application/pdf') {
                previewImage.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect width="200" height="200" fill="%23f8f9fa"/%3E%3Ctext x="100" y="100" font-size="48" text-anchor="middle" dy=".3em" fill="%23dc3545"%3EPDF%3C/text%3E%3C/svg%3E';
                preview.classList.add('active');
            }
        });

        // Card number formatting
        document.getElementById('card_number')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });

        // Expiry date formatting
        document.getElementById('card_expiry')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            e.target.value = value;
        });

        // CVV numeric only
        document.getElementById('card_cvv')?.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });

        // Update button state on page load
        updatePlaceOrderButton();

        document.getElementById("upload_btn").addEventListener("click", function() {
            let fileInput = document.getElementById("payment_proof");
            let file = fileInput.files[0];

            if (!file) {
                alert("Please select a file first.");
                return;
            }

            let formData = new FormData();
            formData.append("payment_proof", file);

            // Show loading
            const statusDiv = document.getElementById("upload_status");
            statusDiv.innerHTML = `<div class="alert alert-info">Uploading...</div>`;

            fetch("upload_proof.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {

                    if (data.status === "success") {
                        statusDiv.innerHTML = `<div class="alert alert-success">✔ ${data.message}</div>`;

                        // Store reference in JS
                        window.proofUploaded = true;

                        // Enable Place Order button
                        updatePlaceOrderButton();

                        // Auto-show preview
                        if (file.type.startsWith("image/")) {
                            document.getElementById("preview_image").src = data.preview;
                            document.getElementById("file_preview").classList.add("active");
                        }

                    } else {
                        statusDiv.innerHTML = `<div class="alert alert-danger">❌ ${data.message}</div>`;
                    }
                })
                .catch(err => {
                    statusDiv.innerHTML = `<div class="alert alert-danger">Upload failed. Try again.</div>`;
                });
        });

        // override PHP value with JS
        window.proofUploaded = <?= $proof_uploaded ? 'true' : 'false' ?>;

        function updatePlaceOrderButton() {
            const btn = document.getElementById("place_order_btn");

            if (!selectedPaymentMethod) {
                btn.disabled = true;
                return;
            }

            if (selectedPaymentMethod === "bank_transfer" && !window.proofUploaded) {
                btn.disabled = true;
            } else {
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>