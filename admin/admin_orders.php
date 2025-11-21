<?php
require '../app/config.php';

// status change handler
$statusMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = trim($_POST['status']);

    // update order
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);

    // insert into status history
    $stmt = $pdo->prepare("
        INSERT INTO order_status_history (order_id, status, changed_by) 
        VALUES (?, ?, 'admin')
    ");
    $stmt->execute([$orderId, $newStatus]);

    $statusMessage = "<div class='alert alert-success'>Order status updated successfully.</div>";
}

// fetch all orders
$orders = $pdo->query("
    SELECT o.*, u.name AS buyer_name
    FROM orders o
    LEFT JOIN users u ON u.id = o.buyer_id
    ORDER BY o.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<?php include 'includes/head.php'; ?>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <button class="btn btn-outline-secondary d-md-none" id="sidebarToggle">
            <i class="bi bi-list"></i>
            </button>
            <h2><i class="bi bi-cart me-2"></i>Manage Orders</h2>
        </div>

        <?= $statusMessage; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Buyer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o => $order): ?>
                            <tr>
                                <td><?=$o+1; ?></td>
                                <td><?=htmlspecialchars($order['buyer_name']); ?></td>
                                <td>$<?=number_format($order['total'], 2); ?></td>
                                <td><?=ucfirst($order['payment_method']); ?></td>
                                <td>
                                    <?php
                                    $status = $order['status'];
                                    $badgeClass = 'bg-secondary';
                                    switch ($status) {
                                        case 'pending':
                                            $badgeClass = 'bg-warning';
                                            break;
                                        case 'processing':
                                            $badgeClass = 'bg-info';
                                            break;
                                        case 'shipped':
                                            $badgeClass = 'bg-primary';
                                            break;
                                        case 'completed':
                                            $badgeClass = 'bg-success';
                                            break;
                                        case 'cancelled':
                                            $badgeClass = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?=$badgeClass; ?>">
                                        <?=ucfirst($status); ?>
                                    </span>
                                </td>
                                <td><?=date("d M Y", strtotime($order['order_date'])); ?></td>
                                <td>
                                    <button
                                        class="btn btn-sm btn-info view-details"
                                        data-id="<?=$order['id']; ?>">
                                        <i class="bi bi-eye text-white"></i>
                                    </button>

                                    <button
                                        class="btn btn-sm btn-primary update-status"
                                        data-id="<?=$order['id']; ?>"
                                        data-status="<?=$order['status']; ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- VIEW ORDER MODAL -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="bi bi-info-circle"></i> Order Details</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="order-details-content">
                    Loading...
                </div>
            </div>
        </div>
    </div>

    <!-- UPDATE STATUS MODAL -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-eye"></i> Update Status</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="order_id" id="update_order_id">

                    <label class="form-label fw-bold">Select New Status:</label>
                    <select name="status" id="update_status" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button name="update_status" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/script.php'; ?>

    <!-- AJAX SCRIPTS -->
    <script>
        document.querySelectorAll('.view-details').forEach(btn => {
            btn.addEventListener('click', function() {
                let orderId = this.dataset.id;

                fetch('order_details_ajax.php?id=' + orderId)
                    .then(res => res.text())
                    .then(data => {
                        document.getElementById('order-details-content').innerHTML = data;
                        new bootstrap.Modal(document.getElementById('viewOrderModal')).show();
                    });
            });
        });

        document.querySelectorAll('.update-status').forEach(btn => {
            btn.addEventListener('click', function() {
                let orderId = this.dataset.id;
                let currentStatus = this.dataset.status;

                document.getElementById('update_order_id').value = orderId;
                document.getElementById('update_status').value = currentStatus;

                new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
            });
        });
    </script>

</body>

</html>