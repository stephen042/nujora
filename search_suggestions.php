<?php
require 'app/config.php'; // your DB connection

$search = $_GET['search'] ?? '';

$suggestions = [];

if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT name, slug FROM products WHERE name LIKE :search LIMIT 5");
    $stmt->execute(['search' => "%$search%"]);
    $suggestions = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

header('Content-Type: application/json');
echo json_encode($suggestions);
