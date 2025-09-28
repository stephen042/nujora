<?php
// get current page name, e.g. "home.php"
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="nav nav-pills nav-fill nav-bottom">
    <a class="nav-link <?= ($currentPage == 'home.php') ? 'active' : '' ?>" href="home.php">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <a class="nav-link <?= ($currentPage == 'products.php') ? 'active' : '' ?>" href="products.php">
        <i class="fas fa-shop"></i>
        <span>Products</span>
    </a>
    <a class="nav-link <?= ($currentPage == 'cart.php') ? 'active' : '' ?>" href="cart.php">
        <i class="fas fa-shopping-cart"></i>
        <span>Cart</span>
    </a>
    <a class="nav-link <?= ($currentPage == 'orders.php') ? 'active' : '' ?>" href="orders.php">
        <i class="fas fa-box"></i>
        <span>Orders</span>
    </a>
    <a class="nav-link <?= ($currentPage == 'profile.php') ? 'active' : '' ?>" href="profile.php">
        <i class="fas fa-user"></i>
        <span>Profile</span>
    </a>
</nav>

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
    }

    /* ================= BOTTOM NAV ================= */
    .nav-bottom {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: var(--light-bg);
        border-top: 1px solid var(--border-color);
        z-index: 1050;
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 0.3rem 0;
        box-shadow: 0 -2px 6px rgba(0, 0, 0, 0.05);
    }

    .nav-bottom .nav-link {
        flex: 1;
        text-align: center;
        color: var(--muted-text);
        font-size: 0.85rem;
        padding: 0.4rem 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        transition: all 0.3s ease;
    }

    .nav-bottom .nav-link i {
        font-size: 1.2rem;
        margin-bottom: 0.2rem;
        transition: color 0.3s ease, transform 0.3s ease;
    }

    /* Active link */
    .nav-bottom .nav-link.active {
        color: var(--primary-color);
        font-weight: 600;
    }

    /* Hover effect */
    .nav-bottom .nav-link:hover {
        color: var(--secondary-color);
    }

    .nav-bottom .nav-link:hover i {
        transform: scale(1.2);
    }

    /* Mobile adjustments */
    @media (max-width: 576px) {
        .nav-bottom .nav-link {
            font-size: 0.7rem;
            padding: 0.25rem 0;
        }

        .nav-bottom .nav-link i {
            font-size: 1rem;
            margin-bottom: 0.1rem;
        }
    }
</style>