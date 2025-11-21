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
        <?php
        $isUsers = in_array($current_page, ['all_admin.php', 'admin_sellers.php', 'admin_buyers.php']);
        ?>
        <li class="dropdown <?= $isUsers ? 'active' : '' ?>">
            <a href="#" class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#usersMenu" aria-expanded="<?= $isUsers ? 'true' : 'false' ?>">
                <i class="bi bi-people"></i> Users
            </a>
            <ul id="usersMenu" class="collapse list-unstyled ps-3 <?= $isUsers ? 'show' : '' ?>">
                <li class="<?= $current_page == 'all_admins.php' ? 'active' : '' ?>">
                    <a href="all_admins.php"><i class="bi bi-person-lines-fill"></i> All Admins</a>
                </li>
                <li class="<?= $current_page == 'admin_sellers.php' ? 'active' : '' ?>">
                    <a href="admin_sellers.php"><i class="bi bi-person-badge"></i> Sellers</a>
                </li>
                <li class="<?= $current_page == 'admin_buyers.php' ? 'active' : '' ?>">
                    <a href="admin_buyers.php"><i class="bi bi-person"></i> Buyers</a>
                </li>
            </ul>
        </li>
        <li class="<?= $current_page == 'admin_products.php' ? 'active' : '' ?>">
            <a href="admin_products.php"><i class="bi bi-box-seam"></i> Products</a>
        </li>
        <li class="<?= $current_page == 'admin_orders.php' ? 'active' : '' ?>">
            <a href="admin_orders.php"><i class="bi bi-cart"></i> Orders</a>
        </li>
        <li class="<?= $current_page == 'coupons.php' ? 'active' : '' ?>">
            <a href="coupons.php"><i class="bi bi-gift"></i>
                Coupons
            </a>
        </li>
        <li class="<?= $current_page == 'admin_reports.php' ? 'active' : '' ?>">
            <a href="admin_reports.php"><i class="bi bi-flag"></i> Reports</a>
        </li>


        <!-- Transactions Dropdown -->
        <?php
        $isTransactions = in_array($current_page, ['bank_transfers.php', 'automatic_transactions.php', 'bank_transfer_details.php']);
        ?>
        <li class="dropdown <?= $isTransactions ? 'active' : '' ?>">
            <a href="#" class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#transactionsMenu" aria-expanded="<?= $isTransactions ? 'true' : 'false' ?>">
                <i class="bi bi-receipt"></i> Transactions
            </a>
            <ul id="transactionsMenu" class="collapse list-unstyled ps-3 <?= $isTransactions ? 'show' : '' ?>">
                <li class="<?= $current_page == 'bank_transfer_details.php' ? 'active' : '' ?>">
                    <a href="bank_transfer_details.php"><i class="bi bi-diagram-2"></i> Bank Transfer Details</a>
                </li>
                <li class="<?= $current_page == 'bank_transfers.php' ? 'active' : '' ?>">
                    <a href="bank_transfers.php"><i class="bi bi-bank"></i> Bank Transfers</a>
                </li>
                <li class="<?= $current_page == 'automatic_transactions.php' ? 'active' : '' ?>">
                    <a href="automatic_transactions.php" onclick="alert('This feature is under development!'); return false"><i class="bi bi-credit-card"></i> Automatic Payments</a>
                </li>
            </ul>


        </li>

        <!-- Categories Dropdown -->
        <?php
        $isCategories = in_array($current_page, ['admin_categories.php', 'admin_subcategories.php']);
        ?>
        <li class="dropdown <?= $isCategories ? 'active' : '' ?>">
            <a href="#" class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#categoriesMenu" aria-expanded="<?= $isCategories ? 'true' : 'false' ?>">
                <i class="bi bi-tags"></i> Categories
            </a>
            <ul id="categoriesMenu" class="collapse list-unstyled ps-3 <?= $isCategories ? 'show' : '' ?>">
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