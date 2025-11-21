<?php
require 'app/config.php';

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_NUMBER_INT);

if (!$order_id) {
    echo "<h1>Invalid Order ID</h1>";
    exit;
}

// Debugging: Check the value of order_id passed via GET
// var_dump($order_id); 
// exit;

// Fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, u.name AS buyer_name
    FROM orders o
    JOIN users u ON o.buyer_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "<h1>Order not found</h1>";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | <?= APP_NAME ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="uploads/default-product.png">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Poppins', sans-serif;
        }
    </style>

<body>
    <div class="container py-5">
        <h1 class="text-center">Order Confirmation</h1>
        <p class="text-center">Thank you for your order, <?= htmlspecialchars($order['buyer_name']) ?>!</p>
        <p class="text-center">We will process your order shortly.</p>
        <a href="index.php" class="btn btn-primary d-block mx-auto" style="max-width: 200px;">Back to Home</a>
    </div>
</body>

</html>