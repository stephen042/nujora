<?php
require 'db.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'seller' AND is_approved = FALSE");
$stmt->execute();
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Pending Seller Approvals</h2>
<table border='1' cellpadding='10'>
<tr><th>ID</th><th>Name</th><th>Email</th><th>Actions</th></tr>
<?php foreach ($sellers as $seller): ?>
<tr>
    <td><?= $seller['id'] ?></td>
    <td><?= htmlspecialchars($seller['name']) ?></td>
    <td><?= htmlspecialchars($seller['email']) ?></td>
    <td>
        <a href='approve_seller.php?id=<?= $seller['id'] ?>'>Approve</a> | 
        <a href='reject_seller.php?id=<?= $seller['id'] ?>' onclick="return confirm('Are you sure you want to reject this seller?')">Reject</a>
    </td>
</tr>
<?php endforeach; ?>
</table>
