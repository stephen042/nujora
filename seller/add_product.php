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
    // Basic product fields
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = $_POST['description'];
    $stock = intval($_POST['stock']); // general stock (optional)
    $status = htmlspecialchars($_POST['status']);
    $categoryId = $_POST['category'] ?? '';
    $subCategoryId = $_POST['sub_category'] ?? '';
    $sellerId = $_SESSION['user_id'];

    // Validate category
    $validCategoryIds = array_map(function ($c) {
        return $c->id;
    }, $categories);
    if (!in_array($categoryId, $validCategoryIds)) {
        $statusMessage = '<div class="alert alert-danger">Please select a valid category.</div>';
    } else {
        // Category info
        $stmtCat = $pdo->prepare("SELECT name, sellers_fee FROM categories WHERE id = ?");
        $stmtCat->execute([$categoryId]);
        $categoryData = $stmtCat->fetch(PDO::FETCH_ASSOC);

        $categoryName = $categoryData['name'];
        $sellersFee = floatval($categoryData['sellers_fee']);

        // Subcategory name if chosen
        $subCategoryName = '';
        if (!empty($subCategoryId)) {
            $stmtSub = $pdo->prepare("SELECT name FROM sub_categories WHERE id = ? AND category_id = ?");
            $stmtSub->execute([$subCategoryId, $categoryId]);
            $subCategoryName = $stmtSub->fetchColumn() ?: '';
        }

        // Final price calculation (product-level price)
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
                    // Begin transaction (we'll insert product + attributes + variants atomically)
                    $pdo->beginTransaction();

                    $slug = generateSlug($name);
                    $stmt = $pdo->prepare("
                        INSERT INTO products (slug, name, price, original_price, description, stock, status, seller_id, category, sub_category, image_url, photos) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $slug,
                        $name,
                        $finalPrice,
                        $price,
                        $description,
                        $stock,
                        $status,
                        $sellerId,
                        $categoryName,
                        $subCategoryName,
                        $destPath,
                        json_encode($uploadedPhotos)
                    ]);

                    $productId = $pdo->lastInsertId();

                    // ---- Save product attributes (optional but useful) ----
                    // We expect the frontend to also send product-level attributes (names + values) as JSON (optional),
                    // but even if not, we'll still save variants below.
                    // If the frontend sent attribute metadata:
                    if (!empty($_POST['attributes_meta'])) {
                        // attributes_meta is JSON string: [{name: "size", values: ["40","41"]}, {...}]
                        $attributesMeta = json_decode($_POST['attributes_meta'], true);
                        if (is_array($attributesMeta)) {
                            $stmtAttr = $pdo->prepare("INSERT INTO product_attributes (product_id, attribute_name) VALUES (?, ?)");
                            $stmtAttrVal = $pdo->prepare("INSERT INTO product_attribute_values (attribute_id, value) VALUES (?, ?)");
                            foreach ($attributesMeta as $attr) {
                                if (empty($attr['name']) || empty($attr['values']) || !is_array($attr['values'])) continue;
                                $attrName = trim($attr['name']);
                                $stmtAttr->execute([$productId, $attrName]);
                                $attributeId = $pdo->lastInsertId();
                                foreach ($attr['values'] as $val) {
                                    $val = trim($val);
                                    if ($val === '') continue;
                                    $stmtAttrVal->execute([$attributeId, $val]);
                                }
                            }
                        }
                    }

                    // ---- Save variants generated by frontend ----
                    // frontend supplies variant_options[] (JSON) and variant_stock[] and optional variant_sku[]
                    if (!empty($_POST['variant_options']) && is_array($_POST['variant_options'])) {
                        $stmtVar = $pdo->prepare("INSERT INTO product_variants (product_id, sku, stock) VALUES (?, ?, ?)");
                        $stmtVarOpt = $pdo->prepare("INSERT INTO product_variant_options (variant_id, option_name, option_value) VALUES (?, ?, ?)");

                        foreach ($_POST['variant_options'] as $i => $variantJson) {
                            $variantData = json_decode($variantJson, true);
                            if (!is_array($variantData)) continue;

                            $vStock = isset($_POST['variant_stock'][$i]) ? intval($_POST['variant_stock'][$i]) : 0;
                            $vSku = isset($_POST['variant_sku'][$i]) ? trim($_POST['variant_sku'][$i]) : null;

                            $stmtVar->execute([$productId, $vSku, $vStock]);
                            $variantId = $pdo->lastInsertId();

                            // $variantData is associative array: ['size'=>'42', 'color'=>'Black', ...]
                            foreach ($variantData as $optName => $optValue) {
                                $optName = trim($optName);
                                $optValue = trim($optValue);
                                if ($optName === '' || $optValue === '') continue;
                                $stmtVarOpt->execute([$variantId, $optName, $optValue]);
                            }
                        }
                    }

                    $pdo->commit();

                    $statusMessage = '<div class="alert alert-success">Product uploaded successfully!</div>';
                    $_POST = [];
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $statusMessage = '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
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
            padding: .75rem;
            font-size: .8rem;
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
    </style>
</head>

