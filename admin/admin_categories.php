<?php
require '../app/config.php';

$statusMessage = '';
$uploadDir = __DIR__ . '/../uploads/categories/'; // Physical path
$uploadUrl = '/uploads/categories/'; // Public URL path

// Ensure upload folder exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = strtoupper(trim($_POST['name']));
    $sellers_fee = $_POST['sellers_fee'];
    $imagePath = '';

    if (!empty($_FILES['image_file']['name'])) {
        $fileName = time() . '_' . basename($_FILES['image_file']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetFile)) {
            $imagePath = $uploadUrl . $fileName;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO categories (name, sellers_fee, image_url) VALUES (?, ?, ?)");
    $stmt->execute([$name, $sellers_fee, $imagePath]);
    $statusMessage = '<div class="alert alert-success">Category added successfully</div>';
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Optional: delete image file from server
    $stmt = $pdo->prepare("SELECT image_url FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cat && !empty($cat['image_url'])) {
        $filePath = $uploadDir . basename($cat['image_url']);
        if (file_exists($filePath)) unlink($filePath);
    }

    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $statusMessage = '<div class="alert alert-success">Category deleted successfully</div>';
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = $_POST['id'];
    $name = strtoupper(trim($_POST['name']));
    $sellers_fee = $_POST['sellers_fee'];

    // Get old image
    $stmt = $pdo->prepare("SELECT image_url FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
    $imagePath = $oldData['image_url'];

    // If new file uploaded
    if (!empty($_FILES['edit_image_file']['name'])) {
        // Delete old image
        if (!empty($imagePath)) {
            $oldPath = $uploadDir . basename($imagePath);
            if (file_exists($oldPath)) unlink($oldPath);
        }
        $fileName = time() . '_' . basename($_FILES['edit_image_file']['name']);
        $targetFile = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['edit_image_file']['tmp_name'], $targetFile)) {
            $imagePath = $uploadUrl . $fileName;
        }
    }

    $stmt = $pdo->prepare("UPDATE categories SET name = ?, sellers_fee = ?, image_url = ? WHERE id = ?");
    $stmt->execute([$name, $sellers_fee, $imagePath, $id]);
    $statusMessage = '<div class="alert alert-success">Category updated successfully</div>';
}

// Fetch All Categories
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY created_at DESC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h2>Manage Categories</h2>
        </div>

        <?php echo $statusMessage; ?>

        <!-- Add Category Form -->
        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <div class="row g-3">
                <div class="col-sm-6">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-sm-6">
                    <label for="edit-fee" class="form-label">Seller's Fee</label>
                    <div class="input-group">
                        <input type="number" step="0.01" name="sellers_fee" id="edit-fee" class="form-control" required>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label class="form-label">Upload Image</label>
                    <input type="file" name="image_file" class="form-control" accept="image/*" onchange="previewFile(this,'add-preview')">
                </div>
                <div class="col-sm-6 text-center">
                    <img id="add-preview" src="" alt="Preview" style="max-width:150px; display:none;">
                </div>
            </div>
            <hr>
            <button type="submit" name="add_category" class="btn btn-primary w-75 mt-3">Add Category</button>
        </form>

        <!-- Categories Table -->
        <table id="categoriesTable" class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Seller's Fee</th>
                    <th>Image</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?= htmlspecialchars($cat['id']) ?></td>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><?= htmlspecialchars($cat['sellers_fee']) ?></td>
                        <td>
                            <?php if (!empty($cat['image_url'])): ?>
                                <img src="<?= htmlspecialchars($cat['image_url']) ?>" alt="" width="50">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($cat['created_at']) ?></td>
                        <td><?= htmlspecialchars($cat['updated_at']) ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-id="<?= $cat['id'] ?>"
                                data-name="<?= htmlspecialchars($cat['name']) ?>"
                                data-fee="<?= htmlspecialchars($cat['sellers_fee']) ?>"
                                data-image="<?= htmlspecialchars($cat['image_url']) ?>">Edit</button>

                            <a href="?delete=<?= $cat['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this category?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit-id">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="name" id="edit-name" class="form-control" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="edit-fee" class="form-label">Seller's Fee</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="sellers_fee" id="edit-fee" class="form-control" required>
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label">Upload New Image</label>
                            <input type="file" name="edit_image_file" class="form-control" accept="image/*" onchange="previewFile(this,'edit-preview')">
                        </div>
                        <div class="col-sm-6 text-center">
                            <img id="edit-preview" src="" alt="Preview" style="max-width:150px; display:none;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_category" class="btn btn-success w-100">Save changes</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/script.php'; ?>

    <script>
        function previewFile(input, imgId) {
            const file = input.files[0];
            const img = document.getElementById(imgId);
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    img.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                img.src = '';
                img.style.display = 'none';
            }
        }

        // Fill edit modal
        document.getElementById('editModal').addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            document.getElementById('edit-id').value = button.getAttribute('data-id');
            document.getElementById('edit-name').value = button.getAttribute('data-name');
            document.getElementById('edit-fee').value = button.getAttribute('data-fee');

            const currentImage = button.getAttribute('data-image');
            const preview = document.getElementById('edit-preview');
            if (currentImage) {
                preview.src = currentImage;
                preview.style.display = 'block';
            } else {
                preview.src = '';
                preview.style.display = 'none';
            }
        });
    </script>
</body>

</html>