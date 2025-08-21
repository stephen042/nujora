<?php
require '../app/config.php';

$statusMessage = '';

/** -------------------------------
 * APPROVE OR DENY TRANSACTION
 * ------------------------------- */
if (isset($_GET['action']) && isset($_GET['tx_ref'])) {
    $tx_ref = $_GET['tx_ref'];
    $action = $_GET['action'];

    if (in_array($action, ['approved', 'denied', 'refunded'])) {
        $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE transaction_reference = ?");
        $stmt->execute([$action, $tx_ref]);
        $statusMessage = "<div class='alert alert-success'>Transaction marked as $action successfully</div>";
    }
}


/** -------------------------------
 * FETCH TRANSACTIONS WITH USER + PROOF
 * ------------------------------- */
$stmt = $pdo->prepare("
    SELECT 
        t.id,
        t.transaction_reference,
        t.amount,
        t.status,
        t.payment_method,
        t.created_at,
        u.id as user_id,
        u.name as user_name,
        u.email as user_email,
        p.proof_path as proof_image
    FROM transactions t
    LEFT JOIN orders o ON t.transaction_reference = o.transaction_reference
    LEFT JOIN users u ON o.buyer_id = u.id
    LEFT JOIN proof_of_payment p ON t.transaction_reference = p.transaction_reference
    ORDER BY t.created_at DESC
");
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<?php include 'includes/head.php'; ?>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Transactions</h2>
        </div>

        <?php echo $statusMessage; ?>

        <!-- TRANSACTIONS TABLE -->
        <table class="table table-bordered table-hover dataTable">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Customer's Name</th>
                    <th>Customer's Email</th>
                    <th>Transaction Reference</th>
                    <th>Payment Method</th>
                    <th>Amount</th>
                    <th>Proof Image</th>
                    <th>Created On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td><?= htmlspecialchars($tx['id']) ?></td>
                        <td>
                            <?php if ($tx['status'] === 'pending'): ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php elseif ($tx['status'] === 'approved'): ?>
                                <span class="badge bg-success">Approved</span>
                            <?php elseif ($tx['status'] === 'refunded'): ?>
                                <span class="badge bg-info text-dark">Refunded</span>
                            <?php elseif ($tx['status'] === 'denied'): ?>
                                <span class="badge bg-danger">Denied</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Unknown</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($tx['user_name'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($tx['user_email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($tx['transaction_reference']) ?></td>
                        <td><?= htmlspecialchars($tx['payment_method']) ?></td>
                        <td>$<?= htmlspecialchars(number_format($tx['amount'], 2)) ?></td>
                        <td>
                            <?php if (!empty($tx['proof_image'])): ?>
                                <a href="<?= htmlspecialchars($tx['proof_image']) ?>" target="_blank">
                                    <img src="<?= htmlspecialchars($tx['proof_image']) ?>" alt="Proof" width="60">
                                </a>
                            <?php else: ?>
                                <span class="text-muted">No Proof</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($tx['created_at']))) ?></td>
                        <td>
                            <?php if ($tx['status'] === 'pending'): ?>
                                <a href="?action=approved&tx_ref=<?= urlencode($tx['transaction_reference']) ?>"
                                    class="btn btn-success btn-sm">Approve</a>
                                <a href="?action=denied&tx_ref=<?= urlencode($tx['transaction_reference']) ?>"
                                    class="btn btn-danger btn-sm">Deny</a>
                            <?php elseif ($tx['status'] === 'approved'): ?>
                                <a href="?action=refunded&tx_ref=<?= urlencode($tx['transaction_reference']) ?>"
                                    class="btn btn-info btn-sm">Refund</a>
                            <?php else: ?>
                                <span class="text-muted">No actions</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php include 'includes/script.php'; ?>
</body>

</html>