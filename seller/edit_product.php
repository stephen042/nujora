<?php
require '../app/config.php';

// small helper: generate slug if you don't already have it in scope
if (!function_exists('generateSlug')) {
    function generateSlug($text)
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        if (empty($text)) return 'product-' . time();
        return $text;
    }
}

// small SKU generator (you can replace with your own logic)
function generateSKU($productName, $attributes)
{
    // $attributes is associative array e.g. ['size'=>'42','color'=>'Red']
    $base = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($productName, 0, 6)));
    if ($base === '') $base = 'PRD';
    $parts = [];
    foreach ($attributes as $k => $v) {
        $clean = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($v, 0, 3)));
        if ($clean !== '') $parts[] = $clean;
    }
    $rand = substr(md5(uniqid('', true)), 0, 4);
    return $base . (count($parts) ? '-' . implode('-', $parts) : '') . '-' . $rand;
}

// get product id
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$product_id) {
    die('Invalid product ID.');
}

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

// ------------------ Load current variants ------------------
$variants = [];
$stmtV = $pdo->prepare("
    SELECT pv.id AS variant_id, pv.sku, pv.stock,
           GROUP_CONCAT(CONCAT(pvo.option_name, '||', pvo.option_value) SEPARATOR ';;') AS opts
    FROM product_variants pv
    LEFT JOIN product_variant_options pvo ON pvo.variant_id = pv.id
    WHERE pv.product_id = ?
    GROUP BY pv.id
    ORDER BY pv.id ASC
");
$stmtV->execute([$product_id]);
while ($row = $stmtV->fetch(PDO::FETCH_ASSOC)) {
    // parse opts into associative array
    $optArr = [];
    if (!empty($row['opts'])) {
        $pairs = explode(';;', $row['opts']);
        foreach ($pairs as $p) {
            if (strpos($p, '||') !== false) {
                [$k, $v] = explode('||', $p, 2);
                $optArr[$k] = $v;
            }
        }
    }
    $variants[] = [
        'id' => $row['variant_id'],
        'sku' => $row['sku'],
        'stock' => $row['stock'],
        'options' => $optArr
    ];
}

// ------------------ Handle POST requests ------------------
// There are three separate actions:
// 1) Update product details (name/price/desc/images) -> submit name="update"
// 2) Delete selected variants -> submit name="delete_variants"
// 3) Add new variants generated from attributes -> submit name="add_variants"

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --------- 1) Update product details ----------
    if (isset($_POST['update'])) {
        $name        = trim($_POST['name']);
        $price       = floatval($_POST['price']);
        $description = trim($_POST['description']);
        $stock       = intval($_POST['stock']);
        $status      = htmlspecialchars($_POST['status']);
        $categoryId  = $_POST['category'] ?? '';
        $subCategoryId = $_POST['sub_category'] ?? '';

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

            // Sub category name if chosen
            $subCategoryName = '';
            if (!empty($subCategoryId)) {
                $stmtSub = $pdo->prepare("SELECT name FROM sub_categories WHERE id = ? AND category_id = ?");
                $stmtSub->execute([$subCategoryId, $categoryId]);
                $subCategoryName = $stmtSub->fetchColumn() ?: '';
            }

            // Final price
            $finalPrice = ceil($price + ($price * $sellersFee / 100));

            // Existing images
            $mainImage = $product['image_url'];
            $photos = json_decode($product['photos'], true) ?? [];

            // Handle delete photos (checkboxes)
            if (!empty($_POST['delete_photos']) && is_array($_POST['delete_photos'])) {
                foreach ($_POST['delete_photos'] as $photoToDelete) {
                    $key = array_search($photoToDelete, $photos);
                    if ($key !== false) {
                        unset($photos[$key]);
                        if (file_exists($photoToDelete)) {
                            @unlink($photoToDelete);
                        }
                    }
                }
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
                $slug = generateSlug($name);
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET slug=?, name=?,price=?, original_price=?, description=?, stock=?, status=?, category=?, sub_category=?, image_url=?, photos=? 
                    WHERE id=?");
                $stmt->execute([
                    $slug,
                    $name,
                    $finalPrice,
                    $price,
                    $description,
                    $stock,
                    $status,
                    $categoryName,
                    $subCategoryName,
                    $mainImage,
                    json_encode($photos),
                    $product_id,
                ]);

                // reload product row
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                $statusMessage = '<div class="alert alert-success">Product updated successfully!</div>';
            } catch (PDOException $e) {
                $statusMessage = '<div class="alert alert-danger">Error updating: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }

    // --------- 2) Delete selected variants ----------
    if (isset($_POST['delete_variants']) && is_array($_POST['delete_variants'])) {
        $toDelete = array_map('intval', $_POST['delete_variants']);
        if (!empty($toDelete)) {
            try {
                $pdo->beginTransaction();
                // delete from product_variant_options first then product_variants (cascade should handle it but do explicit)
                $in = implode(',', array_fill(0, count($toDelete), '?'));
                $stmtDelOpts = $pdo->prepare("DELETE FROM product_variant_options WHERE variant_id IN ($in)");
                $stmtDelOpts->execute($toDelete);

                $stmtDelVar = $pdo->prepare("DELETE FROM product_variants WHERE id IN ($in) AND product_id = ?");
                $params = array_merge($toDelete, [$product_id]);
                $stmtDelVar->execute($params);

                $pdo->commit();
                $statusMessage = '<div class="alert alert-success">Selected variant(s) deleted.</div>';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $statusMessage = '<div class="alert alert-danger">Error deleting variant(s): ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            // reload variants list
            $variants = [];
            $stmtV->execute([$product_id]);
            while ($row = $stmtV->fetch(PDO::FETCH_ASSOC)) {
                $optArr = [];
                if (!empty($row['opts'])) {
                    $pairs = explode(';;', $row['opts']);
                    foreach ($pairs as $p) {
                        if (strpos($p, '||') !== false) {
                            [$k, $v] = explode('||', $p, 2);
                            $optArr[$k] = $v;
                        }
                    }
                }
                $variants[] = [
                    'id' => $row['variant_id'],
                    'sku' => $row['sku'],
                    'stock' => $row['stock'],
                    'options' => $optArr
                ];
            }
        }
    }

    // --------- 3) Add new variants (generated at client and posted) ----------
    if (isset($_POST['add_variants'])) {
        // Expecting:
        // - attributes_meta: JSON string of [{name: 'size', values: ['S','M']} ...] (optional)
        // - variant_options[] (array of JSON strings) each associative {size:'S', color:'Red'}
        // - variant_stock[] (matching index)
        // - variant_sku[] optional (matching index)
        $newVariantOptions = $_POST['variant_options'] ?? [];
        $newVariantStocks = $_POST['variant_stock'] ?? [];
        $newVariantSkus = $_POST['variant_sku'] ?? [];

        if (!is_array($newVariantOptions) || empty($newVariantOptions)) {
            $statusMessage = '<div class="alert alert-danger">No new variants provided.</div>';
        } else {
            try {
                $pdo->beginTransaction();

                // Optional: save product attributes metadata if provided
                if (!empty($_POST['attributes_meta'])) {
                    $attrsMeta = json_decode($_POST['attributes_meta'], true);
                    if (is_array($attrsMeta)) {
                        // cleanup existing attributes for this product (optional behaviour)
                        $stmtAttrDel = $pdo->prepare("DELETE FROM product_attribute_values WHERE attribute_id IN (SELECT id FROM product_attributes WHERE product_id = ?)");
                        $stmtAttrDel->execute([$product_id]);
                        $stmtAttrDel2 = $pdo->prepare("DELETE FROM product_attributes WHERE product_id = ?");
                        $stmtAttrDel2->execute([$product_id]);

                        $stmtAttr = $pdo->prepare("INSERT INTO product_attributes (product_id, attribute_name) VALUES (?, ?)");
                        $stmtAttrVal = $pdo->prepare("INSERT INTO product_attribute_values (attribute_id, value) VALUES (?, ?)");

                        foreach ($attrsMeta as $attr) {
                            if (empty($attr['name']) || empty($attr['values']) || !is_array($attr['values'])) continue;
                            $attrName = trim($attr['name']);
                            $stmtAttr->execute([$product_id, $attrName]);
                            $attributeId = $pdo->lastInsertId();
                            foreach ($attr['values'] as $val) {
                                $val = trim($val);
                                if ($val === '') continue;
                                $stmtAttrVal->execute([$attributeId, $val]);
                            }
                        }
                    }
                }

                // Insert each variant
                $stmtInsVar = $pdo->prepare("INSERT INTO product_variants (product_id, sku, stock) VALUES (?, ?, ?)");
                $stmtInsOpt = $pdo->prepare("INSERT INTO product_variant_options (variant_id, option_name, option_value) VALUES (?, ?, ?)");

                foreach ($newVariantOptions as $i => $jsonOpt) {
                    $optAssoc = json_decode($jsonOpt, true);
                    if (!is_array($optAssoc)) continue;

                    $vStock = isset($newVariantStocks[$i]) ? intval($newVariantStocks[$i]) : 0;
                    $vSku = isset($newVariantSkus[$i]) && trim($newVariantSkus[$i]) !== '' ? trim($newVariantSkus[$i]) : null;
                    if (empty($vSku)) {
                        $vSku = generateSKU($product['name'], $optAssoc);
                    }

                    // insert variant
                    $stmtInsVar->execute([$product_id, $vSku, $vStock]);
                    $variantId = $pdo->lastInsertId();

                    foreach ($optAssoc as $oname => $ovalue) {
                        $oname = trim($oname);
                        $ovalue = trim($ovalue);
                        if ($oname === '' || $ovalue === '') continue;
                        $stmtInsOpt->execute([$variantId, $oname, $ovalue]);
                    }
                }

                $pdo->commit();
                $statusMessage = '<div class="alert alert-success">New variants added.</div>';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $statusMessage = '<div class="alert alert-danger">Error adding variants: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }

            // reload variants
            $variants = [];
            $stmtV->execute([$product_id]);
            while ($row = $stmtV->fetch(PDO::FETCH_ASSOC)) {
                $optArr = [];
                if (!empty($row['opts'])) {
                    $pairs = explode(';;', $row['opts']);
                    foreach ($pairs as $p) {
                        if (strpos($p, '||') !== false) {
                            [$k, $v] = explode('||', $p, 2);
                            $optArr[$k] = $v;
                        }
                    }
                }
                $variants[] = [
                    'id' => $row['variant_id'],
                    'sku' => $row['sku'],
                    'stock' => $row['stock'],
                    'options' => $optArr
                ];
            }
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Edit Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4 mb-5">
        <a href="seller-dashboard.php" class="btn btn-secondary mb-3">← Back to Products</a>
        <h4>Edit Product</h4>
        <?= $statusMessage; ?>

        <!-- Product update form -->
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Product Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <!-- Category -->
            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category" id="categorySelect" class="form-select" required>
                    <option value="">Select category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat->id ?>" data-fee="<?= $cat->sellers_fee ?>" <?= ($cat->name == $product['category']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat->name) ?> (Fee: <?= $cat->sellers_fee ?>%)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Sub Category </label>
                <span class="badge bg-info text-dark me-2">(<?= htmlspecialchars($product['sub_category']) ?>)</span>
                <select name="sub_category" id="subCategorySelect" class="form-select">
                    <option value="<?= htmlspecialchars($product['sub_category']) ?>"><?= htmlspecialchars($product['sub_category']) ?></option>
                </select>
            </div>

            <!-- Price -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Original Price (₦)</label>
                    <input type="number" id="priceInput" name="price" class="form-control" step="0.01" value="<?= htmlspecialchars($product['original_price']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Final Price (₦)</label>
                    <input type="text" id="finalPrice" class="form-control" value="<?= htmlspecialchars($product['price']) ?>" readonly>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="6"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Stock (General)</label>
                <input type="number" name="stock" class="form-control" value="<?= htmlspecialchars($product['stock']) ?>">
                <small class="text-muted">Variants may have independent stock.</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="In Stock" <?= $product['status'] === 'In Stock' ? 'selected' : '' ?>>In Stock</option>
                    <option value="Out of Stock" <?= $product['status'] === 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>

            <!-- Main Image & Photos -->
            <div class="mb-3">
                <label class="form-label">Main Image</label><br>
                <?php if (!empty($product['image_url'])): ?>
                    <img src="<?= htmlspecialchars($product['image_url']) ?>" class="img-thumbnail mb-2" style="max-width:120px;">
                <?php endif; ?>
                <input type="file" name="product_image" class="form-control" accept="image/*" onchange="previewMainImage(event)">
                <div id="mainImagePreview" class="mt-2"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Extra Photos</label><br>
                <?php foreach (json_decode($product['photos'], true) ?? [] as $index => $photo): ?>
                    <div class="d-inline-block text-center me-2 mb-2" style="position: relative;">
                        <img src="<?= htmlspecialchars($photo) ?>" class="img-thumbnail" style="max-width:100px;">
                        <div>
                            <label class="form-check-label small">
                                <input type="checkbox" name="delete_photos[]" value="<?= htmlspecialchars($photo) ?>"> Delete
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
                <input type="file" name="product_photos[]" class="form-control mt-2" multiple accept="image/*" onchange="previewMultipleImages(event)">
                <div id="multiImagePreview" class="mt-2 d-flex flex-wrap"></div>
            </div>

            <button type="submit" name="update" class="btn btn-primary w-100 mb-4">Update Product</button>
        </form>

        <!-- ================= Existing Variants (view + delete) ================= -->
        <hr>
        <h5>Variants</h5>
        <p class="text-muted">View variants below. Select and click "Delete Selected Variants" to remove.</p>

        <?php if (empty($variants)): ?>
            <div class="alert alert-info">No variants for this product yet.</div>
        <?php else: ?>
            <form method="POST" onsubmit="return confirm('Delete selected variants? This cannot be undone.');">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width:40px"><input id="checkAll" type="checkbox"></th>
                            <th>SKU</th>
                            <th>Attributes</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($variants as $v): ?>
                            <tr>
                                <td><input type="checkbox" name="delete_variants[]" value="<?= intval($v['id']) ?>"></td>
                                <td><?= htmlspecialchars($v['sku']) ?></td>
                                <td>
                                    <?php
                                    $parts = [];
                                    foreach ($v['options'] as $on => $ov) {
                                        $parts[] = htmlspecialchars($on) . ': ' . htmlspecialchars($ov);
                                    }
                                    echo implode(' — ', $parts);
                                    ?>
                                </td>
                                <td><?= intval($v['stock']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <button type="submit" class="btn btn-danger" name="delete_variants">Delete Selected Variants</button>
            </form>
        <?php endif; ?>

        <!-- ================= Add New Variants (attributes generator) ================= -->
        <hr class="mt-4">
        <h5>Add New Variants</h5>
        <p class="text-muted">Add any number of attributes (name + comma-separated values). Click "Generate Variants" to create combinations, then "Save New Variants". Existing variants will remain — this only adds new ones.</p>

        <div id="attributesArea" class="mb-2"></div>
        <button type="button" class="btn btn-outline-secondary mb-3" onclick="addAttributeRow()">+ Add Option</button>
        <button type="button" class="btn btn-primary mb-3" onclick="generateVariants()">Generate Variants</button>

        <form method="POST" id="addVariantsForm">
            <div id="variantsArea"></div>
            <input type="hidden" name="attributes_meta" id="attributesMetaInput" value="">
            <button type="submit" class="btn btn-success mt-3" name="add_variants">Save New Variants</button>
        </form>

    </div>

    <script>
        // price/final price helpers (same logic as add page)
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

        // image previews
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

        // -------- Attributes generator code (same approach used in add_product) ----------
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

        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
        }

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

        function generateVariants() {
            const metas = readAttributesMeta();
            if (!metas.length) {
                alert('Add at least one attribute (e.g. size) with values.');
                return;
            }

            document.getElementById('attributesMetaInput').value = JSON.stringify(metas);

            const arrays = metas.map(m => m.values.map(v => ({
                name: m.name,
                value: v
            })));
            const combos = cartesianProduct(arrays);

            const variantsArea = document.getElementById('variantsArea');
            variantsArea.innerHTML = '';

            if (!combos.length) {
                variantsArea.innerHTML = '<div class="alert alert-warning">No combinations could be generated.</div>';
                return;
            }

            // build table
            const headerCols = metas.map(m => `<th>${escapeHtml(m.name)}</th>`).join('') + '<th>SKU (optional)</th><th>Stock</th><th>Actions</th>';
            let html = `<div class="table-responsive"><table class="table table-bordered"><thead><tr>${headerCols}</tr></thead><tbody>`;

            combos.forEach((combo, i) => {
                const optionObj = {};
                combo.forEach(opt => optionObj[opt.name] = opt.value);
                const cells = combo.map(opt => `<td>${escapeHtml(opt.value)}</td>`).join('');
                html += `<tr data-variant-index="${i}">
                    ${cells}
                    <td><input type="text" name="variant_sku[${i}]" class="form-control" placeholder="SKU"></td>
                    <td><input type="number" name="variant_stock[${i}]" class="form-control" value="0" min="0" required></td>
                    <td><button type="button" class="btn btn-sm btn-danger" onclick="removeVariantRow(this)">Remove</button></td>
                    <input type="hidden" name="variant_options[${i}]" value='${escapeHtml(JSON.stringify(optionObj))}'>
                </tr>`;
            });

            html += `</tbody></table></div>`;
            variantsArea.innerHTML = html;
            variantsArea.scrollIntoView({
                behavior: 'smooth'
            });
        }

        function removeVariantRow(btn) {
            const tr = btn.closest('tr');
            if (!tr) return;
            tr.remove();
            reindexVariants();
        }

        function reindexVariants() {
            const rows = Array.from(document.querySelectorAll('#variantsArea table tbody tr'));
            rows.forEach((tr, newIndex) => {
                tr.dataset.variantIndex = newIndex;
                const sku = tr.querySelector('input[name^="variant_sku"]');
                const stock = tr.querySelector('input[name^="variant_stock"]');
                const optionsHidden = tr.querySelector('input[name^="variant_options"]');
                if (sku) sku.name = `variant_sku[${newIndex}]`;
                if (stock) stock.name = `variant_stock[${newIndex}]`;
                if (optionsHidden) optionsHidden.name = `variant_options[${newIndex}]`;
            });
        }

        // convenience: check/uncheck all
        document.getElementById('checkAll')?.addEventListener('change', function() {
            const checked = this.checked;
            document.querySelectorAll('input[name="delete_variants[]"]').forEach(cb => cb.checked = checked);
        });

        // pre-populate a size attribute for convenience
        addAttributeRow('size', 'S,M,L');
    </script>
</body>

</html>