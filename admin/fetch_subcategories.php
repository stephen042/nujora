<?php
require '../app/config.php';

if (isset($_GET['category_id'])) {
    $catId = intval($_GET['category_id']);
    $stmt = $pdo->prepare("SELECT id, name FROM sub_categories WHERE category_id = ?");
    $stmt->execute([$catId]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}
