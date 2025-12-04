<?php
require 'app/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$errors = [];
$couponMessage = "";
$discount = 0;
$totalAmount = 0;
$delivery_fee = 0; // Free delivery

// Fetch states for the state dropdown
$stmt = $pdo->query("SELECT id, name FROM states ORDER BY name ASC");
$states = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
    FUNCTION: GET CART ITEMS
============================================================ */
function getCartItems($isLoggedIn, $pdo)
{
    $items = [];

    if ($isLoggedIn) {
        $buyer_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("
            SELECT c.product_id, c.quantity, p.price, p.name 
            FROM cart_items c
            JOIN products p ON c.product_id = p.id
            WHERE c.buyer_id = ?
        ");
        $stmt->execute([$buyer_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Guest cart
    if (!empty($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $pid => $qty) {
            $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id=?");
            $stmt->execute([$pid]);
            $p = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($p) {
                $items[] = [
                    'product_id' => $p['id'],
                    'name'       => $p['name'],
                    'price'      => $p['price'],
                    'quantity'   => $qty
                ];
            }
        }
    }

    return $items;
}

/* ============================================================
    LOAD CART FIRST
============================================================ */
$cartItems = getCartItems($isLoggedIn, $pdo);

if (empty($cartItems)) {
    $errors[] = "Your cart is empty.";
}

/* ============================================================
    CALCULATE SUBTOTAL
============================================================ */
foreach ($cartItems as $item) {
    $totalAmount += (float)$item['price'] * (int)$item['quantity'];
}

/* ============================================================
    COUPON APPLY CHECK (APPLY BUTTON)
============================================================ */
if (isset($_POST['apply_coupon'])) {

    $coupon_code = trim($_POST['coupon_code']);

    if ($coupon_code === "") {
        $couponMessage = "<span class='text-danger'>Enter a coupon code.</span>";
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM coupons 
            WHERE code = ? 
            AND status='active' 
            AND coupon_type='online'
            AND expiry_date >= CURDATE()
        ");
        $stmt->execute([$coupon_code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($coupon) {

            // Must meet minimum spend
            if ($totalAmount < $coupon['min_spend']) {
                $couponMessage = "<span class='text-danger'>Minimum spend for this coupon is ₦" . number_format($coupon['min_spend']) . "</span>";
            } else {
                // Apply discount
                if ($coupon['discount_type'] === 'percentage') {
                    $discount = $totalAmount * ($coupon['discount_value'] / 100);
                } else {
                    $discount = $coupon['discount_value'];
                }

                $_SESSION['applied_coupon'] = [
                    'code' => $coupon_code,
                    'discount' => $discount
                ];

                $couponMessage = "<span class='text-success'>Coupon applied successfully.</span>";
            }
        } else {
            $couponMessage = "<span class='text-danger'>Invalid or expired coupon.</span>";
        }
    }
}

/* ============================================================
    USE SAVED COUPON IF EXISTS
============================================================ */
if (isset($_SESSION['applied_coupon'])) {
    $discount = $_SESSION['applied_coupon']['discount'];
    $coupon_code = $_SESSION['applied_coupon']['code'];
}

/* ============================================================
    FINAL TOTAL
============================================================ */
$grandTotal = max($totalAmount - $discount + $delivery_fee, 0);

/* ============================================================
    HANDLE CHECKOUT FORM (GUEST ONLY)
============================================================ */
if (isset($_POST['proceed_payment']) && !$isLoggedIn) {

    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $address   = trim($_POST['address']);
    $state_id  = trim($_POST['state_id']);
    $lga_id    = trim($_POST['lga_id']);
    $country = "Nigeria"; // Default country

    // Required fields
    if (!$full_name || !$email || !$phone || !$address || !$state_id || !$lga_id) {
        $errors[] = "All required fields must be filled.";
    }

    if (empty($errors)) {

        // Does email exist? → Redirect to login
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$email]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {

            $_SESSION['pending_checkout'] = [
                'cart'        => $_SESSION['guest_cart'] ?? [],
                'full_name'   => $full_name,
                'email'       => $email,
                'phone'       => $phone,
                'address'     => $address,
                'state_id'    => $state_id,
                'lga_id'      => $lga_id,
                'grand_total' => $grandTotal
            ];

            header("Location: auth/login.php?redirect=checkout");
            exit;
        }

        // ============================================================
        // CREATE AUTO ACCOUNT (password = phone number)
        // ============================================================
        $rawPassword = substr(str_shuffle("0123456789"), 0, 6);
        $hashedPass = password_hash($rawPassword, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, phone, country, password_hash, address, state_id, lga_id, approval_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved')
        ");
        // die(var_dump($_POST));
        $stmt->execute([$full_name, $email, $phone, $country, $hashedPass, $address, $state_id, $lga_id]);

        $newID = $pdo->lastInsertId();
        $_SESSION['user_id'] = $newID;

        // ============================================================
        // MERGE GUEST CART INTO USER CART
        // ============================================================
        if (!empty($_SESSION['guest_cart'])) {

            $q = $pdo->prepare("
                INSERT INTO cart_items (buyer_id, product_id, quantity)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
            ");

            foreach ($_SESSION['guest_cart'] as $pid => $qty) {
                $q->execute([$newID, intval($pid), intval($qty)]);
            }

            unset($_SESSION['guest_cart']);
        }

        // Save info to notify user later
        $_SESSION['auto_account_info'] = [
            'email'    => $email,
            'password' => $rawPassword  // show them phone = password
        ];

        // Continue checkout
        header("Location: place_order.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Checkout | <?= APP_NAME ?></title>
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

        .checkout-box,
        .summary-box {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        h3,
        h5 {
            font-weight: 600;
        }

        label {
            margin-top: 12px;
            font-weight: 500;
        }

        .btn-success {
            padding: 12px;
            font-size: 1.1rem;
            border-radius: 10px;
        }

        .summary-box {
            position: sticky;
            top: 90px;
        }

        @media (max-width: 767px) {
            .summary-box {
                position: static;
                margin-top: 15px;
            }

            .btn-success {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/nav.php'; ?>

    <div class="container my-4">
        <div class="row gy-4">

            <!-- LEFT SIDE -->
            <div class="col-lg-7">
                <h3 class="mb-3">Checkout</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $e) echo "<div>$e</div>"; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$isLoggedIn): ?>
                    <div class="checkout-box">

                        <form method="POST">

                            <label>Full Name *</label>
                            <input class="form-control" name="full_name" required
                                value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">

                            <label>Email *</label>
                            <input class="form-control" type="email" name="email" required
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

                            <label>Phone *</label>
                            <input class="form-control" name="phone" required
                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

                            <label>Address * (can be used as shipping address)</label>
                            <textarea class="form-control" name="address" rows="3" required>
                                <?= htmlspecialchars($_POST['address'] ?? '') ?>
                            </textarea>

                            <label>State *</label>
                            <select class="form-control" id="state_select" name="state_id" required>
                                <option value="">Select State</option>
                                <?php foreach ($states as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label>LGA *</label>
                            <select class="form-control" id="lga_select" name="lga_id" required>
                                <option value="">Select LGA</option>
                            </select>

                            <label class="mt-3">Promo Code</label>
                            <div class="input-group">
                                <input class="form-control" name="coupon_code"
                                    value="<?= htmlspecialchars($_POST['coupon_code'] ?? ($_SESSION['applied_coupon']['code'] ?? '')) ?>">
                                <button class="btn btn-primary" name="apply_coupon">Apply</button>
                            </div>
                            <small><?= $couponMessage ?></small>

                            <button class="btn btn-success w-100 mt-4" name="proceed_payment">
                                Proceed to Payment
                            </button>

                        </form>
                    </div>

                <?php else: ?>

                    <div class="checkout-box mb-3">
                        <form method="POST">
                            <label>Promo Code</label>
                            <div class="input-group">
                                <input class="form-control" name="coupon_code"
                                    value="<?= htmlspecialchars($_POST['coupon_code'] ?? ($_SESSION['applied_coupon']['code'] ?? '')) ?>">
                                <button class="btn btn-primary" name="apply_coupon">Apply</button>
                            </div>
                            <small><?= $couponMessage ?></small>
                        </form>
                    </div>

                    <a href="place_order.php" class="btn btn-success w-100 btn-lg">
                        Proceed to Payment
                    </a>

                <?php endif; ?>
            </div>

            <!-- RIGHT SUMMARY -->
            <div class="col-lg-5">
                <div class="summary-box">

                    <h5 class="mb-3">Order Summary</h5>

                    <?php if (!empty($cartItems)): ?>
                        <table class="table table-borderless table-sm">

                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></td>
                                    <td class="text-end">₦<?= number_format((float)$item['price'] * (int)$item['quantity']) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <tr class="border-top">
                                <td><strong>Subtotal</strong></td>
                                <td class="text-end">₦<?= number_format($totalAmount) ?></td>
                            </tr>

                            <?php if ($discount > 0): ?>
                                <tr>
                                    <td><strong>Discount</strong></td>
                                    <td class="text-end text-danger">-₦<?= number_format($discount) ?></td>
                                </tr>
                            <?php endif; ?>

                            <tr class="border-top">
                                <td><strong>Total</strong></td>
                                <td class="text-end fw-bold">₦<?= number_format($grandTotal) ?></td>
                            </tr>

                        </table>

                    <?php else: ?>
                        <p>Your cart is empty.</p>
                    <?php endif; ?>

                </div>
            </div>

        </div>
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
    </script>

</body>

</html>