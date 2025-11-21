<?php
require '../app/config.php';

$statusMessage = '';

/** -------------------------------
 * GENERATE COUPON CODE
 * ------------------------------- */
if (isset($_POST['code'])) {
    // Generate a random 12-character coupon code with dashes (e.g. ABCD-1234-EFGH)
    function generateCouponCode($length = 12)
    {
        $chars = 'ABCDEFGHIJKLMNPQRSTUVWXYZ123456789'; // skip 0 and O
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            if ($i > 0 && $i % 4 === 0) $code .= '-';
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }

    $code = generateCouponCode();
} else {
    $code = '';
}

/** -------------------------------
 * ADD COUPON
 * ------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $seller_id      = (int) $_POST['seller_id'];
    $code           = trim($_POST['code']);
    $discount_type  = trim($_POST['discount_type']);
    $discount_value = (float) $_POST['discount_value'];
    $min_spend      = (float) $_POST['min_spend'];
    $expiry_date    = $_POST['expiry_date'];
    $coupon_type    = trim($_POST['coupon_type']);
    $status         = trim($_POST['status']);
    $max_redemptions = (int) $_POST['max_redemptions'];
    $commission     = (float) $_POST['commission'];

    if (empty($code)) {
        $code = generateCouponCode();
    }

    if ($code && $discount_value && $expiry_date) {
        $stmt = $pdo->prepare("
            INSERT INTO coupons 
            (seller_id, code, discount_type, discount_value, min_spend, expiry_date, coupon_type, status, max_redemptions, commission, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$seller_id, $code, $discount_type, $discount_value, $min_spend, $expiry_date, $coupon_type, $status, $max_redemptions, $commission]);
        $statusMessage = '<div class="alert alert-success">Coupon added successfully</div>';
    } else {
        $statusMessage = '<div class="alert alert-danger">Please fill in all required fields.</div>';
    }
}

/** -------------------------------
 * DELETE COUPON
 * ------------------------------- */
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
    $stmt->execute([$id]);
    $statusMessage = '<div class="alert alert-success">Coupon deleted successfully</div>';
}

/** -------------------------------
 * EDIT COUPON
 * ------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_coupon'])) {
    $id             = (int) $_POST['id'];
    $seller_id      = (int) $_POST['seller_id'];
    $code           = trim($_POST['code']);
    $discount_type  = trim($_POST['discount_type']);
    $discount_value = (float) $_POST['discount_value'];
    $min_spend      = (float) $_POST['min_spend'];
    $expiry_date    = $_POST['expiry_date'];
    $coupon_type    = trim($_POST['coupon_type']);
    $status         = trim($_POST['status']);
    $max_redemptions = (int) $_POST['max_redemptions'];
    $commission     = (float) $_POST['commission'];

    if (!empty($id) && $code && $discount_value && $expiry_date) {
        $stmt = $pdo->prepare("
            UPDATE coupons 
            SET seller_id=?, code=?, discount_type=?, discount_value=?, min_spend=?, expiry_date=?, coupon_type=?, status=?, max_redemptions=?, commission=?, updated_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([$seller_id, $code, $discount_type, $discount_value, $min_spend, $expiry_date, $coupon_type, $status, $max_redemptions, $commission, $id]);
        $statusMessage = '<div class="alert alert-success">Coupon updated successfully</div>';
    } else {
        $statusMessage = '<div class="alert alert-danger">All required fields must be filled.</div>';
    }
}

/** -------------------------------
 * FETCH ALL COUPONS
 * ------------------------------- */
