<?php
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    $_SESSION['statusMessage'] = $msg;
    exit;
} elseif ($_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h4><?= APP_NAME ?> Admin</h4>
    </div>

    <ul class="sidebar-menu">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']); // Get the current file name
        ?>
        <li class="<?= $current_page == 'admin-dashboard.php' ? 'active' : '' ?>">
            <a href="admin-dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        </li>
        <li class="<?= $current_page == 'admin_users.php' ? 'active' : '' ?>">
            <a href="admin_users.php"><i class="bi bi-people"></i> Users</a>
        </li>
        <li class="<?= $current_page == 'admin_products.php' ? 'active' : '' ?>">
            <a href="admin_products.php"><i class="bi bi-box-seam"></i> Products</a>
        </li>
        <li class="<?= $current_page == 'admin_orders.php' ? 'active' : '' ?>">
            <a href="admin_orders.php"><i class="bi bi-cart"></i> Orders</a>
        </li>
        <li class="<?= $current_page == 'admin_reports.php' ? 'active' : '' ?>">
            <a href="admin_reports.php"><i class="bi bi-flag"></i> Reports</a>
        </li>

        <!-- Categories Dropdown -->
        <?php
        $isCategoryPage = in_array($current_page, ['admin_categories.php', 'admin_subcategories.php']);
        ?>
        <li class="dropdown <?= $isCategoryPage ? 'active' : '' ?>">
            <a href="#" class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#categoriesMenu" aria-expanded="<?= $isCategoryPage ? 'true' : 'false' ?>">
                <i class="bi bi-tags"></i> Categories
            </a>
            <ul id="categoriesMenu" class="collapse list-unstyled ps-3 <?= $isCategoryPage ? 'show' : '' ?>">
                <li class="<?= $current_page == 'admin_categories.php' ? 'active' : '' ?>">
                    <a href="admin_categories.php"><i class="bi bi-tag"></i> Categories</a>
                </li>
                <li class="<?= $current_page == 'admin_subcategories.php' ? 'active' : '' ?>">
                    <a href="admin_subcategories.php"><i class="bi bi-diagram-3"></i> Sub-categories</a>
                </li>
            </ul>
        </li>

        <li class="<?= $current_page == 'admin_settings.php' ? 'active' : '' ?>">
            <a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a>
        </li>
        <li>
            <a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </li>
    </ul>

</div>