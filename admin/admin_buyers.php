<?php
require '../app/config.php'; // include your DB connection (PDO)

// Initialize variables
$statusMessage = '';

// Fetch buyers from DB
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'buyer' ORDER BY created_at DESC");
$buyers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle approve / reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['approve_buyer']) || isset($_POST['reject_buyer']))) {
    $buyerId = intval($_POST['buyer_id'] ?? 0);

    if ($buyerId > 0) {
        try {
            if (isset($_POST['approve_buyer'])) {
                $stmt = $pdo->prepare("UPDATE users SET approval_status = 'approved', is_approved = 1 WHERE id = ? AND role = 'buyer'");
                $stmt->execute([$buyerId]);
                $_SESSION['success'] = "Buyer approved successfully.";
            } elseif (isset($_POST['reject_buyer'])) {
                $stmt = $pdo->prepare("UPDATE users SET approval_status = 'rejected', is_approved = 0 WHERE id = ? AND role = 'buyer'");
                $stmt->execute([$buyerId]);
                $_SESSION['success'] = "Buyer rejected successfully.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating buyer: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Invalid buyer ID.";
    }

    // Redirect back to same page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_buyer'])) {
    $deleteId = intval($_POST['delete_id']);

    try {
        // Check if buyer exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'buyer'");
        $stmt->execute([$deleteId]);
        $buyer = $stmt->fetch();

        if (!$buyer) {
            $_SESSION['error'] = "Buyer not found.";
        } else {
            // Delete buyer
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$deleteId]);

            $_SESSION['success'] = "Buyer deleted successfully.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting buyer: " . $e->getMessage();
    }

    // Refresh same page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<!-- head tag -->
<?php include 'includes/head.php'; ?>

<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <button class="btn btn-outline-secondary d-md-none" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <h2>Admin Dashboard | Buyers</h2>
        </div>

        <?php echo $statusMessage; ?>
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">Buyers List</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <?php $i = 1; ?>
                    <table class="table table-striped table-bordered align-middle dataTable">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($buyers as $buyer): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($buyer['name']) ?></td>
                                    <td><?= htmlspecialchars($buyer['email']) ?></td>
                                    <td><?= htmlspecialchars($buyer['phone'] ?? 'N/A') ?></td>
                                    <td><?= date("M d, Y", strtotime($buyer['created_at'])) ?></td>
                                    <td>
                                        <?php
                                        if ($buyer['approval_status'] == 'pending') {
                                            echo '<span class="badge bg-warning">Pending</span>';
                                        } elseif ($buyer['approval_status'] == 'approved') {
                                            echo '<span class="badge bg-success">Approved</span>';
                                        } elseif ($buyer['approval_status'] == 'rejected') {
                                            echo '<span class="badge bg-danger">Rejected</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="edit_buyer.php?id=<?= urlencode($buyer['id']) ?>"
                                            class="btn btn-sm btn-warning me-1">Edit</a>

                                        <?php if ($buyer['approval_status'] == 'pending'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="buyer_id" value="<?= htmlspecialchars($buyer['id']) ?>">
                                                <button type="submit" name="approve_buyer"
                                                    class="btn btn-sm btn-success me-1"
                                                    onclick="return confirm('Approve this buyer?')">Approve</button>
                                            </form>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="buyer_id" value="<?= htmlspecialchars($buyer['id']) ?>">
                                                <button type="submit" name="reject_buyer"
                                                    class="btn btn-sm btn-danger me-1"
                                                    onclick="return confirm('Reject this buyer?')">Reject</button>
                                            </form>
                                        <?php elseif ($buyer['approval_status'] == 'approved'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="buyer_id" value="<?= htmlspecialchars($buyer['id']) ?>">
                                                <button type="submit" name="reject_buyer"
                                                    class="btn btn-sm btn-danger me-1"
                                                    onclick="return confirm('Reject this buyer?')">Reject</button>
                                            </form>
                                        <?php elseif ($buyer['approval_status'] == 'rejected'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="buyer_id" value="<?= htmlspecialchars($buyer['id']) ?>">
                                                <button type="submit" name="approve_buyer"
                                                    class="btn btn-sm btn-success me-1"
                                                    onclick="return confirm('Approve this buyer?')">Approve</button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="delete_id" value="<?= htmlspecialchars($buyer['id']) ?>">
                                            <button type="submit" name="delete_buyer"
                                                class="btn btn-sm btn-outline-danger my-2"
                                                onclick="return confirm('Are you sure you want to delete this buyer?')">
                                                Delete
                                            </button>
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
    <?php include 'includes/script.php'; ?>

</body>

</html>