$stmt = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC");
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            <h2>Manage Coupons</h2>
        </div>

        <?php echo $statusMessage; ?>

        <!-- ADD COUPON FORM -->
        <form method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Seller</label>
                    <?php
                    $stmt = $pdo->query("SELECT id, shop_name, name FROM users WHERE role = 'seller' ORDER BY id ASC");
                    $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <select class="form-select" name="seller_id" id="">
                        <?php foreach ($sellers as $s): ?>
                            <option value="<?= $s['id'] ?>">
                                <?= htmlspecialchars($s['name']) ?> (shop name: <?= $s['shop_name'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Code. <span class="text-muted"><small>LEFT EMPTY WILL GENERATE AUTOMATICALLY</small></span></label>
                    <div class="input-group">
                        <input type="text" name="code" id="coupon-code" maxlength="20" class="form-control" placeholder="AB3D-9X2L-4PQZ">
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Discount Type</label>
                    <select name="discount_type" class="form-select">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Discount Value</label>
                    <input type="number" step="0.01" name="discount_value" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Min Spend (₦)</label>
                    <input type="number" step="0.01" name="min_spend" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-2">
                    <label class="form-label">Coupon Type</label>
                    <select name="coupon_type" class="form-select">
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="pending_approval">Pending Approval</option>
                        <option value="pending_payment">Pending Payment</option>
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Max Redemptions</label>
                    <input type="number" name="max_redemptions" class="form-control" value="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Commission</label>
                    <input type="number" step="0.01" name="commission" class="form-control" value="0.00">
                </div>
            </div>

            <button type="submit" name="add_coupon" class="btn btn-primary mt-3">Add Coupon</button>
        </form>

        <!-- COUPONS TABLE -->
        <table class="table table-bordered table-hover dataTable">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Seller</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Min Spend (₦)</th>
                    <th>Expiry</th>
                    <th>Coupon Type</th>
                    <th>Status</th>
                    <th>Max Uses</th>
                    <th>Commission</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $c):
                    $seller = $pdo->query("SELECT name FROM users WHERE id = {$c['seller_id']}")->fetch(PDO::FETCH_ASSOC); ?>
                    <tr>
                        <td><?= htmlspecialchars($c['id']) ?></td>
                        <td><?= htmlspecialchars($seller['name']) ?></td>
                        <td><?= htmlspecialchars($c['code']) ?></td>
                        <td><?= htmlspecialchars($c['discount_type']) ?></td>
                        <td><?= htmlspecialchars($c['discount_value']) ?></td>
                        <td><?= htmlspecialchars($c['min_spend']) ?></td>
                        <td><?= htmlspecialchars($c['expiry_date']) ?></td>
                        <td><?= htmlspecialchars($c['coupon_type']) ?></td>
                        <td><?= htmlspecialchars($c['status']) ?></td>
                        <td><?= htmlspecialchars($c['max_redemptions']) ?></td>
                        <td><?= htmlspecialchars($c['commission']) ?></td>
                        <td><?= htmlspecialchars($c['created_at']) ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm m-1"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-id="<?= $c['id'] ?>"
                                data-seller_id="<?= $c['seller_id'] ?>"
                                data-code="<?= htmlspecialchars($c['code']) ?>"
                                data-discount_type="<?= $c['discount_type'] ?>"
                                data-discount_value="<?= $c['discount_value'] ?>"
                                data-min_spend="<?= $c['min_spend'] ?>"
                                data-expiry_date="<?= $c['expiry_date'] ?>"
                                data-coupon_type="<?= $c['coupon_type'] ?>"
                                data-status="<?= $c['status'] ?>"
                                data-max_redemptions="<?= $c['max_redemptions'] ?>"
                                data-commission="<?= $c['commission'] ?>">
                                Edit
                            </button>
                            <a href="?delete=<?= $c['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this coupon?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Coupon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Seller</label>
                            <select class="form-select" name="seller_id" id="edit-seller_id">
                                <?php
                                $stmt = $pdo->query("SELECT id, shop_name, name FROM users WHERE role = 'seller' ORDER BY id ASC");
                                $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php foreach ($sellers as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (shop name: <?= $s['shop_name'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <div class="input-group">
                                <input type="text" name="code" id="edit-code" maxlength="20" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Type</label>
                            <select name="discount_type" id="edit-discount-type" class="form-select">
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Value</label>
                            <input type="number" step="0.01" name="discount_value" id="edit-discount-value" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Min Spend (₦)</label>
                            <input type="number" step="0.01" name="min_spend" id="edit-min-spend" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Expiry</label>
                            <input type="date" name="expiry_date" id="edit-expiry-date" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-2">
                            <label class="form-label">Coupon Type</label>
                            <select name="coupon_type" id="edit-coupon-type" class="form-select">
                                <option value="online">Online</option>
                                <option value="offline">Offline</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit-status" class="form-select">
                                <option value="pending_approval">Pending Approval</option>
                                <option value="pending_payment">Pending Payment</option>
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Max Redemptions</label>
                            <input type="number" name="max_redemptions" id="edit-max-redemptions" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Commission</label>
                            <input type="number" step="0.01" name="commission" id="edit-commission" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_coupon" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        document.getElementById('editModal').addEventListener('show.bs.modal', event => {
            const btn = event.relatedTarget;
            document.getElementById('edit-id').value = btn.getAttribute('data-id');
            document.getElementById('edit-seller_id').value = btn.getAttribute('data-seller_id');
            document.getElementById('edit-code').value = btn.getAttribute('data-code');
            document.getElementById('edit-discount-type').value = btn.getAttribute('data-discount_type');
            document.getElementById('edit-discount-value').value = btn.getAttribute('data-discount_value');
            document.getElementById('edit-min-spend').value = btn.getAttribute('data-min_spend');
            document.getElementById('edit-expiry-date').value = btn.getAttribute('data-expiry_date');
            document.getElementById('edit-coupon-type').value = btn.getAttribute('data-coupon_type');
            document.getElementById('edit-status').value = btn.getAttribute('data-status');
            document.getElementById('edit-max-redemptions').value = btn.getAttribute('data-max_redemptions');
            document.getElementById('edit-commission').value = btn.getAttribute('data-commission');
        });
    </script>

    <?php include 'includes/script.php'; ?>
</body>

</html>