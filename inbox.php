<?php
// inbox.php - Chat inbox for buyer or seller
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role']; // 'buyer' or 'seller'

$query = ($role === 'buyer') ?
    "SELECT DISTINCT receiver_id AS chat_with, u.shop_name AS name 
     FROM messages m 
     JOIN users u ON m.receiver_id = u.id 
     WHERE m.sender_id = ?" :
    "SELECT DISTINCT sender_id AS chat_with, u.name 
     FROM messages m 
     JOIN users u ON m.sender_id = u.id 
     WHERE m.receiver_id = ?";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id]);
$chats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Inbox | NearbyShop</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
  <h4><?= ucfirst($role) ?> Inbox</h4>
  <ul class="list-group">
    <?php foreach ($chats as $chat): ?>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <a href="chat.php?chat_with=<?= $chat['chat_with'] ?>">Chat with <?= htmlspecialchars($chat['name']) ?></a>
        <span class="badge bg-danger" id="notif-<?= $chat['chat_with'] ?>">0</span>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<script>
  <?php foreach ($chats as $chat): ?>
  fetch("check_new_messages.php?chat_with=<?= $chat['chat_with'] ?>")
    .then(res => res.json())
    .then(data => {
      const notif = document.getElementById("notif-<?= $chat['chat_with'] ?>");
      if (data.count > 0) notif.textContent = data.count;
      else notif.style.display = 'none';
    });
  <?php endforeach; ?>
</script>
</body>
</html>