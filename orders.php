<?php
require 'app/config.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: auth/login.php");
    exit;
}
$buyer_id = $_SESSION['user_id'];
$statusMessage = '';
$current_tab = $_GET['tab'] ?? 'all';

// Check for unreviewed products
$pending_stmt = $pdo->prepare("
    SELECT oi.id
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.buyer_id = ? 
      AND oi.reviewed = 0
    LIMIT 1
");
$pending_stmt->execute([$buyer_id]);
$has_pending_reviews = ($pending_stmt->rowCount() > 0);

try {
    // Fetch all orders with their status history
    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT status FROM order_status_history 
                WHERE order_id = o.id 
                ORDER BY changed_at DESC LIMIT 1) as current_status,
                GROUP_CONCAT(oi.product_id) as product_ids,
                GROUP_CONCAT(p.name) as product_names,
                GROUP_CONCAT(p.slug) as product_slug,
                GROUP_CONCAT(oi.quantity) as quantities,
                GROUP_CONCAT(oi.price) as prices,
                GROUP_CONCAT(p.image_url) as image_urls,
                GROUP_CONCAT(oi.variant_options) as variant_options 
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.buyer_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$buyer_id]);
    $all_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter orders by status
    $orders = array_filter($all_orders, function ($order) use ($current_tab) {
        return $current_tab === 'all' || $order['current_status'] === $current_tab;
    });

    // Count orders by status for tabs
    $status_counts = [
        'all' => count($all_orders),
        'pending' => 0,
        'processing' => 0,
        'shipped' => 0,
        'completed' => 0,
        'cancelled' => 0
    ];

    foreach ($all_orders as $order) {
        if (isset($status_counts[$order['current_status']])) {
            $status_counts[$order['current_status']]++;
        }
    }

    // Prepare statements used per-item checks
    $canReviewStmt = $pdo->prepare("
        SELECT 1
        FROM order_items oi
        JOIN orders o ON o.id = oi.order_id
        WHERE oi.product_id = ?
          AND o.id = ?
          AND o.buyer_id = ?
        LIMIT 1
    ");

    $latestStatusStmt = $pdo->prepare("
        SELECT status FROM order_status_history
        WHERE order_id = ?
        ORDER BY changed_at DESC
        LIMIT 1
    ");

    $existingReviewStmt = $pdo->prepare("
        SELECT id FROM product_reviews
        WHERE order_id = ? AND product_id = ? AND buyer_id = ?
        LIMIT 1
    ");
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="uploads/default-product.png">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #f57c00;
            /* Bright Orange */
            --secondary-color: #ef6c00;
            /* Deep Orange */
            --accent-color: #ffb74d;
            /* Soft Yellow-Orange */
            --light-bg: #fff8f0;
            /* Warm Light Background */
            --dark-text: #1e1e1e;
            /* Darker Text for Better Contrast */
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: var(--light-bg);
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Poppins', sans-serif;
        }

        .order-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            border: none;
            transition: transform 0.3s;
        }

        @media (max-width: 768px) {
            .order-card {
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            }

            .order-header {
            flex-direction: column;
            align-items: flex-start !important;
            padding: 12px;
            gap: 10px;
            }

            .order-product {
            flex-direction: column;
            padding: 12px;
            }

            .product-img {
            width: 100%;
            height: 150px;
            margin-right: 0;
            margin-bottom: 10px;
            }

            .order-footer {
            flex-direction: column;
            gap: 12px;
            text-align: center;
            }

            .order-footer > div:first-child {
            width: 100%;
            }

            .order-footer > div:last-child {
            width: 100%;
            }

            h6 {
            font-size: 0.9rem;
            }

            h5 {
            font-size: 1rem;
            }

            .tracking-steps {
            padding: 10px 0;
            }

            .step-text {
            font-size: 0.7rem;
            }
        }

        .order-card:hover {
            transform: translateY(-3px);
        }

        .order-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }

        .order-product {
            display: flex;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-product:last-child {
            border-bottom: none;
        }

        .product-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #ffc107;
            color: #000;
        }

        .status-processing {
            background-color: #17a2b8;
            color: #fff;
        }

        .status-shipped {
            background-color: #007bff;
            color: #fff;
        }

        .status-completed {
            background-color: #28a745;
            color: #fff;
        }

        .status-cancelled {
            background-color: #dc3545;
            color: #fff;
        }

        .nav-tabs .nav-link {
            color: #495057;
            font-weight: 500;
            border: none;
            padding: 10px 15px;
            position: relative;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background: transparent;
        }

        .nav-tabs .nav-link.active:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-color);
        }

        .nav-tabs .nav-link .badge {
            font-size: 0.7rem;
            margin-left: 5px;
        }

        .write-review-btn {
            padding: .25rem .5rem;
            font-size: .85rem;
        }

        .star {
            cursor: pointer;
            font-size: 1.25rem;
            color: #ddd;
        }

        .star.selected {
            color: #ffb400;
        }

        .img-preview {
            max-width: 100px;
            max-height: 80px;
            object-fit: cover;
            margin-top: 8px;
            border-radius: 4px;
        }

        .tracking-steps {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            position: relative;
        }

        .tracking-step {
            text-align: center;
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 5px;
        }

        .step-active .step-icon {
            background: var(--primary-color);
            color: white;
        }

        .step-complete .step-icon {
            background: #28a745;
            color: white;
        }

        .step-text {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .step-active .step-text,
        .step-complete .step-text {
            color: var(--primary-color);
            font-weight: 500;
        }

        .tracking-line {
            position: absolute;
            top: 35px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 0;
        }

        .tracking-progress {
            position: absolute;
            top: 35px;
            left: 0;
            height: 2px;
            background: var(--primary-color);
            z-index: 1;
            transition: width 0.3s;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <?php include 'includes/nav.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Orders</h2>
            <div class="text-muted">Showing <?= count($orders) ?> of <?= count($all_orders) ?> orders</div>
        </div>

        <!-- Order Status Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'all' ? 'active' : '' ?>" href="?tab=all">
                    All Orders <span class="badge bg-secondary"><?= $status_counts['all'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'pending' ? 'active' : '' ?>" href="?tab=pending">
                    Pending <span class="badge bg-warning"><?= $status_counts['pending'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'processing' ? 'active' : '' ?>" href="?tab=processing">
                    Processing <span class="badge bg-info"><?= $status_counts['processing'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'shipped' ? 'active' : '' ?>" href="?tab=shipped">
                    Shipped <span class="badge bg-primary"><?= $status_counts['shipped'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'completed' ? 'active' : '' ?>" href="?tab=completed">
                    Completed <span class="badge bg-success"><?= $status_counts['completed'] ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_tab === 'cancelled' ? 'active' : '' ?>" href="?tab=cancelled">
                    Cancelled <span class="badge bg-danger"><?= $status_counts['cancelled'] ?></span>
                </a>
            </li>
        </ul>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="bi bi-box-seam" style="font-size: 3rem; color: #6c757d;"></i>
                <h4 class="mt-3">No orders found</h4>
                <p class="text-muted">You have no orders yet</p>
                <a href="products.php" class="btn btn-primary mt-3">Shop Now</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order):
                $product_ids = explode(',', $order['product_ids']);
                $product_names = explode(',', $order['product_names']);
                $product_slug = explode(',', $order['product_slug']);
                $quantities = explode(',', $order['quantities']);
                $prices = explode(',', $order['prices']);
                $order_total = $order['total'];
                $image_urls = explode(',', $order['image_urls']);
                $variants_raw = explode(',', $order['variant_options']);
                // die($order_total);
                // Calculate order total
                // $order_total = 0;
                // foreach ($order_total_db as $index => $price) {
                //     $order_total += $price * $quantities[$index];
                // }

                // Tracking progress
                $tracking_steps = [
                    'pending' => ['icon' => 'bi-cart', 'text' => 'Order Placed'],
                    'processing' => ['icon' => 'bi-gear', 'text' => 'Processing'],
                    'shipped' => ['icon' => 'bi-truck', 'text' => 'Shipped'],
                    'completed' => ['icon' => 'bi-check-circle', 'text' => 'Delivered']
                ];

                $current_step_index = array_search($order['current_status'], array_keys($tracking_steps));
                $progress_width = $current_step_index !== false ? ($current_step_index / (count($tracking_steps) - 1)) * 100 : 0;
            ?>
                <div class="order-card bg-white mb-4">
                    <div class="order-header d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Placed on <?= date('M j, Y', strtotime($order['created_at'])) ?></small>
                        </div>
                        <div>
                            <span class="status-badge status-<?= $order['current_status'] ?>">
                                <?= ucfirst($order['current_status'] ?? 'pending') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Order Tracking -->
                    <div class="px-3 pt-3">
                        <div class="tracking-steps">
                            <div class="tracking-line"></div>
                            <div class="tracking-progress" style="width: <?= $progress_width ?>%"></div>
                            <?php foreach ($tracking_steps as $status => $step):
                                $is_active = $order['current_status'] === $status;
                                $is_complete = array_search($status, array_keys($tracking_steps)) < $current_step_index;
                            ?>
                                <div class="tracking-step <?= $is_active ? 'step-active' : '' ?> <?= $is_complete ? 'step-complete' : '' ?>">
                                    <div class="step-icon">
                                        <i class="bi <?= $step['icon'] ?>"></i>
                                    </div>
                                    <div class="step-text"><?= $step['text'] ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>


                    <!-- Order Products -->
                    <?php foreach ($product_ids as $index => $product_id):
                        $product_id = (int)$product_id;
                        // server-side checks: is item eligible for review?
                        $canReview = false;
                        $existingReview = false;

                        // check product in order (should always be true)
                        $canReviewStmt->execute([$product_id, $order['id'], $buyer_id]);
                        if ($canReviewStmt->fetch()) {
                            // check latest status is 'completed'
                            $latestStatusStmt->execute([$order['id']]);
                            $latest = $latestStatusStmt->fetchColumn();
                            if ($latest === 'completed') {
                                $canReview = true;
                                // check existing review for this order/product/buyer
                                $existingReviewStmt->execute([$order['id'], $product_id, $buyer_id]);
                                if ($existingReviewStmt->fetch()) {
                                    $existingReview = true;
                                    $canReview = false;
                                }
                            }
                        }
                    ?>
                        <div class="order-product">
                            <img src="<?= htmlspecialchars($image_urls[$index] ?? "uploads/default-product.png") ?>"
                                class="product-img"
                                alt="<?= htmlspecialchars($product_names[$index]) ?>">
                            <div class="flex-grow-1">
                                <h6><?= htmlspecialchars($product_names[$index]) ?></h6>
                                <div class="text-muted">
                                    Qty: <?= $quantities[$index] ?>
                                </div>
                                <?php
                                // Decode JSON safely
                                $variant_text = '';
                                if (!empty($variants_raw[$index]) && $variants_raw[$index] !== 'null') {
                                    $decoded = json_decode($variants_raw[$index], true);
                                    if (is_array($decoded)) {
                                        foreach ($decoded as $key => $value) {
                                            $variant_text .= ucfirst($key) . ': ' . htmlspecialchars($value) . ' ';
                                        }
                                    }
                                }
                                ?>
                                <?php if ($variant_text): ?>
                                    <div class="text-muted small">
                                        <?= $variant_text ?>
                                    </div>
                                <?php endif; ?>
                                <div class="text-muted small">Product Price: ₦<?= number_format($prices[$index], 2) ?></div>
                            </div>
                            <div class="text-end">
                                <a href="product_details.php?slug=<?= $product_slug[$index]?>" class="btn btn-sm btn-outline-primary mt-2 p-1 decoration-none">
                                    View Product
                                </a>

                                <?php if ($existingReview): ?>
                                    <div class="mt-2"><span class="badge bg-success">Reviewed</span></div>
                                <?php elseif ($canReview): ?>
                                    <button class="btn btn-sm btn-outline-warning mt-2 write-review-btn"
                                        data-order-id="<?= $order['id'] ?>"
                                        data-product-id="<?= $product_id ?>"
                                        data-product-name="<?= htmlspecialchars($product_names[$index], ENT_QUOTES) ?>">
                                        <i class="fa fa-star me-1"></i> Write Review
                                    </button>
                                <?php else: ?>
                                    <!-- not eligible or not completed -->
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>


                    <!-- Order Summary -->
                    <div class="order-footer p-3 bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <?php if ($order['current_status'] === 'pending' || $order['current_status'] === 'processing'): ?>
                                <a href="cancel-order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Are you sure you want to cancel this order?')">
                                    Cancel Order
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <div class="text-muted">Total <?= count($product_ids) ?> item(s)</div>
                            <h6 class="text-muted">Discount: <span class="text-danger">-₦<?= number_format($order['discount'] ?? 0, 2) ?></span></h6>
                            <h5 class="text-muted">Total: <span class="text-success">₦<?= number_format($order_total, 2) ?></span></h5>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="reviewForm" class="modal-content" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Write a review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="rv-order-id">
                    <input type="hidden" name="product_id" id="rv-product-id">

                    <div class="mb-2">
                        <strong id="rv-product-name"></strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <div id="rv-stars">
                            <span class="star" data-value="1">&#9733;</span>
                            <span class="star" data-value="2">&#9733;</span>
                            <span class="star" data-value="3">&#9733;</span>
                            <span class="star" data-value="4">&#9733;</span>
                            <span class="star" data-value="5">&#9733;</span>
                        </div>
                        <input type="hidden" name="rating" id="rv-rating" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Review</label>
                        <textarea name="review_text" id="rv-text" rows="4" class="form-control" placeholder="Write about your experience..."></textarea>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Upload image (optional, max 3MB)</label>
                        <input type="file" name="image" id="rv-image" accept="image/png,image/jpeg" class="form-control">
                        <img id="rv-image-preview" class="img-preview d-none" alt="preview">
                    </div>

                    <div id="rv-error" class="text-danger small mt-2"></div>
                    <div id="rv-success" class="text-success small mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button id="rv-submit" type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Review Reminder Popup -->
    <?php if ($has_pending_reviews): ?>
        <div id="rateReminderPopup"
            style="position: fixed; 
            top: 0; left: 0; right: 0; bottom: 0; 
            background: rgba(0,0,0,0.6); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            z-index: 9999;">

            <div style="background: white; 
              padding: 25px; 
              border-radius: 10px; 
              max-width: 350px; 
              text-align: center; 
              box-shadow: 0 4px 10px rgba(0,0,0,0.2);">

                <h4>Your Feedback Is Needed</h4>
                <p>You have items you purchased but have not rated yet.
                    Please take a moment to leave a review.</p>

                <button id="closePopupBtn"
                    style="padding: 8px 20px; 
                     border: none; 
                     background: #ff6600; 
                     color: white; 
                     border-radius: 5px; 
                     cursor: pointer;">
                    Okay, I’ll rate them
                </button>

            </div>
        </div>
    <?php endif; ?>


    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    <!-- Script -->
    <?php include 'includes/script.php'; ?>

    <!-- Bottom Navigation -->
    <?php include 'includes/bottomNav.php'; ?>

    <script>
        document.getElementById("closePopupBtn")?.addEventListener("click", function() {
            document.getElementById("rateReminderPopup").style.display = "none";
        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
            const rvProductName = document.getElementById('rv-product-name');
            const rvOrderId = document.getElementById('rv-order-id');
            const rvProductId = document.getElementById('rv-product-id');
            const rvRating = document.getElementById('rv-rating');
            const rvStars = document.querySelectorAll('#rv-stars .star');
            const rvImage = document.getElementById('rv-image');
            const rvImagePreview = document.getElementById('rv-image-preview');
            const rvError = document.getElementById('rv-error');
            const rvSuccess = document.getElementById('rv-success');
            const rvForm = document.getElementById('reviewForm');

            // open modal
            document.querySelectorAll('.write-review-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    rvError.textContent = '';
                    rvSuccess.textContent = '';
                    rvImagePreview.classList.add('d-none');
                    rvImagePreview.src = '';

                    rvOrderId.value = btn.getAttribute('data-order-id');
                    rvProductId.value = btn.getAttribute('data-product-id');
                    rvProductName.textContent = btn.getAttribute('data-product-name');

                    // reset stars
                    rvRating.value = '';
                    rvStars.forEach(s => s.classList.remove('selected'));

                    reviewModal.show();
                });
            });

            // star click
            rvStars.forEach(s => {
                s.addEventListener('click', () => {
                    const v = s.getAttribute('data-value');
                    rvRating.value = v;
                    rvStars.forEach(st => st.classList.toggle('selected', st.getAttribute('data-value') <= v));
                });
            });

            // image preview
            rvImage.addEventListener('change', () => {
                rvError.textContent = '';
                const file = rvImage.files[0];
                if (!file) {
                    rvImagePreview.classList.add('d-none');
                    rvImagePreview.src = '';
                    return;
                }
                if (file.size > 3 * 1024 * 1024) {
                    rvError.textContent = 'Image exceeds 3MB.';
                    rvImage.value = '';
                    return;
                }
                const allowed = ['image/jpeg', 'image/png'];
                if (!allowed.includes(file.type)) {
                    rvError.textContent = 'Only JPG/PNG allowed.';
                    rvImage.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = e => {
                    rvImagePreview.src = e.target.result;
                    rvImagePreview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            });

            // AJAX submit
            rvForm.addEventListener('submit', (e) => {
                e.preventDefault();
                rvError.textContent = '';
                rvSuccess.textContent = '';

                if (!rvRating.value) {
                    rvError.textContent = 'Please select a rating.';
                    return;
                }

                const fd = new FormData(rvForm);
                const submitBtn = document.getElementById('rv-submit');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';

                fetch('review_submit.php', {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin'
                    }).then(res => res.json())
                    .then(json => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Submit Review';
                        if (json.success) {
                            rvSuccess.textContent = json.message || 'Review submitted.';
                            // change the button that opened the modal to 'Reviewed' badge
                            const selector = `.write-review-btn[data-order-id="${rvOrderId.value}"][data-product-id="${rvProductId.value}"]`;
                            const opener = document.querySelector(selector);
                            if (opener) {
                                const badge = document.createElement('div');
                                badge.innerHTML = '<span class="badge bg-success">Reviewed</span>';
                                opener.replaceWith(badge);
                            }
                            // close modal after a short delay and redirect
                            setTimeout(() => {
                                reviewModal.hide();
                                window.location.href = 'orders.php?tab=completed';
                            }, 900);
                        } else {
                            rvError.textContent = json.message || 'Failed to submit review.';
                        }
                    }).catch(err => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Submit Review';
                        rvError.textContent = 'Network error. Try again.';
                        console.error(err);
                    });
            });
        });
    </script>

</body>

</html>