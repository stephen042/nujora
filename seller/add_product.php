<?php
require '../app/config.php';

// Check if the seller is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit;
}

$statusMessage = '';
$uploadDir = '../uploads/';

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll(PDO::FETCH_OBJ);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = floatval($_POST['price']);
    $description = $_POST['description'];
    $stock = intval($_POST['stock']);
    $status = htmlspecialchars($_POST['status']);
    $categoryId = $_POST['category'] ?? '';
    $subCategoryId = $_POST['sub_category'] ?? '';
    $sellerId = $_SESSION['user_id'];

    // Validate category
    $validCategoryIds = array_map(fn($c) => $c->id, $categories);
    if (!in_array($categoryId, $validCategoryIds)) {
        $statusMessage = '<div class="alert alert-danger">Please select a valid category.</div>';
    } else {
        // Get category name + sellers_fee
        $stmtCat = $pdo->prepare("SELECT name, sellers_fee FROM categories WHERE id = ?");
        $stmtCat->execute([$categoryId]);
        $categoryData = $stmtCat->fetch(PDO::FETCH_ASSOC);

        $categoryName = $categoryData['name'];
        $sellersFee = floatval($categoryData['sellers_fee']);

        // Get subcategory name (if chosen)
        $subCategoryName = '';
        if (!empty($subCategoryId)) {
            $stmtSub = $pdo->prepare("SELECT name FROM sub_categories WHERE id = ? AND category_id = ?");
            $stmtSub->execute([$subCategoryId, $categoryId]);
            $subCategoryName = $stmtSub->fetchColumn() ?: '';
        }

        // Calculate final price (rounded up to nearest whole number)
        $finalPrice = ceil($price + ($price * $sellersFee / 100));

        // Handle main image upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['product_image']['tmp_name'];
            $fileName = $_FILES['product_image']['name'];
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid() . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Handle multiple photos
                $uploadedPhotos = [];
                if (!empty($_FILES['product_photos']['name'][0])) {
                    foreach ($_FILES['product_photos']['tmp_name'] as $index => $tmpName) {
                        if ($_FILES['product_photos']['error'][$index] === UPLOAD_ERR_OK) {
                            $photoName = $_FILES['product_photos']['name'][$index];
                            $photoExt = pathinfo($photoName, PATHINFO_EXTENSION);
                            $photoNewName = uniqid() . '.' . $photoExt;
                            $photoDest = $uploadDir . $photoNewName;

                            if (move_uploaded_file($tmpName, $photoDest)) {
                                $uploadedPhotos[] = $photoDest;
                            }
                        }
                    }
                }

                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO products (name, price, description, stock, status, seller_id, category, sub_category, image_url, photos) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $name,
                        $finalPrice, // ✅ Rounded price saved
                        $description,
                        $stock,
                        $status,
                        $sellerId,
                        $categoryName,
                        $subCategoryName,
                        $destPath,
                        json_encode($uploadedPhotos)
                    ]);

                    $statusMessage = '<div class="alert alert-success">Product uploaded successfully!</div>';
                    $_POST = [];
                } catch (PDOException $e) {
                    $statusMessage = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
                }
            } else {
                $statusMessage = '<div class="alert alert-danger">Failed to upload the main image. Please try again.</div>';
            }
        } else {
            $statusMessage = '<div class="alert alert-danger">Please upload a valid main image file.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .nav-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #fff;
            border-top: 1px solid #dee2e6;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
            z-index: 1000;
        }

        .nav-bottom .nav-link {
            padding: 0.75rem;
            font-size: 0.8rem;
            color: #495057;
        }

        .nav-bottom .nav-link.active {
            color: #6a11cb;
            font-weight: 600;
        }

        body {
            padding-bottom: 60px;
        }

        .ck-editor__editable_inline {
            min-height: 300px;
            max-height: 500px;
            overflow-y: auto;
        }

        .ck-editor__editable_inline[role="textbox"] {
            min-height: 300px !important;
        }
    </style>
</head>

