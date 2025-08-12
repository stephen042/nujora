<?php
session_start();
require 'db.php';

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. Initialize and validate user_id
$user_id = (int)$_SESSION['user_id'];
if ($user_id <= 0) {
    $_SESSION['error'] = "Invalid user session";
    header("Location: login.php");
    exit;
}

// 3. Initialize and validate chat_with parameter
if (!isset($_GET['chat_with']) || !is_numeric($_GET['chat_with'])) {
    $_SESSION['error'] = "No chat partner specified";
    header("Location: inbox.php");
    exit;
}

$chat_with = (int)$_GET['chat_with'];
if ($chat_with <= 0) {
    $_SESSION['error'] = "Invalid chat partner";
    header("Location: inbox.php");
    exit;
}

// 4. Verify the chat partner exists in database
try {
    $stmt = $pdo->prepare("SELECT id, name, shop_name FROM users WHERE id = ?");
    $stmt->execute([$chat_with]);
    $partner = $stmt->fetch();
    
    if (!$partner) {
        $_SESSION['error'] = "Chat partner not found";
        header("Location: inbox.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error";
    header("Location: inbox.php");
    exit;
}

// 5. Get user role
$role = $_SESSION['role'] ?? 'buyer'; // Default to buyer if not set
$isBuyer = ($role === 'buyer');

// 6. Get messages for this conversation
try {
    $stmt = $pdo->prepare("SELECT * FROM messages 
                          WHERE (sender_id = ? AND receiver_id = ?) 
                          OR (sender_id = ? AND receiver_id = ?) 
                          ORDER BY sent_at ASC");
    $stmt->execute([$user_id, $chat_with, $chat_with, $user_id]);
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $messages = [];
    $_SESSION['error'] = "Failed to load messages";
}

// 7. Get partner name based on role
$partner_name = $isBuyer ? ($partner['shop_name'] ?? $partner['name']) : $partner['name'];
?>

<!DOCTYPE html>
<html>
<head>
  <title>Chat | NearbyShop</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    .chat-box { max-height: 70vh; overflow-y: auto; }
    .message-buyer { background: #d1e7dd; padding: 10px; margin: 5px; border-radius: 10px; align-self: end; }
    .message-seller { background: #fff3cd; padding: 10px; margin: 5px; border-radius: 10px; align-self: start; }
  </style>
</head>
<body>
<div class="container mt-4">
  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>
  
  <a href="inbox.php" class="btn btn-secondary mb-3">← Back to Inbox</a>
  
  <h4>Chat with <?= htmlspecialchars($partner_name) ?></h4>
  
  <div class="d-flex flex-column chat-box mb-3 p-2 border" id="chat-box">
    <?php if (empty($messages)): ?>
      <div class="text-muted text-center">No messages yet</div>
    <?php else: ?>
      <?php foreach ($messages as $msg): ?>
        <div class="<?= $msg['sender_id'] == $user_id ? 'message-buyer' : 'message-seller' ?>">
          <?= htmlspecialchars($msg['message']) ?>
          <div class="small text-muted"><?= htmlspecialchars($msg['sent_at']) ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <form id="chat-form" class="fixed-bottom bg-white p-3 shadow">
    <input type="hidden" name="chat_with" value="<?= $chat_with ?>">
    <div class="input-group">
      <input type="text" id="chat-input" name="message" class="form-control" placeholder="Type message..." required>
      <button type="submit" class="btn btn-primary">Send</button>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chat-box');
    const form = document.getElementById('chat-form');

    // Form submission handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const messageInput = document.getElementById('chat-input');
        const message = messageInput.value.trim();
        
        if (!message) return;
        
        try {
            const response = await fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `chat_with=<?= $chat_with ?>&message=${encodeURIComponent(message)}`
            });
            
            if (response.ok) {
                messageInput.value = '';
                // Reload messages after sending
                await loadMessages();
            }
        } catch (error) {
            console.error('Send failed:', error);
        }
    });

    // Function to load messages
    async function loadMessages() {
        try {
            const response = await fetch(`get_messages.php?chat_with=<?= $chat_with ?>`);
            if (response.ok) {
                const messages = await response.json();
                updateChatBox(messages);
            }
        } catch (error) {
            console.error('Load messages failed:', error);
        }
    }

    // Function to update chat box with messages
    function updateChatBox(messages) {
        chatBox.innerHTML = '';
        
        if (messages.length === 0) {
            chatBox.innerHTML = '<div class="text-muted text-center">No messages yet</div>';
            return;
        }
        
        messages.forEach(msg => {
            const messageDiv = document.createElement('div');
            messageDiv.className = msg.sender_id == <?= $user_id ?> ? 'message-buyer' : 'message-seller';
            messageDiv.innerHTML = `
                ${escapeHtml(msg.message)}
                <div class="small text-muted">${msg.sent_at}</div>
            `;
            chatBox.appendChild(messageDiv);
        });
        
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Helper function to escape HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Load messages initially
    loadMessages();
    
    // Poll for new messages every 3 seconds
    setInterval(loadMessages, 3000);
});
</script>
</body>
</html>