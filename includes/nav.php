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
    $buyerId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT SUM(quantity) AS total_items FROM cart_items WHERE buyer_id = ?");
    $stmt->execute([$buyerId]);
    $cartCount = $stmt->fetchColumn() ?? 0;
} elseif (isset($_SESSION['guest_cart'])) {
    $cartCount = array_sum($_SESSION['guest_cart']);
}
?>
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