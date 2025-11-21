<?php
require 'app/config.php';

$isLoggedIn = isset($_SESSION['user_id']);
$errors = [];
$couponMessage = "";
$discount = 0;
$totalAmount = 0;
$delivery_fee = 0; // Free delivery

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
    $totalAmount += $item['price'] * $item['quantity'];
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
    $state     = trim($_POST['state']);
    $city      = trim($_POST['city']);

    // Required fields check
    if (!$full_name || !$email || !$phone || !$address) {
        $errors[] = "All required fields must be filled.";
    }

    if (empty($errors)) {

        // Does email already exist?
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $stmt->execute([$email]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exists) {
            // Redirect to login with cart preserved
            $_SESSION['pending_checkout'] = [
                'cart'         => $_SESSION['guest_cart'] ?? [],
                'full_name'    => $full_name,
                'email'        => $email,
                'phone'        => $phone,
                'address'      => $address,
                'state'        => $state,
                'city'         => $city,
                'grand_total'  => $grandTotal
            ];
            header("Location: auth/login.php?redirect=checkout");
            exit;
        }

        // Create auto account
        $autoPass = substr(md5(time()), 0, 8);
        $hashedPass = password_hash($autoPass, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("INSERT INTO users (name,email,phone,password_hash,address,state,lga, approval_status) 
                               VALUES (?,?,?,?,?,?,?, 'approved')");
        $stmt->execute([$full_name, $email, $phone, $hashedPass, $address, $state, $city]);

        $newID = $pdo->lastInsertId();
        $_SESSION['user_id'] = $newID;

        // Transfer guest cart into DB
        if (!empty($_SESSION['guest_cart'])) {
            foreach ($_SESSION['guest_cart'] as $pid => $qty) {
                $q = $pdo->prepare("INSERT INTO cart_items (buyer_id,product_id,quantity)
                                    VALUES (?,?,?)
                                    ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
                $q->execute([$newID, $pid, $qty]);
            }
            unset($_SESSION['guest_cart']);
        }

        header("Location: place_order.php");
        $_SESSION['auto_account_info'] = [
            'email'    => $email,
            'password' => $autoPass
        ];
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Checkout - <?= APP_NAME ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="uploads/default-product.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background: #f8f9fa;
            font-family: 'Open Sans';
        }

        .summary-box,
        .checkout-box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        input {
            height: 48px;
        }

        /* ============================================
        STRICT MOBILE LAYOUT FOR CHECKOUT PAGE
        ============================================ */
        @media (max-width: 768px) {

            /* Stop ALL horizontal stretching */
            html,
            body {
                width: 100%;
                overflow-x: hidden !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            /* Force the row stack vertically */
            .checkout-container,
            .row.g-4,
            .row {
                display: block !important;
                width: 100%;
            }

            /* Left section (checkout form) */
            .col-lg-7 {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Right section (summary box) */
            .col-lg-5 {
                width: 100% !important;
                max-width: 100% !important;
                margin-top: 20px !important;
                padding: 0 !important;
            }

            /* Boxes resize correctly */
            .summary-box,
            .checkout-box {
                width: 100% !important;
                margin: 0 0 20px 0 !important;
                padding: 20px !important;
                box-sizing: border-box !important;
            }

            /* Inputs full width */
            .checkout-box input,
            .checkout-box .input-group input {
                width: 100% !important;
                box-sizing: border-box !important;
            }

            /* Input-group (coupon) */
            .input-group {
                width: 100% !important;
                flex-wrap: nowrap !important;
            }

            .input-group .form-control {
                width: 70% !important;
            }

            .input-group .btn {
                width: 30% !important;
            }

            /* Summary table full width and wrap text */
            .summary-box table {
                width: 100% !important;
                table-layout: fixed !important;
                word-wrap: break-word;
                word-break: break-word;
            }

            /* Fix bottom nav white space */
            .bottom-nav,
            .mobile-bottom-nav,
            .fixed-bottom {
                margin: 0 !important;
                padding: 0 !important;
            }

            footer {
                margin-bottom: 0 !important;
                padding-bottom: 0 !important;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/nav.php'; ?>

    <div class="container my-4">
        <div class="row g-4">

            <!-- ========================= LEFT SIDE ========================= -->
            <div class="col-lg-7">
                <h3 class="mb-3">Checkout</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $e) echo "<div>$e</div>"; ?>
                    </div>
                <?php endif; ?>

                <!-- Only show form for guests -->
                <?php if (!$isLoggedIn): ?>
                    <div class="checkout-box">

                        <form method="POST">    

                            <!-- NAME -->
                            <label>Full Name *</label>
                            <input class="form-control" name="full_name" required
                                value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">

                            <!-- EMAIL -->
                            <label>Email *</label>
                            <input class="form-control" type="email" name="email" required
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

                            <!-- PHONE -->
                            <label>Phone *</label>
                            <input class="form-control" name="phone" required
                                value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

                            <!-- ADDRESS -->
                            <label>Address *</label>
                            <input class="form-control" name="address" required
                                value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">

                            <!-- STATE -->
                            <label>State *</label>
                            <select class="form-control mb-2" name="state" id="state_select" required>
                                <option value="">Select State</option>
                                <?php
                                $states = $pdo->query("SELECT id, name FROM states ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($states as $s):
                                ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <!-- LGA -->
                            <label>LGA *</label>
                            <select class="form-control mb-2" name="city" id="lga_select" required>
                                <option value="">Select LGA</option>
                            </select>


                            <!-- COUPON APPLY FIELD -->
                            <label>Promo Code</label>
                            <div class="input-group">
                                <input class="form-control" name="coupon_code"
                                    value="<?= htmlspecialchars($_POST['coupon_code'] ?? ($_SESSION['applied_coupon']['code'] ?? '')) ?>">
                                <button class="btn btn-primary" name="apply_coupon">Apply</button>
                            </div>
                            <small><?= $couponMessage ?></small>

                            <button class="btn btn-success w-100 mt-4" name="proceed_payment">Proceed to Payment</button>
                        </form>

                    </div>

                <?php else: ?>
                    <div class="checkout-box mb-3">

                        <!-- COUPON FIELD FOR LOGGED-IN USERS -->
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

                    <!-- PAYMENT BUTTON -->
                    <a href="place_order.php" class="btn btn-success w-100 btn-lg mt-2">
                        Proceed to Payment
                    </a>
                <?php endif; ?>
            </div>

            <!-- ========================= RIGHT SIDE SUMMARY ========================= -->
            <div class="col-lg-5">
                <div class="summary-box">

                    <h5 class="mb-3">Order Summary</h5>

                    <?php if (!empty($cartItems)): ?>
                        <table class="table table-sm">

                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></td>
                                    <td class="text-end">₦<?= number_format($item['price'] * $item['quantity']) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <tr>
                                <td><strong>Subtotal</strong></td>
                                <td class="text-end">₦<?= number_format($totalAmount) ?></td>
                            </tr>

                            <?php if ($discount > 0): ?>
                                <tr>
                                    <td><strong>Discount</strong></td>
                                    <td class="text-end text-danger">-₦<?= number_format($discount) ?></td>
                                </tr>
                            <?php endif; ?>

                            <tr>
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
            const stateID = this.value;

            fetch("get_lgas.php?state_id=" + stateID)
                .then(response => response.text())
                .then(data => {
                    document.getElementById("lga_select").innerHTML = data;
                });
        });
    </script>

</body>

</html>