<body>
    <div class="container mt-4 mb-5">
        <h4>Upload New Product</h4>
        <?= $statusMessage; ?>
        <a href="seller-dashboard.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
        <form method="POST" action="add_product.php" enctype="multipart/form-data">

            <div class="mb-3">
                <label class="form-label">Product Name</label>
                <input
                    type="text"
                    name="name"
                    class="form-control"
                    placeholder="e.g. Bluetooth Speaker"
                    required
                    value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
            </div>

            <!-- Category + Subcategory -->
            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category" id="categorySelect" class="form-select" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat->id) ?>"
                            data-fee="<?= htmlspecialchars($cat->sellers_fee) ?>"
                            <?= (isset($_POST['category']) && $_POST['category'] == $cat->id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat->name) ?> (Fee: <?= $cat->sellers_fee ?>%)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Sub Category</label>
                <select name="sub_category" id="subCategorySelect" class="form-select" required>
                    <option value="">Select a sub-category</option>
                    <?php if (!empty($subcategories)): ?>
                        <?php foreach ($subcategories as $sub): ?>
                            <option value="<?= htmlspecialchars($sub->id) ?>"
                                <?= (isset($_POST['sub_category']) && $_POST['sub_category'] == $sub->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sub->name) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Price + Final Price -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Price (₦)</label>
                    <input
                        type="number"
                        step="0.01"
                        name="price"
                        id="priceInput"
                        class="form-control"
                        placeholder="e.g. 8500"
                        required
                        value="<?= isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Final Price (With Fee %)</label>
                    <input type="text" id="finalPrice" class="form-control" readonly>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea
                    name="description"
                    class="form-control"
                    rows="9"
                    placeholder="Enter product description"
                    required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Stock Quantity</label>
                <input
                    type="number"
                    name="stock"
                    class="form-control"
                    min="0"
                    required
                    value="<?= isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '' ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="In Stock" <?= (isset($_POST['status']) && $_POST['status'] === 'In Stock') ? 'selected' : '' ?>>In Stock</option>
                    <option value="Out of Stock" <?= (isset($_POST['status']) && $_POST['status'] === 'Out of Stock') ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>

            <!-- Main image -->
            <div class="mb-3">
                <label class="form-label">Upload Main Image</label>
                <input type="file" name="product_image" class="form-control" accept="image/*" required onchange="previewMainImage(event)">
                <div id="mainImagePreview" class="mt-2"></div>
            </div>

            <!-- Multiple images -->
            <div class="mb-3">
                <label class="form-label">Upload Extra Photos of your product</label>
                <input type="file" name="product_photos[]" class="form-control" accept="image/*" multiple onchange="previewMultipleImages(event)">
                <div id="multiImagePreview" class="mt-2 d-flex flex-wrap"></div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Submit</button>
        </form>
    </div>

    <!-- Seller navigation -->
    <nav class="nav nav-pills nav-fill nav-bottom">
        <a class="nav-link" href="seller-dashboard.php"><i class="bi bi-house"></i> Dashboard</a>
        <a class="nav-link" href="seller-products.php"><i class="bi bi-box-seam"></i> My Products</a>
        <a class="nav-link" href="seller-orders.php"><i class="bi bi-receipt"></i> Orders</a>
        <a class="nav-link active" href="add_product.php"><i class="bi bi-plus-circle"></i> Add Product</a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <script>
        // Fetch subcategories dynamically
        document.getElementById('categorySelect').addEventListener('change', function() {
            let categoryId = this.value;
            let fee = parseFloat(this.selectedOptions[0].getAttribute('data-fee')) || 0;
            document.getElementById('priceInput').dataset.fee = fee;
            updateFinalPrice();

            fetch('fetch_subcategories.php?category_id=' + categoryId)
                .then(res => res.json())
                .then(data => {
                    let subCatSelect = document.getElementById('subCategorySelect');
                    subCatSelect.innerHTML = '<option value="">Select a sub-category</option>';
                    data.forEach(sub => {
                        let option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name;
                        subCatSelect.appendChild(option);
                    });
                });
        });

        // Update final price
        document.getElementById('priceInput').addEventListener('input', updateFinalPrice);

        function updateFinalPrice() {
            let price = parseFloat(document.getElementById('priceInput').value) || 0;
            let fee = parseFloat(document.getElementById('priceInput').dataset.fee) || 0;
            let finalPrice = Math.ceil(price + (price * fee / 100)); // ✅ round up
            document.getElementById('finalPrice').value = finalPrice > 0 ? finalPrice : '';
        }

        // Preview main image
        function previewMainImage(event) {
            const preview = document.getElementById('mainImagePreview');
            preview.innerHTML = '';
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width: 150px;">`;
                }
                reader.readAsDataURL(file);
            }
        }

        // Preview multiple images
        function previewMultipleImages(event) {
            const preview = document.getElementById('multiImagePreview');
            preview.innerHTML = '';
            const files = event.target.files;
            Array.from(files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('img-thumbnail', 'me-2', 'mb-2');
                    img.style.maxWidth = '120px';
                    preview.appendChild(img);
                }
                reader.readAsDataURL(file);
            });
        }
    </script>
</body>

</html>