<body>
    <div class="container mt-4 mb-5">
        <h4>Upload New Product</h4>
        <?= $statusMessage; ?>
        <a href="seller-dashboard.php" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>

        <form method="POST" action="add_product.php" enctype="multipart/form-data" id="addProductForm">
            <!-- Product basic fields (same as before) -->
            <div class="mb-3">
                <label class="form-label">Product Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Bluetooth Speaker" required value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
            </div>

            <!-- Category + Subcategory -->
            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category" id="categorySelect" class="form-select" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat->id) ?>" data-fee="<?= htmlspecialchars($cat->sellers_fee) ?>" <?= (isset($_POST['category']) && $_POST['category'] == $cat->id) ? 'selected' : '' ?>>
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
                            <option value="<?= htmlspecialchars($sub->id) ?>" <?= (isset($_POST['sub_category']) && $_POST['sub_category'] == $sub->id) ? 'selected' : '' ?>><?= htmlspecialchars($sub->name) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Price + Final Price -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Original Price (₦)</label>
                    <input type="number" step="0.01" name="price" id="priceInput" class="form-control" placeholder="e.g. 8500" required value="<?= isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '' ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Final Price (With Fee %)</label>
                    <input type="text" id="finalPrice" class="form-control" readonly>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="6" placeholder="Enter product description" required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Stock Quantity (General)</label>
                <input type="number" name="stock" class="form-control" min="0" value="<?= isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '' ?>">
                <small class="text-muted">Optional — variants can have their own stock per combination.</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="In Stock" <?= (isset($_POST['status']) && $_POST['status'] === 'In Stock') ? 'selected' : '' ?>>In Stock</option>
                    <option value="Out of Stock" <?= (isset($_POST['status']) && $_POST['status'] === 'Out of Stock') ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>

            <!-- Images -->
            <div class="mb-3">
                <label class="form-label">Upload Main Image</label>
                <input type="file" name="product_image" class="form-control" accept="image/*" required onchange="previewMainImage(event)">
                <div id="mainImagePreview" class="mt-2"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Upload Extra Photos of your product</label>
                <input type="file" name="product_photos[]" class="form-control" accept="image/*" multiple onchange="previewMultipleImages(event)">
                <div id="multiImagePreview" class="mt-2 d-flex flex-wrap"></div>
            </div>

            <!-- ======= Unlimited Attributes UI ======= -->
            <h5 class="mt-4">Product Options / Attributes (e.g. size, color, storage)</h5>
            <p class="text-muted">Add attribute name and comma-separated values. Then click Generate Variants to auto-create every combination.</p>

            <div id="attributesArea"></div>

            <button type="button" class="btn btn-outline-secondary mb-3" onclick="addAttributeRow()">
                + Add Option (e.g. size)
            </button>

            <div class="mb-3">
                <button type="button" class="btn btn-primary" onclick="generateVariants()">Generate Variants</button>
            </div>

            <!-- Variants table will be injected here -->
            <div id="variantsArea"></div>

            <!-- Hidden: attributes metadata (JSON) & variants (array of JSONs) will be posted -->
            <input type="hidden" name="attributes_meta" id="attributesMetaInput" value="">
            <!-- variant_options[] and variant_stock[] and variant_sku[] are added dynamically to the form -->

            <hr class="my-4">
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
        // ---------- Price / fee helpers (preserve your earlier behavior) ----------
        document.getElementById('categorySelect').addEventListener('change', function() {
            let fee = parseFloat(this.selectedOptions[0].getAttribute('data-fee')) || 0;
            document.getElementById('priceInput').dataset.fee = fee;
            updateFinalPrice();

            let categoryId = this.value;
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

        document.getElementById('priceInput').addEventListener('input', updateFinalPrice);

        function updateFinalPrice() {
            let price = parseFloat(document.getElementById('priceInput').value) || 0;
            let fee = parseFloat(document.getElementById('priceInput').dataset.fee) || 0;
            let finalPrice = Math.ceil(price + (price * fee / 100));
            document.getElementById('finalPrice').value = finalPrice > 0 ? finalPrice : '';
        }

        // ---------- Image previews ----------
        function previewMainImage(event) {
            const preview = document.getElementById('mainImagePreview');
            preview.innerHTML = '';
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width:150px;">`;
                }
                reader.readAsDataURL(file);
            }
        }

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

        // ---------- Attributes + Variants UI & logic ----------
        let attrIndex = 0;

        function addAttributeRow(name = '', values = '') {
            const area = document.getElementById('attributesArea');
            const row = document.createElement('div');
            row.classList.add('row', 'mb-2', 'align-items-end');
            row.dataset.index = attrIndex;

            row.innerHTML = `
            <div class="col-md-5">
                <label>Option Name</label>
                <input class="form-control" name="attribute_name_${attrIndex}" placeholder="e.g. size, color" value="${escapeHtml(name)}">
            </div>
            <div class="col-md-6">
                <label>Values (comma separated)</label>
                <input class="form-control" name="attribute_values_${attrIndex}" placeholder="e.g. 40,41,42 or Red,Blue" value="${escapeHtml(values)}">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger" onclick="removeAttributeRow(${attrIndex})" title="Remove">×</button>
            </div>
        `;
            area.appendChild(row);
            attrIndex++;
        }

        function removeAttributeRow(idx) {
            const row = document.querySelector(`[data-index="${idx}"]`);
            if (row) row.remove();
        }

        // escape helper
        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
        }

        // Read attributes from the UI and return meta array
        function readAttributesMeta() {
            const area = document.getElementById('attributesArea');
            const metas = [];
            const rows = area.querySelectorAll('[data-index]');
            rows.forEach(row => {
                const idx = row.dataset.index;
                const nameInput = row.querySelector(`[name="attribute_name_${idx}"]`);
                const valuesInput = row.querySelector(`[name="attribute_values_${idx}"]`);
                if (!nameInput || !valuesInput) return;
                const name = nameInput.value.trim();
                const vals = valuesInput.value.split(',').map(v => v.trim()).filter(v => v !== '');
                if (name && vals.length) {
                    metas.push({
                        name: name,
                        values: vals
                    });
                }
            });
            return metas;
        }

        // Cartesian product of arrays (array of arrays)
        function cartesianProduct(arrays) {
            return arrays.reduce((acc, curr) => {
                const res = [];
                acc.forEach(a => {
                    curr.forEach(b => {
                        res.push(a.concat([b]));
                    });
                });
                return res;
            }, [
                []
            ]);
        }

        // Generate variants HTML and hidden inputs
        function generateVariants() {
            const metas = readAttributesMeta();
            if (!metas.length) {
                alert('Add at least one attribute (e.g. size) with values.');
                return;
            }

            // Save attributes meta JSON into hidden input so backend can store attribute metadata
            document.getElementById('attributesMetaInput').value = JSON.stringify(metas);

            // Build arrays for cartesian product: [[{name,val}, ...], ...]
            const arrays = metas.map(m => m.values.map(v => ({
                name: m.name,
                value: v
            })));

            const combos = cartesianProduct(arrays); // combos is array of arrays of {name,value}

            const variantsArea = document.getElementById('variantsArea');
            variantsArea.innerHTML = '';

            if (!combos.length) {
                variantsArea.innerHTML = '<div class="alert alert-warning">No combinations could be generated.</div>';
                return;
            }

            // Build table header
            const headerCols = metas.map(m => `<th>${escapeHtml(m.name)}</th>`).join('') + '<th>SKU  Stock Keeping Unit (optional)</th><th>Stock</th><th>Actions</th>';
            let html = `<div class="table-responsive"><table class="table table-bordered"><thead><tr>${headerCols}</tr></thead><tbody>`;

            // For each combination create a row + hidden inputs
            combos.forEach((combo, i) => {
                // Build readable label and JSON
                const optionObj = {};
                combo.forEach(opt => optionObj[opt.name] = opt.value);

                // Render cells for each attribute
                const cells = combo.map(opt => `<td>${escapeHtml(opt.value)}</td>`).join('');

                // Each variant will have:
                // - hidden input variant_options[] = JSON.stringify(optionObj)
                // - input variant_sku[i]
                // - input variant_stock[i]
                html += `<tr data-variant-index="${i}">
                        ${cells}
                        <td><input type="text" name="variant_sku[${i}]" class="form-control" placeholder="e.g NIKE-AIR-RED-42-001"></td>
                        <td><input type="number" name="variant_stock[${i}]" class="form-control" value="0" min="0" required></td>
                        <td><button type="button" class="btn btn-sm btn-danger" onclick="removeVariantRow(this)">Remove</button></td>
                        <input type="hidden" name="variant_options[${i}]" value='${escapeHtml(JSON.stringify(optionObj))}'>
                    </tr>`;
            });

            html += `</tbody></table></div>`;

            variantsArea.innerHTML = html;

            // Scroll to variants
            variantsArea.scrollIntoView({
                behavior: 'smooth'
            });
        }

        function removeVariantRow(btn) {
            const tr = btn.closest('tr');
            if (!tr) return;
            const idx = tr.dataset.variantIndex;
            // Remove row
            tr.remove();

            // After removal, re-index the variant inputs to ensure sequential keys (important for PHP indexing)
            reindexVariants();
        }

        function reindexVariants() {
            const rows = Array.from(document.querySelectorAll('#variantsArea table tbody tr'));
            rows.forEach((tr, newIndex) => {
                tr.dataset.variantIndex = newIndex;
                // Rename inputs inside the row accordingly
                const sku = tr.querySelector('input[name^="variant_sku"]');
                const stock = tr.querySelector('input[name^="variant_stock"]');
                const optionsHidden = tr.querySelector('input[name^="variant_options"]');
                if (sku) sku.name = `variant_sku[${newIndex}]`;
                if (stock) stock.name = `variant_stock[${newIndex}]`;
                if (optionsHidden) optionsHidden.name = `variant_options[${newIndex}]`;
            });
        }

        // Pre-populate with a default attribute row for convenience
        addAttributeRow('size', 'S,M,L');
    </script>
</body>

</html>