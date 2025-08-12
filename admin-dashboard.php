<?php
session_start();
// require_once 'admin_auth.php'; // Authentication check
require_once 'db.php'; // Database connection

// Initialize variables
$statusMessage = '';
$sellers = [];
$products = [];
$reports = [];
$buyerCount = 0;
$sellerCount = 0;
$productCount = 0;
$orderCount = 0;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['seller_action'])) {
        // Handle seller actions (approve/suspend)
        $seller_id = (int)$_POST['seller_id'];
        $action = $_POST['seller_action'];

        // Validate the action
        if (!in_array($action, ['pending', 'approved', 'rejected'])) {
            $statusMessage = '<div class="alert alert-danger">Invalid action provided.</div>';
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET approval_status = ? WHERE id = ? AND role = 'seller'");
            $stmt->execute([$action, $seller_id]);

            if ($stmt->rowCount() > 0) {
                $statusMessage = '<div class="alert alert-success">Seller status updated</div>';
            } else {
                $statusMessage = '<div class="alert alert-warning">No changes made to seller status.</div>';
            }
        } catch (PDOException $e) {
            $statusMessage = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } elseif (isset($_POST['product_action'])) {
        // Handle product actions (feature/delete)
        $product_id = (int)$_POST['product_id'];
        $action = $_POST['product_action'];
        
        try {
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $statusMessage = '<div class="alert alert-success">Product deleted</div>';
            } elseif ($action === 'feature') {
                $stmt = $pdo->prepare("UPDATE products SET is_featured = NOT is_featured WHERE id = ?");
                $stmt->execute([$product_id]);
                $statusMessage = '<div class="alert alert-success">Product feature status toggled</div>';
            }
        } catch (PDOException $e) {
            $statusMessage = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } elseif (isset($_POST['report_action'])) {
        // Handle report actions
        $report_id = (int)$_POST['report_id'];
        $action = $_POST['report_action'];
        
        try {
            if ($action === 'resolve') {
                $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
                $stmt->execute([$report_id]);
                $statusMessage = '<div class="alert alert-success">Report marked as resolved</div>';
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
                $stmt->execute([$report_id]);
                $statusMessage = '<div class="alert alert-success">Report deleted</div>';
            }
        } catch (PDOException $e) {
            $statusMessage = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

// Fetch data
try {
    // Get sellers
    $stmt = $pdo->prepare("SELECT id, name, email, approval_status, created_at FROM users WHERE role = 'seller' ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $sellers = $stmt->fetchAll();

    // Get recent products
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.price, p.created_at, u.name as seller_name, p.is_featured 
        FROM products p
        JOIN users u ON p.seller_id = u.id
        ORDER BY p.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();

    // Get flagged reports
    $stmt = $pdo->prepare("
        SELECT r.id, r.reason, r.status, r.created_at, 
               u.name as reporter_name, p.name as product_name
        FROM reports r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN products p ON r.product_id = p.id
        WHERE r.status = 'pending'
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $reports = $stmt->fetchAll();

    // Get stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'buyer'");
    $stmt->execute();
    $buyerCount = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'seller'");
    $stmt->execute();
    $sellerCount = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products");
    $stmt->execute();
    $productCount = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders");
    $stmt->execute();
    $orderCount = $stmt->fetch()['total'];

} catch (PDOException $e) {
    $statusMessage = '<div class="alert alert-danger">Database error: ' . $e->getMessage() . '</div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | TrendyMart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --sidebar-width: 250px;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background-color: #343a40;
            color: white;
            transition: all 0.3s;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }
        .sidebar-header {
            padding: 20px;
            background-color: #212529;
        }
        .sidebar-menu {
            padding: 0;
            list-style: none;
        }
        .sidebar-menu li {
            padding: 10px 20px;
            border-bottom: 1px solid #495057;
        }
        .sidebar-menu li a {
            color: #adb5bd;
            text-decoration: none;
            display: block;
        }
        .sidebar-menu li a:hover {
            color: white;
        }
        .sidebar-menu li.active {
            background-color: #495057;
        }
        .sidebar-menu li.active a {
            color: white;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            font-weight: 600;
        }
        .stat-card {
            text-align: center;
            padding: 20px;
            color: white;
            border-radius: 10px;
        }
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .badge-featured {
            background-color: #ffc107;
            color: #212529;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .badge-approved {
            background-color: #28a745;
            color: white;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-suspended {
            background-color: #dc3545;
            color: white;
        }
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
            .main-content.active {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4>TrendyMart Admin</h4>
        </div>
        <ul class="sidebar-menu">
            <li class="active">
                <a href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            </li>
            <li>
                <a href="admin_users.php"><i class="bi bi-people"></i> Users</a>
            </li>
            <li>
                <a href="admin_products.php"><i class="bi bi-box-seam"></i> Products</a>
            </li>
            <li>
                <a href="admin_orders.php"><i class="bi bi-cart"></i> Orders</a>
            </li>
            <li>
                <a href="admin_reports.php"><i class="bi bi-flag"></i> Reports</a>
            </li>
            <li>
                <a href="admin_settings.php"><i class="bi bi-gear"></i> Settings</a>
            </li>
            <li>
                <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Admin Dashboard</h2>
            <button class="btn btn-outline-secondary d-md-none" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
        </div>

        <?php echo $statusMessage; ?>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary">
                    <i class="bi bi-people"></i>
                    <h3><?= number_format($buyerCount) ?></h3>
                    <p>Buyers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success">
                    <i class="bi bi-shop"></i>
                    <h3><?= number_format($sellerCount) ?></h3>
                    <p>Sellers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info">
                    <i class="bi bi-box-seam"></i>
                    <h3><?= number_format($productCount) ?></h3>
                    <p>Products</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning">
                    <i class="bi bi-cart"></i>
                    <h3><?= number_format($orderCount) ?></h3>
                    <p>Orders</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sellers Section -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Sellers</h5>
                        <a href="admin_users.php?role=seller" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sellers as $seller): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($seller['name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($seller['email']) ?></small>
                                            </td>
                                            <td>
                                                <?php if ($seller['approval_status'] === 'approved'): ?>
                                                    <span class="status-badge badge-approved">Approved</span>
                                                <?php elseif ($seller['approval_status'] === 'pending'): ?>
                                                    <span class="status-badge badge-pending">Pending</span>
                                                <?php else: ?>
                                                    <span class="status-badge badge-rejected">Rejected</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="action-buttons">
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to reject this seller?');">
                                                    <input type="hidden" name="seller_id" value="<?= $seller['id'] ?>">
                                                    <?php if ($seller['approval_status'] === 'approved'): ?>
                                                        <input type="hidden" name="seller_action" value="rejected">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                                    <?php elseif ($seller['approval_status'] === 'pending'): ?>
                                                        <input type="hidden" name="seller_action" value="approved">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="seller_action" value="approved">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">Reinstate</button>
                                                    <?php endif; ?>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Products</h5>
                        <a href="admin_products.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Seller</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($product['name']) ?></strong>
                                                <?php if ($product['is_featured']): ?>
                                                    <span class="badge badge-featured">Featured</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>₦<?= number_format($product['price'], 2) ?></td>
                                            <td><?= htmlspecialchars($product['seller_name']) ?></td>
                                            <td class="action-buttons">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                    <input type="hidden" name="product_action" value="feature">
                                                    <button type="submit" class="btn btn-sm <?= $product['is_featured'] ? 'btn-warning' : 'btn-outline-primary' ?>">
                                                        <?= $product['is_featured'] ? 'Unfeature' : 'Feature' ?>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                    <input type="hidden" name="product_action" value="delete">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                                </form>
                                                
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Pending Reports</h5>
                        <a href="admin_reports.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($reports)): ?>
                            <div class="alert alert-info">No pending reports</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Report</th>
                                            <th>Product</th>
                                            <th>Reporter</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reports as $report): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($report['reason']) ?></td>
                                                <td><?= $report['product_name'] ? htmlspecialchars($report['product_name']) : 'N/A' ?></td>
                                                <td><?= htmlspecialchars($report['reporter_name']) ?></td>
                                                <td><?= date('M j, Y', strtotime($report['created_at'])) ?></td>
                                                <td class="action-buttons">
                                                    <a href="admin_report_detail.php?id=<?= $report['id'] ?>" class="btn btn-sm btn-outline-secondary">View</a>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                        <input type="hidden" name="report_action" value="resolve">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">Resolve</button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                                        <input type="hidden" name="report_action" value="delete">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this report?')">Delete</button>
                                                    </form>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });

        // Confirm before critical actions
        document.querySelectorAll('form').forEach(form => {
            if (form.querySelector('input[name="product_action"][value="delete"]') || 
                form.querySelector('input[name="report_action"][value="delete"]')) {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to perform this action?')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>