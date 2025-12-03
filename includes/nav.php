<?php
require_once 'app/rate_limiter.php';

if (!global_rate_limit(50, 60)) {
    http_response_code(429);
    die("Too many requests. Try again in a 3 minute.");
}
?>
<?php
$cartCount = 0;

if (isset($_SESSION['user_id'])) {
    // LOGGED IN USER
    $buyerId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT SUM(quantity) AS total_items FROM cart_items WHERE buyer_id = ?");
    $stmt->execute([$buyerId]);
    $cartCount = $stmt->fetchColumn() ?? 0;
} elseif (!empty($_SESSION['guest_cart'])) {
    // GUEST USER
    foreach ($_SESSION['guest_cart'] as $item) {
        $cartCount += intval($item['quantity']);
    }
}

?>
<!-- TOP BANNER STRIP -->
<style>
    /* Desktop default */
    .top-banner {
        width: 100%;
        background-color: black;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 100px;
        margin: 0;
        height: 60px;
        border-bottom: 1px solid #eee;
        overflow: hidden;
    }

    .top-banner img {
        height: 60px;
        width: auto;
        max-width: 100%;
        object-fit: cover;
        flex-grow: 1;
    }

    /* Mobile View Fix */
    @media (max-width: 600px) {
        .top-banner {
            height: 70px;
            /* Slightly taller on mobile */
            padding: 0;
            /* Reduce side padding on mobile */
        }

        .top-banner img {
            width: 100%;
            /* Fill full width on mobile */
            height: 70px;
            /* Match container */
            object-fit: cover;
        }
    }
</style>
<!-- TOP BANNER STRIP -->
<div class="top-banner">
    <img src="uploads/topbar.gif" alt="Promo Banner">
</div>

<!-- Contact Us NAV -->
<style>
    .contact-bar {
        width: 100%;
        background-color: #f8f8f8;
        border-bottom: 1px solid #e5e5e5;
        padding: 6px 0;
        font-size: 0.85rem;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .contact-wrapper {
        width: 95%;
        max-width: 1200px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #333;
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 500;
    }

    .contact-item i {
        color: #ff9900;
        /* Jumia-style orange accent */
        font-size: 1rem;
    }

    /* Mobile */
    @media (max-width: 600px) {
        .contact-wrapper {
            /* flex-direction: column; */
            gap: 4px;
            text-align: center;
            padding: 0 20px;
        }
    }
</style>
<!-- CONTACT INFO BAR -->
<div class="contact-bar">
    <div class="contact-wrapper">

        <div class="contact-item">
            <i class="fas fa-phone-alt"></i>
            <span> <a href="tel:+<?=APP_PHONE?>"><?= APP_PHONE ?></a></span>
        </div>

        <div class="contact-item">
            <i class="fas fa-envelope"></i>
            <span><a href="mailto:<?= APP_EMAIL ?>"><?= APP_EMAIL ?></a></span>
        </div>

        <div class="contact-item d-none d-md-flex">
            <i class="fas fa-headset"></i>
            <span>24/7 Customer Support</span>
        </div>

    </div>
</div>


</div>
<!-- NAVIGATION BAR -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container d-flex justify-content-between align-items-center flex-nowrap">
        <?php include 'app/logo.php'; ?>
        <div class="d-flex align-items-center flex-shrink-0" style="gap: 0.5rem;">
            <a href="cart.php"
                class="btn btn-outline-secondary position-relative me-2 py-1 px-2 px-sm-3"
                style="font-size: 0.85rem;">
                <i class="fas fa-shopping-cart"></i>
                <span class="d-sm-inline">My Cart</span>
                <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo $cartCount ?? 0 ?>
                </span>
            </a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle py-1 px-2 px-sm-3"
                        type="button" id="userDropdown" data-bs-toggle="dropdown"
                        style="font-size: 0.85rem;">
                        <i class="fas fa-user-circle"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="orders.php"><i class="fas fa-box me-2"></i>My Orders</a></li>
                        <li><a class="dropdown-item" href="buyer-dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>My Dashboard</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="../auth/login.php"
                    class="btn btn-outline-secondary d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill shadow-sm border-1"
                    style="font-size: 0.85rem;">
                    <i class="fas fa-sign-in-alt"></i>
                    <span class="d-sm-inline">Login</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>