<?php
require '../app/config.php';

$statusMessage = '';

/** -------------------------------
 * ADD BANK DETAILS
 * ------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bank_transfer_details'])) {
    $account_name   = trim($_POST['account_name']);
    $account_number = trim($_POST['account_number']);
    $bank_name      = trim($_POST['bank_name']);

    if (!empty($account_name) && !empty($account_number) && !empty($bank_name)) {
        $stmt = $pdo->prepare("INSERT INTO bank_details (account_name, account_number, bank_name, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$account_name, $account_number, $bank_name]);
        $statusMessage = '<div class="alert alert-success">Bank details added successfully</div>';
    } else {
        $statusMessage = '<div class="alert alert-danger">All fields are required.</div>';
    }
}

/** -------------------------------
 * DELETE BANK DETAILS
 * ------------------------------- */
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM bank_details WHERE id = ?");
    $stmt->execute([$id]);
    $statusMessage = '<div class="alert alert-success">Bank details deleted successfully</div>';
}

/** -------------------------------
 * EDIT BANK DETAILS
 * ------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_bank_transfer_details'])) {
    $id             = (int) $_POST['id'];
    $account_name   = trim($_POST['account_name']);
    $account_number = trim($_POST['account_number']);
    $bank_name      = trim($_POST['bank_name']);

    if (!empty($id) && !empty($account_name) && !empty($account_number) && !empty($bank_name)) {
        $stmt = $pdo->prepare("UPDATE bank_details SET account_name = ?, account_number = ?, bank_name = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$account_name, $account_number, $bank_name, $id]);
        $statusMessage = '<div class="alert alert-success">Bank details updated successfully</div>';
    } else {
        $statusMessage = '<div class="alert alert-danger">All fields are required.</div>';
    }
}

/** -------------------------------
 * FETCH ALL BANK DETAILS
 * ------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM bank_details ORDER BY created_at DESC");
$stmt->execute();
$bankDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h2>Manage Bank Transfer Details</h2>
        </div>

        <?php echo $statusMessage; ?>

        <!-- ADD BANK DETAILS FORM -->
        <form method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-sm-4">
                    <label class="form-label">Account Name</label>
                    <input type="text" name="account_name" class="form-control" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Account Number</label>
                    <input type="text" name="account_number" class="form-control" required>
                </div>
                <div class="col-sm-4">
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="bank_name" class="form-control" required>
                </div>
            </div>
            <button type="submit" name="add_bank_transfer_details" class="btn btn-primary mt-3">Add Bank Details</button>
        </form>

        <!-- BANK DETAILS TABLE -->
        <table class="table table-bordered table-hover dataTable">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Account Name</th>
                    <th>Account Number</th>
                    <th>Bank Name</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bankDetails as $bank): ?>
                    <tr>
                        <td><?= htmlspecialchars($bank['id']) ?></td>
                        <td><?= htmlspecialchars($bank['account_name']) ?></td>
                        <td><?= htmlspecialchars($bank['account_number']) ?></td>
                        <td><?= htmlspecialchars($bank['bank_name']) ?></td>
                        <td><?= htmlspecialchars(date('Y-M-d ', strtotime($bank['created_at']))) ?></td>
                        <td><?= htmlspecialchars(date('Y-M-d', strtotime($bank['updated_at']))) ?></td>

                        <td>
                            <button
                                class="btn btn-warning btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-id="<?= $bank['id'] ?>"
                                data-account_name="<?= htmlspecialchars($bank['account_name']) ?>"
                                data-account_number="<?= htmlspecialchars($bank['account_number']) ?>"
                                data-bank_name="<?= htmlspecialchars($bank['bank_name']) ?>">
                                Edit
                            </button>
                            <a href="?delete=<?= $bank['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this bank detail?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Bank Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="mb-3">
                        <label class="form-label">Account Name</label>
                        <input type="text" name="account_name" id="edit-account-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="account_number" id="edit-account-number" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" id="edit-bank-name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_bank_transfer_details" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/script.php'; ?>

    <script>
        // Fill edit modal
        document.getElementById('editModal').addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('edit-id').value = button.getAttribute('data-id');
            document.getElementById('edit-account-name').value = button.getAttribute('data-account_name');
            document.getElementById('edit-account-number').value = button.getAttribute('data-account_number');
            document.getElementById('edit-bank-name').value = button.getAttribute('data-bank_name');
        });
    </script>
</body>

</html>