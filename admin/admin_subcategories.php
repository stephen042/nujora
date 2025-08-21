<?php
require '../app/config.php';

$statusMessage = '';

// Fetch all categories for dropdown
$stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name ASC");
$stmt->execute();
$allCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Add Subcategory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subcategory'])) {
    $category_id = $_POST['category_id'];
    $name = strtoupper(trim($_POST['name']));

    $stmt = $pdo->prepare("INSERT INTO sub_categories (category_id, name) VALUES (?, ?)");
    $stmt->execute([$category_id, $name]);

    $statusMessage = '<div class="alert alert-success">Subcategory added successfully</div>';
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM sub_categories WHERE id = ?");
    $stmt->execute([$id]);

    $statusMessage = '<div class="alert alert-success">Subcategory deleted successfully</div>';
}

// Handle Edit Subcategory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subcategory'])) {
    $id = $_POST['id'];
    $category_id = $_POST['category_id'];
    $name = strtoupper(trim($_POST['name']));

    $stmt = $pdo->prepare("UPDATE sub_categories SET category_id = ?, name = ? WHERE id = ?");
    $stmt->execute([$category_id, $name, $id]);

    $statusMessage = '<div class="alert alert-success">Subcategory updated successfully</div>';
}

// Fetch All Subcategories with Category Names
$stmt = $pdo->prepare("
    SELECT sc.*, c.name AS category_name
    FROM sub_categories sc
    JOIN categories c ON sc.category_id = c.id
    ORDER BY sc.created_at DESC
");
$stmt->execute();
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h2>Manage Subcategories</h2>
        </div>

        <?= $statusMessage; ?>

        <!-- Add Subcategory Form -->
        <form method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label">Parent Category</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($allCategories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Subcategory Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
            </div>
            <hr>
            <button type="submit" name="add_subcategory" class="btn btn-primary w-75 mt-3">Add Subcategory</button>
        </form>

        <!-- Subcategories Table -->
        <table class="table table-bordered table-hover dataTable">
            <thead class="table-dark">
                <tr>
                    <th>SN</th>
                    <th>Category</th>
                    <th>Subcategory</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; ?>
                <?php foreach ($subcategories as $sub): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($sub['category_name']) ?></td>
                        <td><?= htmlspecialchars($sub['name']) ?></td>
                        <td><?= $sub['created_at'] ?></td>
                        <td><?= $sub['updated_at'] ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-id="<?= $sub['id'] ?>"
                                data-category="<?= $sub['category_id'] ?>"
                                data-name="<?= htmlspecialchars($sub['name']) ?>">Edit</button>

                            <a href="?delete=<?= $sub['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this subcategory?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Subcategory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Parent Category</label>
                            <select name="category_id" id="edit-category" class="form-control" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($allCategories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Subcategory Name</label>
                            <input type="text" name="name" id="edit-name" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_subcategory" class="btn btn-success w-100">Save changes</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/script.php'; ?>

    <script>
        document.getElementById('editModal').addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('edit-id').value = button.getAttribute('data-id');
            document.getElementById('edit-category').value = button.getAttribute('data-category');
            document.getElementById('edit-name').value = button.getAttribute('data-name');
        });
    </script>
</body>

</html>