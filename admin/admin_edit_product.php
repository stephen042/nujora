<?php
require '../app/config.php';


$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$statusMessage = '';
$uploadDir = '../uploads/';

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found or you don't have permission to edit it.");
}

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll(PDO::FETCH_OBJ);

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $name        = trim($_POST['name']);
    $price       = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $stock       = intval($_POST['stock']);
    $status      = htmlspecialchars($_POST['status']);
    $categoryId  = $_POST['category'] ?? '';
    $subCategoryId = $_POST['sub_category'] ?? '';
    $free_delivery = intval($_POST['free_delivery'] ?? 1);
    $pay_on_delivery = intval($_POST['pay_on_delivery'] ?? 1);

    // Validate category
    $validCategoryIds = array_map(function ($c) {
        return $c->id;
    }, $categories);

    if (!in_array($categoryId, $validCategoryIds)) {
        $statusMessage = '<div class="alert alert-danger">Please select a valid category.</div>';
    } else {
        // Get category name + sellers_fee
        $stmtCat = $pdo->prepare("SELECT name, sellers_fee FROM categories WHERE id = ?");
        $stmtCat->execute([$categoryId]);
        $categoryData = $stmtCat->fetch(PDO::FETCH_ASSOC);

        $categoryName = $categoryData['name'];
        $sellersFee   = floatval($categoryData['sellers_fee']);

        // Get subcategory name (if chosen)
        $subCategoryName = '';
        if (!empty($subCategoryId)) {
            $stmtSub = $pdo->prepare("SELECT name FROM sub_categories WHERE id = ? AND category_id = ?");
            $stmtSub->execute([$subCategoryId, $categoryId]);
            $subCategoryName = $stmtSub->fetchColumn() ?: '';
        }

        // Final price
        $finalPrice = $price + ($price * $sellersFee / 100);

        // Existing images
        $mainImage = $product['image_url'];
        $photos = json_decode($product['photos'], true) ?? [];
        // Handle photo deletions
        if (!empty($_POST['delete_photos'])) {
            foreach ($_POST['delete_photos'] as $photoToDelete) {
                // Remove from the array
                $key = array_search($photoToDelete, $photos);
                if ($key !== false) {
                    unset($photos[$key]);
                    // Optionally delete the actual file
                    if (file_exists($photoToDelete)) {
                        unlink($photoToDelete);
                    }
                }
            }
            // Re-index array to avoid gaps
            $photos = array_values($photos);
        }

        // Update main image if uploaded
        if (!empty($_FILES['product_image']['name']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['product_image']['tmp_name'];
            $fileName = $_FILES['product_image']['name'];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFile = uniqid() . '.' . $ext;
            $destPath = $uploadDir . $newFile;
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $mainImage = $destPath;
            }
        }

        // Add extra photos
        if (!empty($_FILES['product_photos']['name'][0])) {
            foreach ($_FILES['product_photos']['tmp_name'] as $index => $tmpName) {
                if ($_FILES['product_photos']['error'][$index] === UPLOAD_ERR_OK) {
                    $photoName = $_FILES['product_photos']['name'][$index];
                    $ext = pathinfo($photoName, PATHINFO_EXTENSION);
                    $newPhoto = uniqid() . '.' . $ext;
                    $destPhoto = $uploadDir . $newPhoto;
                    if (move_uploaded_file($tmpName, $destPhoto)) {
                        $photos[] = $destPhoto;
                    }
                }
            }
        }

        // Update DB
        try {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET name=?, price=?, description=?, stock=?, status=?, category=?, sub_category=?, image_url=?, photos=?, free_delivery=?, pay_on_delivery=?
                WHERE id=?
            ");

            $stmt->execute([
                $name,
                $finalPrice,
                $description,
                $stock,
                $status,
                $categoryName,
                $subCategoryName,
                $mainImage,
                json_encode($photos),
                $free_delivery,
                $pay_on_delivery,
                $product_id
            ]);

            $statusMessage = '<div class="alert alert-success">Product updated successfully!</div>';
            // Refresh product after update
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $statusMessage = '<div class="alert alert-danger">Error updating: ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
    <div class="container mt-4 mb-5">
        <a href="admin-dashboard.php" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left"></i> Back to Products</a>
        <hr>
        <h4>Edit Product</h4>
        <?= $statusMessage; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Product Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <!-- Category -->
            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category" id="categorySelect" class="form-select" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat->id ?>" data-fee="<?= $cat->sellers_fee ?>"
                            <?= $cat->id == $product['category'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat->name) ?> (Fee: <?= $cat->sellers_fee ?>%)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Sub Category </label>
                <span class="badge bg-info text-white me-2">(<?= htmlspecialchars($product['sub_category']) ?>)</span>
                <select name="sub_category" id="subCategorySelect" class="form-select">
                    <option value="<?= $product['sub_category'] ?>"><?= $product['sub_category'] ?></option>
                </select>
            </div>

            <!-- Price -->
            <div class="row mb-3 text-white">
                <div class="col-md-6 bg-info p-1 rounded m-1">
                    <label class="form-label">Price (₦)</label>
                    <input type="number" id="priceInput" name="price" class="form-control" step="0.01"
                        value="<?= htmlspecialchars($product['price']) ?>" required>
                </div>
                <div class="col-md-5 bg-success p-1 rounded m-1">
                    <label class="form-label">Final Price (Price customer will see) (₦)</label>
                    <input type="text" id="finalPrice" class="form-control" value="<?= htmlspecialchars($product['price']) ?>" readonly>
                </div>
            </div>

            <!-- Free Delivery & Pay on Delivery -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Free Delivery</label>
                    <select class="form-select" name="free_delivery">
                        <option value="1" <?= $product['free_delivery'] == 1 ? 'selected' : '' ?>>False</option>
                        <option value="2" <?= $product['free_delivery'] == 2 ? 'selected' : '' ?>>True</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Pay on Delivery</label>
                    <select class="form-select" name="pay_on_delivery">
                        <option value="1" <?= $product['pay_on_delivery'] == 1 ? 'selected' : '' ?>>False</option>
                        <option value="2" <?= $product['pay_on_delivery'] == 2 ? 'selected' : '' ?>>True</option>
                    </select>
                </div>
            </div>


            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="6"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Stock</label>
                <input type="number" name="stock" class="form-control" value="<?= htmlspecialchars($product['stock']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="In Stock" <?= $product['status'] === 'In Stock' ? 'selected' : '' ?>>In Stock</option>
                    <option value="Out of Stock" <?= $product['status'] === 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>

            <!-- Main Image -->
            <div class="mb-3">
                <label class="form-label">Main Image</label><br>
                <img src="<?= $product['image_url'] ?>" class="img-thumbnail mb-2" style="max-width:120px;">
                <input type="file" name="product_image" class="form-control" accept="image/*" onchange="previewMainImage(event)">
                <div id="mainImagePreview" class="mt-2"></div>
            </div>

            <!-- Extra Photos -->
            <div class="mb-3">
                <label class="form-label">Extra Photos</label><br>
                <?php foreach (json_decode($product['photos'], true) ?? [] as $index => $photo): ?>
                    <div class="d-inline-block text-center me-2 mb-2" style="position: relative;">
                        <img src="<?= htmlspecialchars($photo) ?>" class="img-thumbnail" style="max-width:100px;">
                        <div>
                            <label class="form-check-label small">
                                <input type="checkbox" name="delete_photos[]" value="<?= htmlspecialchars($photo) ?>">
                                Delete
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
                <input type="file" name="product_photos[]" class="form-control mt-2" multiple accept="image/*" onchange="previewMultipleImages(event)">
                <div id="multiImagePreview" class="mt-2 d-flex flex-wrap"></div>
            </div>

            <button type="submit" name="update" class="btn btn-primary w-100">Update Product</button>
        </form>
    </div>

    <script>
        // Fetch subcategories dynamically
        document.getElementById('categorySelect').addEventListener('change', function() {
            let categoryId = this.value;
            let fee = parseFloat(this.selectedOptions[0].getAttribute('data-fee')) || 0;
            document.getElementById('priceInput').dataset.fee = fee;
            updateFinalPrice();

            fetch('fetch_subcategories.php?category_id=' + categoryId)
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.json();
                })
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
            let finalPrice = Math.ceil(price + (price * fee / 100));
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