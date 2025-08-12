<?php
// product_reviews_display.php
require 'db.php';

if (!isset($_GET['product_id'])) {
    exit;
}
$product_id = (int) $_GET['product_id'];

$stmt = $pdo->prepare("SELECT r.stars, r.review, r.created_at, u.name FROM product_reviews r JOIN users u ON r.buyer_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$product_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="mt-4">
    <h5>Product Reviews</h5>
    <?php if ($reviews): ?>
        <?php foreach ($reviews as $rev): ?>
            <div class="border p-2 mb-2">
                <strong><?= htmlspecialchars($rev['name']) ?></strong> —
                <?= str_repeat('★', (int)$rev['stars']) ?><?= str_repeat('☆', 5 - (int)$rev['stars']) ?><br>
                <small class="text-muted"><?= $rev['created_at'] ?></small>
                <p><?= nl2br(htmlspecialchars($rev['review'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted">No reviews yet.</p>
    <?php endif; ?>
</div>
